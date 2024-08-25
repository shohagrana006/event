<?php

namespace App\Controller\Dashboard\Shared;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\AppServices;
use App\Entity\Event;
use App\Form\EventType;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;


class EventController extends Controller
{

    /**
     * @Route("/administrator/manage-events", name="dashboard_administrator_event", methods="GET")
     * @Route("/organizer/my-events", name="dashboard_organizer_event", methods="GET")
     */
    public function index(Request $request, PaginatorInterface $paginator, AppServices $services, AuthorizationCheckerInterface $authChecker)
    {
        $slug = ($request->query->get('slug')) == "" ? "all" : $request->query->get('slug');
        $category = ($request->query->get('category')) == "" ? "all" : $request->query->get('category');
        $venue = ($request->query->get('venue')) == "" ? "all" : $request->query->get('venue');
        $elapsed = ($request->query->get('elapsed')) == "" ? "all" : $request->query->get('elapsed');
        $published = ($request->query->get('published')) == "" ? "all" : $request->query->get('published');

        $organizer = "all";
        if ($authChecker->isGranted('ROLE_ORGANIZER')) {
            $organizer = $this->getUser()->getOrganizer()->getSlug();
        }

        $events = $paginator->paginate($services->getEvents(array("slug" => $slug, "category" => $category, "venue" => $venue, "elapsed" => $elapsed, "published" => $published, "organizer" => $organizer, "sort" => "startdate", "organizerEnabled" => "all", "sort" => "e.createdAt", "order" => "DESC"))->getQuery(), $request->query->getInt('page', 1), 10, array('wrap-queries' => true));
        return $this->render('Dashboard/Shared/Event/index.html.twig', [
            'events' => $events,
        ]);
    }

    /**
     * @Route("/organizer/my-events/add", name="dashboard_organizer_event_add", methods="GET|POST")
     * @Route("/organizer/my-events/{slug}/edit", name="dashboard_organizer_event_edit", methods="GET|POST")
     */
    public function addedit(Request $request, AppServices $services, TranslatorInterface $translator, $slug = null, AuthorizationCheckerInterface $authChecker, EntityManagerInterface $entityManager, Connection $connection,SessionInterface $session)
    {

        // dd($request->request->all(), $request->files->get('subscriber_file'));
        if ($session->has('file_data') && !empty($session->get('file_data'))) {
            $session->remove('file_data');
        }

        $em = $this->getDoctrine()->getManager();
        $organizer = "all";
        if ($authChecker->isGranted('ROLE_ORGANIZER')) {
            $organizer = $this->getUser()->getOrganizer()->getSlug();
        }

        if (!$slug) {
            $event = new Event();
            $form = $this->createForm(EventType::class, $event, array('validation_groups' => ['create', 'Default']));
        } else {
            $event = $services->getEvents(array('published' => 'all', "elapsed" => "all", 'slug' => $slug, 'organizer' => $organizer, "organizerEnabled" => "all"))->getQuery()->getOneOrNullResult();
            if (!$event) {
                $this->addFlash('error', $translator->trans('The event can not be found'));
                return $services->redirectToReferer('event');
            }
            $reference = $event->getReference();
            $form = $this->createForm(EventType::class, $event, array('validation_groups' => ['update', 'Default']));
        }

        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            if ($form->isValid()) {
                $input = $request->request->all();
                $input['event']['whatsapp_number'] = isset($input['whatsapp_number']) ? $input['whatsapp_number'] : null   ;
                $file = $_FILES['subscriber_file'];
                if (isset($reference)) {
                    $sql = "SELECT name, surname, email, phone_number, department, city, country, address 
                            FROM event_mails 
                            WHERE event_ref_id = :ref_id";
                    $params = ['ref_id' => $reference];
                    $statement = $connection->prepare($sql);
                    $statement->execute($params);
                    $csv_info = $statement->fetchAll();
                }

                $csv_upload = true;
                if (is_uploaded_file($file['tmp_name'])) {
                    $csv_upload = $this->event_mails_csv_check($event->getReference(), $input, $file, $entityManager);
                }

                if($csv_upload){
                    foreach ($event->getImages() as $image) {
                        $image->setEvent($event);
                    }
                    foreach ($event->getEventdates() as $eventdate) {
                        $eventdate->setEvent($event);
                        if (!$slug || !$eventdate->getReference()) {
                            $eventdate->setReference($services->generateReference(10));
                        }
                        foreach ($eventdate->getTickets() as $eventticket) {
                            if($eventdate->getOnline()){
                                $eventticket->setTicketsperattendee(1);
                            }
                            $eventticket->setEventdate($eventdate);
                            if (!$slug || !$eventticket->getReference()) {
                                $eventticket->setReference($services->generateReference(10));
                            }
                        }
                    }
                    if (!$slug) {
                        $event->setOrganizer($this->getUser()->getOrganizer());
                        $rr = $event->setReference($services->generateReference(10));
                        if (is_uploaded_file($file['tmp_name'])) {
                            $this->event_mails_data_save($rr->getReference(), $input, $file, $entityManager);
                        }
                        $this->addFlash('success', $translator->trans('The event has been successfully created'));
                    } else {
                        if (is_uploaded_file($file['tmp_name'])) {
                            $this->event_mails_data_edit($reference, $input,$csv_info, $file, $entityManager);
                        }
                        $this->addFlash('success', $translator->trans('The event has been successfully updated'));
                    }
                    $em->persist($event);
                    $em->flush();
                    if ($authChecker->isGranted('ROLE_ORGANIZER')) {
                        return $this->redirectToRoute("dashboard_organizer_event");
                    } elseif ($authChecker->isGranted('ROLE_ADMINISTRATOR')) {
                        return $this->redirectToRoute("dashboard_administrator_event");
                    }
                } else {
                    $this->addFlash('error', $translator->trans('Must be upload a valid CSV file. Download and show demo CSV'));
                }

            } else {
                // $fileData = [];

                // // Check if subscriber_file exists and is an instance of UploadedFile
                // $subscriberFile = $request->files->get('subscriber_file');
                // if ($subscriberFile instanceof UploadedFile) {
                //     $fileData['subscriber_file'] = $subscriberFile->getClientOriginalName();
                // }

                // // Check if event file exists and is an instance of UploadedFile
                // $eventFile = $request->files->get('event')['imageFile']['file'];
                // if ($eventFile instanceof UploadedFile) {
                //     $fileData['event_file'] = $eventFile->getClientOriginalName();
                // }
                // $session->set('file_data', $fileData);

                $this->addFlash('error', $translator->trans('The form contains invalid data'));
            }
        }

        // organizer list get
        $user = $this->getUser();
        $userId = $user->getId();
        $sqlSelect = "SELECT * FROM subscriber_lists WHERE org_id = :org_id ORDER BY id DESC";
        $statementSelect = $entityManager->getConnection()->prepare($sqlSelect);
        $statementSelect->execute(['org_id' => $userId]);
        $subscriber_lists = $statementSelect->fetchAll();
        return $this->render('Dashboard/Shared/Event/add-edit.html.twig', array(
            "event"          => $event,
            "form"           => $form->createView(),
            "subscriber_lists" => $subscriber_lists
        ));
    }


    public function event_mails_csv_check($event_ref_id, $input, $file, $entityManager)
    {
        try {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $tmpFilePath = $file['tmp_name'];
                if ($file['type'] === 'text/csv') {

                    $handle = fopen($tmpFilePath, 'r');
                    $datas = [];
                    $headers = fgetcsv($handle);
                    while (($row = fgetcsv($handle)) !== false) {
                        if (count($row) !== count($headers)) {
                            continue;
                        }
                        $datas[] = array_combine($headers, $row);
                    }

                    fclose($handle);
                    $subscriber_list_id = $input['subscriber_id'];
                    $send_type = $input['event']['sendevent'] == 1 ? 'corporate' : 'massive';
                    $send_chanel = $input['event']['sendchanel'] == 1 ? 'email' : 'whatsapp';

                    $processedEmails = [];
                    foreach ($datas as $data) {
                        $email = $data['email'];
                        if (in_array($email, $processedEmails)) {
                            continue;
                        }
                        $processedEmails[] = $email;
                        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";

                        if ((!preg_match($pattern, $data['email']))) {
                            $sql = "INSERT INTO event_invalid_mails (event_ref_id, send_type, send_chanel,subscriber_list_id, name, surname, email, country_code, phone_number, department, city, country,address) VALUES (:event_ref_id, :send_type, :send_chanel,:subscriber_list_id, :name, :surname, :email, :country_code, :phone_number, :department, :city, :country,:address)";
                        } else {
                            $sql = "INSERT INTO event_mails (event_ref_id, send_type, send_chanel,subscriber_list_id, name, surname, email, country_code, phone_number, department, city, country,address) VALUES (:event_ref_id, :send_type, :send_chanel,:subscriber_list_id, :name, :surname, :email, :country_code, :phone_number, :department, :city, :country,:address)";
                        }

                        $country_code = preg_replace('/[^0-9]/', '', $data['country_code']);
                        $phone_number = preg_replace('/[^0-9]/', '', $data['phone_number']);

                        $params = [
                            'event_ref_id'       => $event_ref_id,
                            'send_type'          => $send_type,
                            'send_chanel'        => $send_chanel,
                            'subscriber_list_id' => $subscriber_list_id,
                            'name'               => $data['name'],
                            'surname'            => $data['surname'],
                            'email'              => $data['email'],
                            'country_code'       => $country_code,
                            'phone_number'       => $phone_number,
                            'department'         => $data['department'],
                            'city'               => $data['city'],
                            'country'            => $data['country'],
                            'address'            => $data['address'],
                        ];
                        $statement = $entityManager->getConnection()->prepare($sql);
                    }
                    return true;

                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function event_mails_data_save($event_ref_id, $input, $file, $entityManager)
    {
        try {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $tmpFilePath = $file['tmp_name'];
                if ($file['type'] === 'text/csv') {

                    $handle = fopen($tmpFilePath, 'r');
                    $datas = [];
                    $headers = fgetcsv($handle);
                    while (($row = fgetcsv($handle)) !== false) {
                        if (count($row) !== count($headers)) {
                            continue;
                        }
                        $datas[] = array_combine($headers, $row);
                    }

                    fclose($handle);
                    $subscriber_list_id = $input['subscriber_id'];
                    $send_type = $input['event']['sendevent'] == 1 ? 'corporate' : 'massive';
                    $send_chanel = $input['event']['sendchanel'] == 1 ? 'email' : 'whatsapp';

                    $processedEmails = [];
                    foreach ($datas as $data) {
                        $email = $data['email'];
                        if (in_array($email, $processedEmails)) {
                            continue; 
                        }
                        $processedEmails[] = $email;
                        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";

                        if ((!preg_match($pattern, $data['email']))) {
                            $sql = "INSERT INTO event_invalid_mails (event_ref_id, send_type, send_chanel,subscriber_list_id, name, surname, email, country_code, phone_number, department, city, country,address) VALUES (:event_ref_id, :send_type, :send_chanel,:subscriber_list_id, :name, :surname, :email, :country_code, :phone_number, :department, :city, :country,:address)";
                        } else {
                            $sql = "INSERT INTO event_mails (event_ref_id, send_type, send_chanel,subscriber_list_id, name, surname, email, country_code, phone_number, department, city, country,address) VALUES (:event_ref_id, :send_type, :send_chanel,:subscriber_list_id, :name, :surname, :email, :country_code, :phone_number, :department, :city, :country,:address)";
                        }

                        $country_code = preg_replace('/[^0-9]/', '', $data['country_code']);
                        $phone_number = preg_replace('/[^0-9]/', '', $data['phone_number']);

                        $params = [
                            'event_ref_id'       => $event_ref_id,
                            'send_type'          => $send_type,
                            'send_chanel'        => $send_chanel,
                            'subscriber_list_id' => $subscriber_list_id,
                            'name'               => $data['name'],
                            'surname'            => $data['surname'],
                            'email'              => $data['email'],
                            'country_code'       => $country_code,
                            'phone_number'       => $phone_number,
                            'department'         => $data['department'],
                            'city'               => $data['city'],
                            'country'            => $data['country'],
                            'address'            => $data['address'],
                        ];

                        $statement = $entityManager->getConnection()->prepare($sql);
                        $success = $statement->execute($params);

                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public function event_mails_data_edit($event_ref_id, $input, $csv_info, $file, $entityManager)
    {
        try {
            if ($file['error'] === UPLOAD_ERR_OK) {
                
                $tmpFilePath = $file['tmp_name'];
                if ($file['type'] === 'text/csv') {
                   
                    $handle = fopen($tmpFilePath, 'r');
                    $datas = [];
                    $headers = fgetcsv($handle);
                    while (($row = fgetcsv($handle)) !== false) {
                        if (count($row) !== count($headers)) {
                            continue;
                        }
                        $datas[] = array_combine($headers, $row);
                    }
                    fclose($handle);

                    $processedEmails = [];
                    foreach ($datas as $newRow) {
                        $email = $newRow['email'];
                        if (in_array($email, $processedEmails)) {
                            continue;
                        }
                        $processedEmails[] = $email;

                        $matched = false;
                        foreach ($csv_info as $existingRow) {
                            if ($existingRow['email'] == $newRow['email'] || $existingRow['phone_number'] == $newRow['phone_number']) {

                                $country_code = preg_replace('/[^0-9]/', '', $newRow['country_code']);
                                $phone_number = preg_replace('/[^0-9]/', '', $newRow['phone_number']);
                                $updateSql = "UPDATE event_mails SET name = :name, surname = :surname, country_code = :country_code, phone_number = :phone_number, department = :department, city = :city, country = :country, address = :address WHERE email = :email";
                                $updateParams = [
                                    'name'         => $newRow['name'],
                                    'surname'      => $newRow['surname'],
                                    'country_code' => $country_code,
                                    'phone_number' => $phone_number,
                                    'department'   => $newRow['department'],
                                    'city'         => $newRow['city'],
                                    'country'      => $newRow['country'],
                                    'address'      => $newRow['address'],
                                    'email'        => $newRow['email']
                                ];
                                $updateStatement = $entityManager->getConnection()->prepare($updateSql);
                                $success = $updateStatement->execute($updateParams);


                                $matched = true;
                                break;
                            }
                        }
                        if (!$matched) {
                            $differences[] = $newRow;
                        }
                    }

                    // Delete rows from event_invalid_mails where event_ref_id matches
                    $deleteSql = "DELETE FROM event_invalid_mails WHERE event_ref_id = :event_ref_id";
                    $deleteParams = ['event_ref_id' => $event_ref_id];
                    $deleteStatement = $entityManager->getConnection()->prepare($deleteSql);
                    $deleteStatement->execute($deleteParams);

                
                    if(!empty($differences)){
                        
                        $subscriber_list_id = $input['subscriber_id'];
                        $send_type = $input['event']['sendevent'] == 1 ? 'corporate' : 'massive';
                        $send_chanel = $input['event']['sendchanel'] == 1 ? 'email' : 'whatsapp';
                        foreach ($differences as $data) {
           
                            $country_code = preg_replace('/[^0-9]/', '', $data['country_code']);
                            $phone_number = preg_replace('/[^0-9]/', '', $data['phone_number']);

                            $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                            if ((!preg_match($pattern, $data['email']))) {
                                $sql = "INSERT INTO event_invalid_mails (event_ref_id, send_type, send_chanel,subscriber_list_id, name, surname, email, country_code, phone_number, department, city, country,address) 
                                VALUES (:event_ref_id, :send_type, :send_chanel,:subscriber_list_id, :name, :surname, :email, :country_code, :phone_number, :department, :city, :country,:address)";
                            } else {
                                $sql = "INSERT INTO event_mails (event_ref_id, send_type, send_chanel,subscriber_list_id, name, surname, email, country_code, phone_number, department, city, country,address) 
                                VALUES (:event_ref_id, :send_type, :send_chanel,:subscriber_list_id, :name, :surname, :email, :country_code, :phone_number, :department, :city, :country,:address)";
                            }
                            
                            $params = [
                                'event_ref_id'       => $event_ref_id,
                                'send_type'          => $send_type,
                                'send_chanel'        => $send_chanel,
                                'subscriber_list_id' => $subscriber_list_id,
                                'name'               => $data['name'],
                                'surname'            => $data['surname'],
                                'email'              => $data['email'],
                                'country_code'       => $country_code,
                                'phone_number'       => $phone_number,
                                'department'         => $data['department'],
                                'city'               => $data['city'],
                                'country'            => $data['country'],
                                'address'            => $data['address'],
                            ];

                            $statement = $entityManager->getConnection()->prepare($sql);
                            $success = $statement->execute($params);
                        }
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @Route("/administrator/manage-events/{slug}/delete-permanently", name="dashboard_administrator_event_delete_permanently", methods="GET")
     * @Route("/administrator/manage-events/{slug}/delete", name="dashboard_administrator_event_delete", methods="GET")
     * @Route("/organizer/my-events/{slug}/delete-permanently", name="dashboard_organizer_event_delete_permanently", methods="GET")
     * @Route("/organizer/my-events/{slug}/delete", name="dashboard_organizer_event_delete", methods="GET")
     */
    public function delete(Request $request, AppServices $services, TranslatorInterface $translator, $slug, AuthorizationCheckerInterface $authChecker)
    {
        $organizer = "all";
        if ($authChecker->isGranted('ROLE_ORGANIZER')) {
            $organizer = $this->getUser()->getOrganizer()->getSlug();
        }

        $event = $services->getEvents(array("slug" => $slug, "published" => "all", "elapsed" => "all", "organizer" => $organizer, "organizerEnabled" => "all"))->getQuery()->getOneOrNullResult();
        if (!$event) {
            $this->addFlash('error', $translator->trans('The event can not be found'));
            return $services->redirectToReferer('event');
        }

        if ($event->getOrderElementsQuantitySum() > 0) {
            $this->addFlash('error', $translator->trans('The event can not be deleted because it has one or more orders'));
            return $services->redirectToReferer('event');
        }
        if ($event->getDeletedAt() !== null) {
            $this->addFlash('error', $translator->trans('The event has been deleted permanently'));
        } else {
            $this->addFlash('notice', $translator->trans('The event has been deleted'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->remove($event);
        $em->flush();
        return $services->redirectToReferer('event');
    }

    /**
     * @Route("/administrator/manage-events/{slug}/restore", name="dashboard_administrator_event_restore", methods="GET")
     */
    public function restore($slug, Request $request, TranslatorInterface $translator, AppServices $services)
    {

        $event = $services->getEvents(array("slug" => $slug, "published" => "all", "elapsed" => "all", "organizer" => "all", "organizerEnabled" => "all"))->getQuery()->getOneOrNullResult();
        if (!$event) {
            $this->addFlash('error', $translator->trans('The event can not be found'));
            return $services->redirectToReferer('event');
        }
        $event->setDeletedAt(null);
        $em = $this->getDoctrine()->getManager();
        $em->persist($event);
        $em->flush();
        $this->addFlash('success', $translator->trans('The event has been succesfully restored'));

        return $services->redirectToReferer('event');
    }

    /**
     * @Route("/organizer/my-events/{slug}/publish", name="dashboard_organizer_event_publish", methods="GET")
     * @Route("/organizer/my-events/{slug}/draft", name="dashboard_organizer_event_draft", methods="GET")
     */
    public function showhide(Request $request, AppServices $services, TranslatorInterface $translator, $slug, AuthorizationCheckerInterface $authChecker)
    {

        $organizer = "all";
        if ($authChecker->isGranted('ROLE_ORGANIZER')) {
            $organizer = $this->getUser()->getOrganizer()->getSlug();
        }

        $event = $services->getEvents(array("slug" => $slug, "published" => "all", "elapsed" => "all", "organizer" => $organizer, "organizerEnabled" => "all"))->getQuery()->getOneOrNullResult();
        if (!$event) {
            $this->addFlash('error', $translator->trans('The event can not be found'));
            return $services->redirectToReferer('event');
        }
        if ($event->getPublished() === true) {
            $event->setPublished(false);
            $this->addFlash('notice', $translator->trans('The event has been unpublished and will not be included in the search results'));
        } else {
            $event->setPublished(true);
            $this->addFlash('success', $translator->trans('The event has been published and will figure in the search results'));
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($event);
        $em->flush();
        return $services->redirectToReferer('event');
    }

    /**
     * @Route("/administrator/manage-events/{slug}/details", name="dashboard_administrator_event_details", methods="GET", condition="request.isXmlHttpRequest()")
     * @Route("/organizer/my-events/{slug}/details", name="dashboard_organizer_event_details", methods="GET", condition="request.isXmlHttpRequest()")
     */
    public function details(Request $request, AppServices $services, TranslatorInterface $translator, $slug, AuthorizationCheckerInterface $authChecker,Connection $connection)
    {

        $organizer = "all";
        if ($authChecker->isGranted('ROLE_ORGANIZER')) {
            $organizer = $this->getUser()->getOrganizer()->getSlug();
        }

        $event = $services->getEvents(array("slug" => $slug, "published" => "all", "elapsed" => "all", "organizer" => $organizer, "organizerEnabled" => "all"))->getQuery()->getOneOrNullResult();
        if (!$event) {
            return new Response($translator->trans('The event can not be found'));
        }
        
        $eventDate = $event->getEventDates()->toArray();
        $meeting_id =  isset($eventDate[0]) ? $eventDate[0]->getMeetinglink() : null;
        $meeting_link = '';
        
        if (!empty($meeting_id) or $meeting_id != null) {
            $sql = "SELECT * FROM event_zoom_meeting_list WHERE id = :id";
            $params = ['id' => $meeting_id];
            $statement = $connection->prepare($sql);
            $statement->execute($params);
            $event_meeting = $statement->fetch();

            if (!empty($event_meeting) or $event_meeting != null) {
                $meeting_link = $event_meeting['start_url'];
            }
        }

    

        return $this->render('Dashboard/Shared/Event/details.html.twig', [
            'event' => $event,
            'meeting_link' => $meeting_link
        ]);
    }


    /**
     * @Route("/organizer/my-events/addlist", name="dashboard_organizer_event_addlist", methods="GET|POST")
     */
    public function addlist(Request $request, EntityManagerInterface $entityManager)
    {
        $input = $request->request->all();

        $user = $this->getUser();
        $userId = $user->getId();

        $sql = "INSERT INTO subscriber_lists (org_id, name, tag, description) VALUES (:org_id, :name, :tag, :description)";
        $params = [
            'org_id'        => $userId,
            'name'          => trim($input['name']),
            'tag'           => trim($input['tag']),
            'description'   => trim($input['description'])
        ];

        $statement = $entityManager->getConnection()->prepare($sql);
        $statement->execute($params);
        return $this->redirectToRoute('dashboard_organizer_event_add');
    }

    /**
     * @Route("/file/preview", name="file_preview_download", methods="GET")
     */
    public function filePreview(Request $request, KernelInterface $kernel)
    {
        $filePath = $kernel->getProjectDir() . '/public/demo_file.csv';
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('The file does not exist');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, basename($filePath));
        return $response;
    }
}
