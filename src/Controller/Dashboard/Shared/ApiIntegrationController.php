<?php

namespace App\Controller\Dashboard\Shared;

use App\Entity\GoogleMeeting;
use App\Service\GoogleMeetService;
use App\Service\TestZoomService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Service\ZoomService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use App\Service\MicrosoftGraphService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class ApiIntegrationController extends Controller {

    /**
     * @Route("/zoom/schedule/meeting", name="zoom_schedule_meeting", methods="GET")
     */
    public function index() {
        $timezones = timezone_identifiers_list();
        return $this->render('Dashboard/Shared/ApiIntegration/zoom.html.twig',[
            'timezones'    => $timezones,
        ]);
    }


    public function getTimeZoneTime($timezone){
        $timezone_object = new \DateTimeZone($timezone);
        $offset = $timezone_object->getOffset(new \DateTime()) / 3600;
        if ($offset >= 0) {
            return "+".$offset*3600;
        } else {
            return $offset*3600;
        }
    }


    /**
     * @Route("/zoom/schedule/meeting/post", name="zoom_data_post", methods="POST")
     */
    public function createMeetingZoom(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $input = $request->request->all();
        $date             = date('Y-m-d\TH:i:s', strtotime($input['date_time']));
        $duration         = $input['hour'] * 60 + $input['minute'];
        $password         = $input['password'] ?? '';

        $teams_esta       = isset($input['setting']['teams_esta']) ? true : false;
        $host_start_video = isset($input['setting']['host_start_video']) ? true : false;
        $start_video      = isset($input['setting']['start_video']) ? true : false;
        $auto_mute        = isset($input['setting']['auto_mute']) ? true : false;
        $waiting_room     = isset($input['setting']['waiting_room']) ? true : false;

        $data = [
            'topic' => $input['topic'],
            'type' => 2, // Scheduled meeting
            'start_time' => $date,
            'duration' => $duration, // Meeting duration in minutes
            'timezone' => $input['timezone'],
            'agenda' => $input['description'],
            "password" => $password,
            "settings" => [
                'host_video' => $host_start_video,
                'participant_video' => $start_video,
                'mute_upon_entry' => $auto_mute,
                'waiting_room' => $waiting_room,
            ],
        ];

        $user = $this->getUser();
        $userId = $user->getId();
        $apiType = 'zoom';
        $sqlSelect = "SELECT * FROM api_settings WHERE user_id = :user_id AND api_type = :api_type";
        $paramsSelect = [
            'user_id' => $userId,
            'api_type' => $apiType,
        ];
        $statementSelect = $entityManager->getConnection()->prepare($sqlSelect);
        $statementSelect->execute($paramsSelect);
        $zoom_data = $statementSelect->fetch();
        $zoomService = new ZoomService($zoom_data['zoom_account_id']??'', $zoom_data['zoom_client_id']??'', $zoom_data['zoom_client_secret']??'');
        $response = $zoomService->createMeeting($data);

        if ($response['status'] == false) {
            $this->addFlash('error', $translator->trans('At first zoom credential setting on your profile'));
            return $this->redirect($request->headers->get('referer'));
        }


        $dateTime = new \DateTime($response['data']['start_time']);
        $createDateTime = new \DateTime($response['data']['created_at']);

        $startDate    = $dateTime->format('Y-m-d H:i:s');
        $end_date_sec = $response['data']['duration'] * 60 + strtotime($startDate);
        $endDate      = date('Y-m-d H:i:s', $end_date_sec);
        $createdAt    = $createDateTime->format('Y-m-d H:i:s');
        $user = $this->getUser();
        $authId = $user->getId();

        $sql = "INSERT INTO event_zoom_meeting_list (type, org_id, uuid, meeting_id, host_id, host_email, topic, status, duration, timezone, agenda, start_url, join_url, password, start_date, end_date, created_at, settings) VALUES (:type, :org_id, :uuid, :meeting_id, :host_id, :host_email, :topic, :status, :duration, :timezone, :agenda, :start_url, :join_url, :password, :start_date, :end_date, :created_at , :settings)";
                    $params = [
                        'type'              => 'zoom',
                        'uuid'              => $response['data']['uuid'],
                        'meeting_id'        => $response['data']['id'],
                        'host_id'           => $response['data']['host_id'],
                        'host_email'        => $response['data']['host_email'],
                        'topic'             => $response['data']['topic'],
                        'status'            => $response['data']['status'],
                        'duration'          => $response['data']['duration'],
                        'timezone'          => $response['data']['timezone'],
                        'agenda'            => $response['data']['agenda'] ?? "",
                        'start_url'         => $response['data']['start_url'],
                        'join_url'          => $response['data']['join_url'],
                        'password'          => $response['data']['password'],
                        'settings'          => null,
                        'org_id'            => $authId,
                        'start_date'        => $startDate,
                        'end_date'          => $endDate,
                        'created_at'         => $createdAt,
                        // 'settings'          => json_encode($response['data']['settings']),
                    ];

                    $statement = $entityManager->getConnection()->prepare($sql);
                    $statement->execute($params);

        return $this->redirectToRoute('dashboard_organizer_venue_add');
        // return new JsonResponse($response);
    }


    /**
     * @Route("/google/schedule/meeting", name="google_schedule_meeting", methods="GET")
     */
    public function googleMeeting()
    {
        $timezones = timezone_identifiers_list();
        return $this->render('Dashboard/Shared/ApiIntegration/google.html.twig', [
            'timezones'    => $timezones,
        ]);
    }

    /**
     * @Route("/google/schedule/meeting/post", name="google_data_post", methods="POST")
     */
    public function createGoogleMeeting(Request $request, EntityManagerInterface $entityManager, KernelInterface $kernel,TranslatorInterface $translator)
    {
        $input            = $request->request->all();

        $date             = date('Y-m-d\TH:i:s', strtotime($input['date_time']));
        $duration         = ($input['hour'] * 60 + $input['minute']) *60;
        $end_date_sec     = $duration + strtotime($input['date_time']);
        $end_date         = date('Y-m-d\TH:i:s', $end_date_sec);
        $password         = $input['password'] ?? '';

        $host_start_video = isset($input['setting']['host_start_video']) ? true : false;
        $start_video      = isset($input['setting']['start_video']) ? true : false;
        $auto_mute        = isset($input['setting']['auto_mute']) ? true : false;
        $waiting_room     = isset($input['setting']['waiting_room']) ? true : false;
        $data = [
            'topic'      => $input['topic'],
            'type'       => 2, // Scheduled meeting
            'start_time' => $date,
            'end_date'   => $end_date,
            'duration'   => $duration, // Meeting duration in sec
            'timezone'   => $input['timezone'],
            'agenda'     => $input['description'],
            "password"   => $password,
            "settings"   => [
                'host_video' => $host_start_video,
                'participant_video' => $start_video,
                'mute_upon_entry' => $auto_mute,
                'waiting_room' => $waiting_room,
            ],
        ];

        $this->get('session')->set('form_data', $data);


        $user = $this->getUser();
        $userId = $user->getId();
        $apiType = 'google';
        $sqlSelect = "SELECT * FROM api_settings WHERE user_id = :user_id AND api_type = :api_type";
        $paramsSelect = [
            'user_id' => $userId,
            'api_type' => $apiType,
        ];
        $statementSelect = $entityManager->getConnection()->prepare($sqlSelect);
        $statementSelect->execute($paramsSelect);
        $google_data = $statementSelect->fetch();

        $rootPath = $kernel->getProjectDir() . '/public/google_credential';

        if($google_data != false && ($google_data['google_filename'] != null) && file_exists($rootPath . '/' . $google_data['google_filename']) ){
            $googleMeetService = new GoogleMeetService($google_data, $kernel);
        }else{
            $this->addFlash('error', $translator->trans('At first google credential setting on your profile'));
            return $this->redirect($request->headers->get('referer'));
        }

        $google_url = $googleMeetService->authorized();
        return new RedirectResponse($google_url);
    }

    /**
     * @Route("/google/redirect", name="google_redirect")
     */
    public function googleMeetingRedirect(EntityManagerInterface $entityManager, KernelInterface $kernel)
    {

        $user = $this->getUser();
        $userId = $user->getId();
        $apiType = 'google';
        $sqlSelect = "SELECT * FROM api_settings WHERE user_id = :user_id AND api_type = :api_type";
        $paramsSelect = [
            'user_id' => $userId,
            'api_type' => $apiType,
        ];
        $statementSelect = $entityManager->getConnection()->prepare($sqlSelect);
        $statementSelect->execute($paramsSelect);
        $google_data = $statementSelect->fetch();

        $googleMeetService = new GoogleMeetService($google_data, $kernel);


        $value = $this->get('session')->get('form_data');
        $response = $googleMeetService->googleRedirect($value);

        $user = $this->getUser();
        $userId = $user->getId();
        $calendarId = $google_data['google_calendar_id'];
        $topic = $response->summary;
        $description = $response->description;
        $meetUrl = $response->hangoutLink;
        $timeZone = $response->start->timeZone;

        $startDate = date('Y-m-d H:i:s', strtotime($response->start->dateTime));
        $endDate   = date('Y-m-d H:i:s', strtotime($response->end->dateTime));
        $createdAt = date('Y-m-d H:i:s', strtotime($response->created));

        // $sql = "INSERT INTO google_meetings (organizer_id,calendar_id, topic, description, meet_url, time_zone, start_date, end_date, created_at)
        //         VALUES (:organizer_id, :calendar_id, :topic, :description, :meet_url, :time_zone, :start_date, :end_date, :created_at)";
        // $params = [
        //     'organizer_id' => $userId,
        //     'calendar_id' => $calendarId,
        //     'topic' => $topic,
        //     'description' => $description,
        //     'meet_url' => $meetUrl,
        //     'time_zone' => $timeZone,
        //     'start_date' => $startDate,
        //     'end_date' => $endDate,
        //     'created_at' => $createdAt,
        // ];

        $sql = "INSERT INTO event_zoom_meeting_list (type, org_id, host_email, topic, agenda, join_url, timezone, start_date, end_date, created_at)
                VALUES (:type, :org_id, :host_email, :topic, :agenda, :join_url, :timezone, :start_date, :end_date, :created_at)";

        $params = [
            'type' => 'google',
            'org_id' => $userId,
            'host_email' => $calendarId,
            'topic' => $topic,
            'agenda' => $description,
            'join_url' => $meetUrl,
            'timezone' => $timeZone,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_at' => $createdAt,
        ];

        $statement = $entityManager->getConnection()->prepare($sql);
        $statement->execute($params);
        return $this->redirectToRoute('dashboard_organizer_venue_add');
    }



    /**
     * @Route("/teams/schedule/meeting", name="teams_schedule_meeting", methods="GET")
     */
    public function teamsMeeting()
    {
        $timezones = timezone_identifiers_list();
        return $this->render('Dashboard/Shared/ApiIntegration/teams.html.twig', [
            'timezones'    => $timezones,
        ]);
    }



    public function getApiSettingCredentials($entityManager, $api_type){
        $user = $this->getUser();
        $userId = $user->getId();
        $apiType = $api_type;
        $sqlSelect = "SELECT * FROM api_settings WHERE user_id = :user_id AND api_type = :api_type";
        $paramsSelect = [
            'user_id' => $userId,
            'api_type' => $apiType,
        ];
        $statementSelect = $entityManager->getConnection()->prepare($sqlSelect);
        $statementSelect->execute($paramsSelect);
        return $statementSelect->fetch();
    }

    /**
     * @Route("teams/auth/login", name="teams_data_post")
     */
    public function login(Request $request, MicrosoftGraphService $microsoftGraphService, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $teams_api_credential = $this->getApiSettingCredentials($entityManager, 'teams');
        if(!$teams_api_credential){
            $this->addFlash('error', $translator->trans('At first teams credential setting on your profile'));
            return $this->redirect($request->headers->get('referer'));
        }
        $authUrl = $microsoftGraphService->getAuthorizationUrl($teams_api_credential);
        return $this->redirect($authUrl);
    }

    /**
     * @Route("/teams/redirect", name="teams_redirect")
     */
    public function callback(Request $request, MicrosoftGraphService $microsoftGraphService, EntityManagerInterface $entityManager, KernelInterface $kernel)
    {
        $code = $request->query->get('code');
        if (!$code) {
            return new Response('Authorization code not found in the request.', 400);
        }

        $teams_api_credential = $this->getApiSettingCredentials($entityManager, 'teams');
        $tokens = $microsoftGraphService->getAccessToken($code, $teams_api_credential);

        if (isset($tokens['error'])) {
            return new Response('Error fetching access token: ' . $tokens['error'], 400);
        }

        $accessToken = $tokens['access_token'];

        $subject = 'My Teams Meeting';
        $startDateTime = (new \DateTime('+1 hour', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s');
        $endDateTime = (new \DateTime('+2 hours', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s');

        $meeting = $microsoftGraphService->createMeeting($accessToken, $subject, $startDateTime, $endDateTime);

        if (isset($meeting['error'])) {
            return new Response('Error creating meeting: ' . $meeting['error'], 400);
        }

        $user = $this->getUser();
        $userId = $user->getId();
        $topic = $meeting->summary;
        $description = $meeting->description;
        $meetUrl = $meeting->hangoutLink;
        $timeZone = $meeting->start->timeZone;

        $startDate = date('Y-m-d H:i:s', strtotime($meeting->start->dateTime));
        $endDate   = date('Y-m-d H:i:s', strtotime($meeting->end->dateTime));
        $createdAt = date('Y-m-d H:i:s', strtotime($meeting->created));

        $sql = "INSERT INTO event_zoom_meeting_list (type, org_id, topic, agenda, join_url, timezone, start_date, end_date, created_at)
                VALUES (:type, :org_id, :topic, :agenda, :join_url, :timezone, :start_date, :end_date, :created_at)";

        $params = [
            'type' => 'teams',
            'org_id' => $userId,
            'topic' => $topic,
            'agenda' => $description,
            'join_url' => $meetUrl,
            'timezone' => $timeZone,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'created_at' => $createdAt,
        ];

        $statement = $entityManager->getConnection()->prepare($sql);
        $statement->execute($params);
        return $this->redirectToRoute('dashboard_organizer_venue_add');


    }














}

