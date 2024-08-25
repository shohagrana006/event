<?php

namespace App\Service;

use Google_Client;
use Google_Service_Calendar;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Doctrine\ORM\EntityManagerInterface;

class GoogleMeetService
{


  public $client;
  public $kernel;
  public $google_data;

  public function __construct($google_data, $kernel)
  {
    $this->kernel = $kernel;
    $this->google_data = $google_data;
    $rootPath = $this->kernel->getProjectDir(). '/public/google_credential';
    $this->client = new Google_Client();
    // Set the application name, this is included in the User-Agent HTTP header.
    $this->client->setApplicationName("Eventos");
    // Set the authentication credentials we downloaded from Google.
    $this->client->setAuthConfig($rootPath.'/'. $google_data['google_filename']);
    // Setting offline here means we can pull data from the venue's calendar when they are not actively using the site.
    $this->client->setAccessType('offline');
    // This will include any other scopes (Google APIs) previously granted by the venue
    $this->client->setIncludeGrantedScopes(true);
    // Set this to force to consent form to display.
    $this->client->setApprovalPrompt('force');
    // Add the Google Calendar scope to the request.
    $this->client->addScope(Google_Service_Calendar::CALENDAR);
  }


  public function authorized()
  {
    $authUrl = $this->client->createAuthUrl();
    $filteredUrl = filter_var($authUrl, FILTER_SANITIZE_URL);
    return $filteredUrl;
  }


  public function googleRedirect($request)
  {

    $slugger = new AsciiSlugger();
    $randomBytes = random_bytes(5);
    $randomString = $slugger->slug(bin2hex($randomBytes));
    $meetingId = $randomString->toString();


    $CODE = $_GET['code'];
    $calendarId = $this->google_data['google_calendar_id'];
    // Exchange authorization code for access token
    $this->client->fetchAccessTokenWithAuthCode($CODE);
    // Set the access token
    $accessToken = $this->client->getAccessToken();

    $this->client->setAccessToken($accessToken);

    // Optionally, check if the access token is expired and refresh it
    if ($this->client->isAccessTokenExpired()) {
      if ($this->client->getRefreshToken()) {
        $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
      }
    }

    $service = new \Google_Service_Calendar($this->client);

    $title       = $request['topic'];
    $startDate   = $request['start_time'];
    $endDate     = $request['end_date'];
    $timezone    = $request['timezone'];
    $description = $request['agenda'];

    $event = new \Google_Service_Calendar_Event(array(
      'summary' => $title,
      'description' => $description,
      'start' => array(
        'dateTime' => $startDate,
        'timeZone' => $timezone,
      ),
      'end' => array(
        'dateTime' => $endDate,
        'timeZone' => $timezone,
      ),
    ));
    $event = $service->events->insert($calendarId, $event);

    $conference = new \Google_Service_Calendar_ConferenceData();
    $conferenceRequest = new \Google_Service_Calendar_CreateConferenceRequest();
    $conferenceRequest->setRequestId($meetingId);
    $conference->setCreateRequest($conferenceRequest);
    $event->setConferenceData($conference);
    $event = $service->events->patch($calendarId, $event->id, $event, ['conferenceDataVersion' => 1]);
    // $value = $this->get('session')->remove('form_data');
    return $event;
  }



}