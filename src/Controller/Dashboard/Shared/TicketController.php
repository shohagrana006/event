<?php

namespace App\Controller\Dashboard\Shared;

use Google\Service\Adsense\TimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Service\AppServices;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Swift_Mailer;
use Twig\Environment;
use Doctrine\DBAL\Connection;
use Picqer\Barcode\BarcodeGeneratorPNG;
use GuzzleHttp\Client;
use App\Entity\CartElement;
use App\Entity\Order;
use App\Entity\TicketReservation;
use App\Entity\OrderTicket;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\OrderElement;
use FOS\UserBundle\Model\UserManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;


class TicketController extends Controller
{
    private $userManager;
    private $requestStack;

    public function __construct(UserManagerInterface $userManager , RequestStack $requestStack)
    {
        // For Sign Up 
        $this->userManager = $userManager;
        $this->requestStack = $requestStack;
    }

    public function sendTicket(Request $request, AppServices $services, TranslatorInterface $translator, Connection $connection, $event = null)
    {
        $sqlEvent = "SELECT * FROM eventic_event WHERE id = :id";
        $paramsEvent = ['id' => $event];
        $statementEvent = $connection->prepare($sqlEvent);
        $statementEvent->execute($paramsEvent);
        $one_event = $statementEvent->fetch();

        if (!$one_event) {
            $this->addFlash('error', $translator->trans('The event can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $link = $_ENV['MAIN_DOMAIN'] . 'join_event_meeting/' . $one_event['reference'];

        if (!$link) {
            $this->addFlash('error', $translator->trans('The event Link can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $sql = "SELECT * FROM eventic_event_date WHERE event_id = :id";
        $params = ['id' => $event];
        $statement = $connection->prepare($sql);
        $statement->execute($params);
        $event_date = $statement->fetch();

        if (!$event_date) {
            $this->addFlash('error', $translator->trans('The event date can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }
        $event_date_id = $event_date['id'];

        $sql2 = "SELECT * FROM eventic_event_date_ticket WHERE eventdate_id = :id";
        $params2 = ['id' => $event_date_id];
        $statement2 = $connection->prepare($sql2);
        $statement2->execute($params2);
        $event_date_ticket = $statement2->fetch();

        if (!$event_date_ticket) {
            $this->addFlash('error', $translator->trans('The event date ticket can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $event_date_ticket_id = $event_date_ticket['id'];

        $sql3 = "SELECT * FROM eventic_order_element WHERE eventticket_id = :id";
        $params3 = ['id' => $event_date_ticket_id];
        $statement3 = $connection->prepare($sql3);
        $statement3->execute($params3);
        $event_orders = $statement3->fetchAll();

        if (!empty($event_orders)) {
            foreach ($event_orders as $event_order) {
                $event_order_id = $event_order['order_id'];

                $sql_order = "SELECT * FROM eventic_order WHERE id = :order_id";
                $params_order = ['order_id' => $event_order_id];
                $statement_order = $connection->prepare($sql_order);
                $statement_order->execute($params_order);
                $order_data = $statement_order->fetch();
                if (isset($order_data['reference'])) {
                    $references[] = $order_data['reference'];
                }
            }
        }

        if (empty($references)) {
            $this->addFlash('error', $translator->trans('No orders found for this event'));
            return $this->redirect($request->headers->get('referer'));
        }
        foreach ($references as $reference) {
            $order[] = $services->getOrders(array("reference" => $reference))->getQuery()->getOneOrNullResult();
        }
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $eventDateTicketReference = $request->query->get('event', 'all');

        return $this->render('Dashboard/Shared/SendTicket/index.html.twig', [
            'orders' => $order,
            'eventDateTicketReference' => $eventDateTicketReference,
            'link' => $link,
            'event_id' => $event

        ]);
    }


    /**
     * @Route("/mail-ticket/send/{reference?}", name="mail_server_test", methods="GET|POST")
     */

    public function mailServerTest(Request $request, $reference, AppServices $services, Swift_Mailer $mailer, Environment $templating, TranslatorInterface $translator, Connection $connection)
    {

        $order = $services->getOrders(array("reference" => $reference))->getQuery()->getOneOrNullResult();
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirectToRoute("dashboard_attendee_orders");
        }

        if ($request->getLocale() == "ar") {
            return $this->redirectToRoute("dashboard_tickets_print", ["reference" => $reference, "_locale" => "en"]);
        }
        foreach ($order->getOrderElements() as $single_order_element) {

            $sql_order_elem = "SELECT * FROM eventic_order_ticket WHERE orderelement_id = :orderelement_id";
            $params_order_elem = ['orderelement_id' => $single_order_element->getId()];
            $statement_order_elem = $connection->prepare($sql_order_elem);
            $statement_order_elem->execute($params_order_elem);
            $order_elems = $statement_order_elem->fetchall();

            foreach ($order_elems as $order_elem) {

                $even_id = $single_order_element->getEventticket()->getEventdate()->getEvent()->getId();
            
                // Get the 'id' parameter from the URL
                $id = $even_id;
                $ref_id = $order_elem['reference'];

                $sqlEvent = "SELECT * FROM eventic_event WHERE id = :id";
                $paramsEvent = ['id' => $id];
                $statementEvent = $connection->prepare($sqlEvent);
                $statementEvent->execute($paramsEvent);
                $one_event = $statementEvent->fetch();

                // if (!$one_event) {
                //     $this->addFlash('error', $translator->trans('The event can not be found'));
                //     return $this->redirect($request->headers->get('referer'));
                // }

                $sql = "SELECT * FROM eventic_order_ticket WHERE reference = :ref_id";
                $params = ['ref_id' => $ref_id];
                $statement = $connection->prepare($sql);
                $statement->execute($params);
                $order_eventic_ticket = $statement->fetch();

                // if (!$order_eventic_ticket) {
                //     $this->addFlash('error', $translator->trans('The order ticket can not be found'));
                //     return $this->redirect($request->headers->get('referer'));
                // }

                $orderelement_id = $order_eventic_ticket['orderelement_id'] ?? '';

                $sql1 = "SELECT * FROM eventic_order_element WHERE id = :orderelement_id";
                $params1 = ['orderelement_id' => $orderelement_id];
                $statement1 = $connection->prepare($sql1);
                $statement1->execute($params1);
                $order_element = $statement1->fetch();

                // if (!$order_element) {
                //     $this->addFlash('error', $translator->trans('The order element can not be found'));
                //     return $this->redirect($request->headers->get('referer'));
                // }

                $order_id = $order_element['order_id'] ?? '';

                $sql2 = "SELECT * FROM eventic_order WHERE id = :order_id";
                $params2 = ['order_id' => $order_id];
                $statement2 = $connection->prepare($sql2);
                $statement2->execute($params2);
                $order_eventic = $statement2->fetch();

                // if (!$order_eventic) {
                //     $this->addFlash('error', $translator->trans('The order can not be found'));
                //     return $this->redirect($request->headers->get('referer'));
                // }

                $user_id = $order_eventic['user_id'] ?? '';

                $sql3 = "SELECT * FROM eventic_user WHERE id = :user_id";
                $params3 = ['user_id' => $user_id];
                $statement3 = $connection->prepare($sql3);
                $statement3->execute($params3);
                $user = $statement3->fetch();

                if (!$user) {
                    $this->addFlash('error', $translator->trans('The user can not be found'));
                    return $this->redirect($request->headers->get('referer'));
                }

                $event_refe = $one_event['reference'] ?? '';
                $link = $_ENV['MAIN_DOMAIN'] . 'join_event_meeting/' . $event_refe .'/'. $user['slug'].'/'. $order->getReference();

                // $orders = $services->getOrders(array("reference" => $order['reference']))->getQuery()->getOneOrNullResult();
                // if (!$orders) {
                //     $this->addFlash('error', $translator->trans('The order can not be found'));
                //     return $this->redirect($request->headers->get('referer'));
                // }
                // $eventDateTicketReference = $request->query->get('event', 'all');

                $org_auth = $this->getUser();
                $org_name = $org_auth->getFirstname().' '.$org_auth->getLastname();

                // return $this->render('Dashboard/Shared/Order/confirmation-email.html.twig', ['order' => $orders, 'org_name' => $org_name]);

                $pdfOptions = new Options();
                $dompdf = new Dompdf($pdfOptions);

                if($one_event['tickettemplate'] == 1 || $one_event['tickettemplate'] == null ){
                    $html = $this->renderView('Dashboard/Shared/Order/ticket-pdf.html.twig', [
                        'order' => $order,
                        'eventDateTicketReference' => 'all',
                        'link' => $link,
                    ]);
                }else{
                    $html = $this->renderView('Dashboard/Shared/Order/ticket-pdf-2.html.twig', [
                        'order' => $order,
                        'eventDateTicketReference' => 'all',
                        'link' => $link,
                    ]);
                }

                $description = $this->getEventDescriptions($connection,$one_event['id']);

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $ticketsPdfFile = $dompdf->output();
                $emailTo = $user['email'];
                $email_subject_title = $user['firstname'] . ' ' . $user['lastname'] . ', te estamos esperando en ' . $single_order_element->getEventticket()->getEventdate()->getEvent()->getName();
                $email = (new \Swift_Message($email_subject_title))
                    ->setFrom($services->getSetting('no_reply_email'))
                    ->setTo($emailTo)
                    ->setBody(
                        $this->renderView('Dashboard/Shared/Order/confirmation-email.html.twig', ['order' => $order, 'org_name' => $org_name ,'description' => $description]),
                        'text/html'
                    )
                    ->attach(new \Swift_Attachment($ticketsPdfFile, $order->getReference() . "-" . $translator->trans("tickets") . '.pdf', 'application/pdf'));

                try {
                    $mailer->send($email);
                } catch (\Throwable $th) {
                    //throw $th;
                }

                
            }
        }


        $this->addFlash('success', $translator->trans('Invitation Sent'));
        return $this->redirect($request->headers->get('referer'));
    }

    public function getEventDescriptions($connection,$eventID){
        
        $sqlDesc = "SELECT name,description FROM eventic_event_translation WHERE translatable_id = :translatable_id";
        $paramsDesc = ['translatable_id' => $eventID];
        $statementDesc = $connection->prepare($sqlDesc);
        $statementDesc->execute($paramsDesc);
        $descriptions = $statementDesc->fetch();
        $description = $descriptions != null  && isset($descriptions['description']) ? $descriptions['description'] : null;
        return $description ;
    }


    public function sendTicketCsv(Request $request, AppServices $services, TranslatorInterface $translator, Connection $connection, Swift_Mailer $mailer, Environment $templating, $event = null)
    {
        $sql = "SELECT * FROM eventic_event WHERE id = :id";
        $params = ['id' => $event];
        $statement = $connection->prepare($sql);
        $statement->execute($params);
        $event_info = $statement->fetch();

        if (!$event_info) {
            $this->addFlash('error', $translator->trans('The event can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        // Events Descrioption & title get 
        $description = $this->getEventDescriptions($connection,$event);

        $ref_id = $event_info['reference'];

       


        $user_id = $event_info['organizer_id'];

        $sql2 = "SELECT * FROM eventic_user WHERE organizer_id = :id";
        $params2 = ['id' => $user_id];
        $statement2 = $connection->prepare($sql2);
        $statement2->execute($params2);
        $user_info = $statement2->fetch();

        if (!$user_info) {
            $this->addFlash('error', $translator->trans('The user information can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $sql3 = "SELECT * FROM event_mails WHERE event_ref_id = :ref_id";
        $params3 = ['ref_id' => $ref_id];
        $statement3 = $connection->prepare($sql3);
        $statement3->execute($params3);
        $eventMails = $statement3->fetchAll();

        if (empty($eventMails)) {
            $this->addFlash('error', $translator->trans('No emails found for this event'));
            return $this->redirect($request->headers->get('referer'));
        }

        $sql4 = "SELECT eventic_event_date.*,eventic_event_date.id as event_date_id FROM eventic_event_date WHERE event_id = :id";
        $params4 = ['id' => $event];
        $statement4 = $connection->prepare($sql4);
        $statement4->execute($params4);
        $event_date = $statement4->fetch();

        $event_date_id = $event_date['event_date_id'];


        if (!$event_date) {
            $this->addFlash('error', $translator->trans('The event date can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $sql6 = "SELECT * FROM eventic_event_date_ticket WHERE eventdate_id = :id";
        $params6 = ['id' => $event_date_id];
        $statement6 = $connection->prepare($sql6);
        $statement6->execute($params6);
        $event_ticket = $statement6->fetch();

        if (!$event_ticket) {
            $this->addFlash('error', $translator->trans('The event ticket can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $currentDateTime = new \DateTime();
        $timezone = new \DateTimeZone('America/New_York');
        $currentDateTime->setTimezone($timezone);
        $orderDateTime = $currentDateTime->format('D d M Y, h:i A T');


        $ticketreference = $event_ticket['reference'];
        $em = $this->getDoctrine()->getManager();
        $eventticket = $em->getRepository("App\Entity\EventTicket")->findOneByReference($ticketreference);

        $org_auth = $this->getUser();
        $org_name = $org_auth->getFirstname().' '.$org_auth->getLastname();

        // For Create User As a attendee
        $mail_send_count = 1;
        foreach ($eventMails as $eventMail) {
            if ($eventMail['status'] == 0) {
                $sql7 = "SELECT slug FROM eventic_user WHERE email = :email";
                $params7 = ['email' => $eventMail['email']];
                $statement7 = $connection->prepare($sql7);
                $statement7->execute($params7);
                $eventic_user = $statement7->fetch();
                $user_name = strtolower($eventMail['name'] . $eventMail['surname']) . strtotime('now');
                $user_link_slug = false;

                if (!empty($eventic_user)) {
                    $user_slug = $eventic_user['slug'];

                } else {
                    $user = $this->userManager->createUser();
                    $user->setEnabled(true);
                    $user->setFirstname($eventMail['name']);
                    $user->setLastname($eventMail['surname']);
                    $user->setUsername($user_name);
                    $user->setUsernameCanonical($user_name);
                    $user->setEmail($eventMail['email']);
                    $user->setEmailCanonical($eventMail['email']);
                    $user->setPlainPassword('12345678');
                    $user->setSlug($eventMail['name'] . $eventMail['surname'] . strtotime('now'));
                    $user->addRole('ROLE_ATTENDEE');
                    $csvEnabled = true;
                    $this->userManager->updateUser($user);

                    $user_slug = $user->getSlug();

                    $user_link_slug = $user_slug;
                }

                $user = $services->getUsers(array("slug" => $user_slug, "enabled" => "all"))->getQuery()->getOneOrNullResult();
                if ($user == null) continue;
                // Order table data pushed by user
                $order = new Order();
                $order->setUser($user);
                $order->setReference($services->generateReference(15));
                $order->setStatus(0);
                // Order element inserted
                $orderelement = new OrderElement();
                $orderelement->setOrder($order);
                $orderelement->setEventticket($eventticket);
                // $orderelement->setUnitprice(0.00);
                $orderelement->setUnitprice($eventticket->getSalePrice());
                $orderelement->setQuantity(1);
                $order->addOrderelement($orderelement);

                if ($user != null) {
                    if ($user->hasRole("ROLE_ATTENDEE")) {
                        $order->setTicketFee($services->getSetting("ticket_fee_online"));
                        $order->setTicketPricePercentageCut($services->getSetting("online_ticket_price_percentage_cut"));
        
                    } else if ($user->hasRole("ROLE_POINTOFSALE")) {
                        $order->setTicketFee($services->getSetting("ticket_fee_pos"));
                        $order->setTicketPricePercentageCut($services->getSetting("pos_ticket_price_percentage_cut"));
                     
                    }
                }
                
                $order->setCurrencyCcy($services->getSetting("currency_ccy"));
                $order->setCurrencySymbol($services->getSetting("currency_symbol"));

                $order->setStatus(1);
                $paymentGateway = $em->getRepository("App\Entity\PaymentGateway")->findOneBySlug("point-of-sale");
                $gatewayFactoryName = "offline";
                $order->setPaymentGateway($paymentGateway);
                $em->persist($order);
                foreach ($order->getOrderelements() as $orderElement) {
                    $ticket = new OrderTicket();
                    $ticket->setOrderElement($orderElement);
                    $ticket->setReference($services->generateReference(20));
                    $ticket->setScanned(false);
                    $em->persist($ticket);
                   
                }
                $storage = $this->get('payum')->getStorage('App\Entity\Payment');
                $payment = $storage->create();
                $payment->setOrder($order);
                $payment->setNumber($services->generateReference(20));
                $payment->setCurrencyCode($services->getSetting("currency_ccy"));
                $payment->setTotalAmount("0.0"); // 1.23 USD = 123
                $payment->setDescription($translator->trans("Payment of tickets purchased on %website_name%", array('%website_name%' => $services->getSetting("website_name"))));
                $payment->setClientId($user->getId());
                $payment->setFirstname($eventMail['name']);
                $payment->setLastname($eventMail['surname']);
                $payment->setClientEmail($eventMail['email']);
                $payment->setState($eventMail['email']);
                $payment->setCity($eventMail['city']);
                $payment->setPostalcode('213344');
                $payment->setStreet($eventMail['address']);
                $payment->setStreet2($eventMail['address']);

                $storage->update($payment);
                $order->setPayment($payment);

               
                $em->flush();
                $services->emptyCart($user);



                $orderReference = $order->getReference();

                $em->refresh($order);
                $em->refresh($ticket);
                $em->refresh($orderElement);

                $pdfOptions = new Options();
                $dompdf = new Dompdf($pdfOptions);

                $link = $_ENV['MAIN_DOMAIN'] . 'join_event_meeting/' . $ref_id.'/'. $user->getSlug() . '/' . $order->getReference();

                // if ($user_link_slug != false) {
                //     $link = $link . '?ud=' . $user_link_slug;
                // }

                if($event_info['tickettemplate'] == 1 || $event_info['tickettemplate'] == null ){
                    $html = $this->renderView('Dashboard/Shared/Order/ticket-pdf.html.twig', [
                        'order' => $order,
                        'eventDateTicketReference' => 'all',
                        'link' => $link,
                    ]);
                }else{
                    $html = $this->renderView('Dashboard/Shared/Order/ticket-pdf-2.html.twig', [
                        'order' => $order,
                        'eventDateTicketReference' => 'all',
                        'link' => $link,
                    ]);
                }

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $ticketsPdfFile = $dompdf->output();

                $emailTo = $eventMail['email'];
                $email_subject_title = $user->getFirstname() . ' ' . $user->getLastname() . ', te estamos esperando en ' . $order->getOrderelements()[0]->getEventticket()->getEventdate()->getEvent()->getName();
                $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
                // check email is valid or not 
                if ((preg_match($pattern, $emailTo))) {
                    $email = (new \Swift_Message($email_subject_title))
                        ->setFrom($services->getSetting('no_reply_email'))
                        ->setTo($emailTo)
                        ->setBody(
                            $this->renderView('Dashboard/Shared/Order/confirmation-email.html.twig', ['order' => $order, 'org_name' => $org_name ,'description' => $description]),
                            'text/html'
                        )
                        ->attach(new \Swift_Attachment($ticketsPdfFile, $order->getReference() . "-" . $translator->trans("tickets") . '.pdf', 'application/pdf'));

                    $mailer->send($email);

                    $update_order_sql = "UPDATE eventic_order SET ticket_send_status = :ticket_send_status, event_ref_id = :event_ref_id WHERE reference = :reference";
                    $update_order_params = [
                        'ticket_send_status' => 1,
                        'event_ref_id'       => $ref_id,
                        'reference'          => $orderReference,
                    ];
                    $statement_order = $connection->prepare($update_order_sql);
                    $statement_order->execute($update_order_params);

                }

                // email status update after mail send
                $updateSql = "UPDATE event_mails SET status = :status, attendee_id = :attendee_id WHERE email = :email";
                $updateParams = [
                    'status'       => 1,
                    'attendee_id'  => $user->getId(),
                    'email' => $eventMail['email'],
                ];
                $updateStatement = $connection->prepare($updateSql);
                $updateStatement->execute($updateParams);

                if($mail_send_count >= 20){
                    break;
                }
                $mail_send_count++;

            }
        }
        $this->addFlash('success', $translator->trans('Invitation Sent'));
        return $this->redirect($request->headers->get('referer'));
    }



    public function send_ticket_for_whatsapp(Request $request, AppServices $services, TranslatorInterface $translator, Connection $connection, Swift_Mailer $mailer, Environment $templating, $event = null)
    {
        $sql = "SELECT * FROM eventic_event WHERE id = :id";
        $params = ['id' => $event];
        $statement = $connection->prepare($sql);
        $statement->execute($params);
        $event_info = $statement->fetch();



        if (!$event_info) {
            $this->addFlash('error', $translator->trans('The event can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $ref_id = $event_info['reference'];

        $link = $_ENV['MAIN_DOMAIN'] . 'join_event_meeting/' . $ref_id;

        if (!$link) {
            $this->addFlash('error', $translator->trans('The event Link can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $user_id = $event_info['organizer_id'];

        $sql2 = "SELECT * FROM eventic_user WHERE organizer_id = :id";
        $params2 = ['id' => $user_id];
        $statement2 = $connection->prepare($sql2);
        $statement2->execute($params2);
        $user_info = $statement2->fetch();

        if (!$user_info) {
            $this->addFlash('error', $translator->trans('The user information can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $sql3 = "SELECT * FROM event_mails WHERE event_ref_id = :ref_id";
        $params3 = ['ref_id' => $ref_id];
        $statement3 = $connection->prepare($sql3);
        $statement3->execute($params3);
        $eventMails = $statement3->fetchAll();

        if (empty($eventMails)) {
            $this->addFlash('error', $translator->trans('No emails found for this event'));
            return $this->redirect($request->headers->get('referer'));
        }

        $sql4 = "SELECT * FROM eventic_event_date WHERE event_id = :id";
        $params4 = ['id' => $event];
        $statement4 = $connection->prepare($sql4);
        $statement4->execute($params4);
        $event_date = $statement4->fetch();
        $event_date_id = $event_date['id'];

        if (!$event_date) {
            $this->addFlash('error', $translator->trans('The event date can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $sql6 = "SELECT * FROM eventic_event_date_ticket WHERE eventdate_id = :id";
        $params6 = ['id' => $event_date_id];
        $statement6 = $connection->prepare($sql6);
        $statement6->execute($params6);
        $event_ticket = $statement6->fetch();

        if (!$event_ticket) {
            $this->addFlash('error', $translator->trans('The event ticket can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $currentDateTime = new \DateTime();
        $timezone = new \DateTimeZone('America/New_York');
        $currentDateTime->setTimezone($timezone);
        $orderDateTime = $currentDateTime->format('D d M Y, h:i A T');

        foreach ($eventMails as $eventMail) {
           
            $templateObject = [
                'id' => '19660948-5440-48e1-9073-0ff8b575f32a',
                'params' => [$eventMail['name'], "15643", $event_date['startdate'], "Online", $link]
            ];
            $templateJson = json_encode($templateObject);
            $postData = http_build_query([
                'source' => '573022177303',
                // 'destination' => '8801768828992',
                'destination' => $eventMail['phone_number'],
                'template' => $templateJson
            ]);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.gupshup.io/sm/api/v1/template/msg',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded',
                    'apikey: cky6px6gylnajx0epf1xafnxqluh8lyh',
                    'Authorization: Bearer sk_4b777aa004e7403d86c481b3d2c15f49'
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        }
        return $this->redirect($request->headers->get('referer'));
    }


    public function csv_users_list($event_ref, EntityManagerInterface $entityManager){

        $sql = "SELECT * FROM event_invalid_mails  WHERE event_ref_id = :event_ref_id";
        $statement = $entityManager->getConnection()->prepare($sql);
        $statement->execute(['event_ref_id' => $event_ref]);
        $event_invalid_mails = $statement->fetchAll();

        $sql2 = "SELECT * FROM event_mails  WHERE event_ref_id = :event_ref_id";
        $statement2 = $entityManager->getConnection()->prepare($sql2);
        $statement2->execute(['event_ref_id' => $event_ref]);
        $event_valid_mails = $statement2->fetchAll();

        $sql3 = "SELECT * FROM eventic_event WHERE reference = :reference";
        $statement3 = $entityManager->getConnection()->prepare($sql3);
        $statement3->execute(['reference' => $event_ref]);
        $event = $statement3->fetch();

        return $this->render('Dashboard/Shared/SendTicket/csv_users_list.html.twig', array(
            "event_invalid_mails" => $event_invalid_mails,
            "event_valid_mails"   => $event_valid_mails,
            "event_id"            => $event['id'],
        ));
    }
    public function attending_users_list($event_ref,$join, AppServices $services, EntityManagerInterface $entityManager, Request $request, TranslatorInterface $translator, Connection $connection){

        $sqlEvent = "SELECT * FROM eventic_event WHERE reference = :reference";
        $paramsEvent = ['reference' => $event_ref];
        $statementEvent = $connection->prepare($sqlEvent);
        $statementEvent->execute($paramsEvent);
        $one_event = $statementEvent->fetch();

        if (!$one_event) {
            $this->addFlash('error', $translator->trans('The event can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $link = $_ENV['MAIN_DOMAIN'] . 'join_event_meeting/' . $one_event['reference'];

        if (!$link) {
            $this->addFlash('error', $translator->trans('The event Link can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $sql = "SELECT * FROM eventic_event_date WHERE event_id = :id";
        $params = ['id' => $one_event['id']];
        $statement = $connection->prepare($sql);
        $statement->execute($params);
        $event_date = $statement->fetch();

        if (!$event_date) {
            $this->addFlash('error', $translator->trans('The event date can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }
        $event_date_id = $event_date['id'];

        $sql2 = "SELECT * FROM eventic_event_date_ticket WHERE eventdate_id = :id";
        $params2 = ['id' => $event_date_id];
        $statement2 = $connection->prepare($sql2);
        $statement2->execute($params2);
        $event_date_ticket = $statement2->fetch();

        if (!$event_date_ticket) {
            $this->addFlash('error', $translator->trans('The event date ticket can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $event_date_ticket_id = $event_date_ticket['id'];

        $sql3 = "SELECT * FROM eventic_order_element WHERE eventticket_id = :id";
        $params3 = ['id' => $event_date_ticket_id];
        $statement3 = $connection->prepare($sql3);
        $statement3->execute($params3);
        $event_orders = $statement3->fetchAll();

        if (!empty($event_orders)) {
            foreach ($event_orders as $event_order) {
                $event_order_id = $event_order['order_id'];

                if ($join == 'no') {
                    $sql_order = "SELECT * FROM eventic_order WHERE id = :order_id AND join_meeting IS NULL";
                } else if ($join == 'all') {
                    $sql_order = "SELECT * FROM eventic_order WHERE id = :order_id";
                } else {
                    $sql_order = "SELECT * FROM eventic_order WHERE id = :order_id AND join_meeting = 1";
                }

                $params_order = ['order_id' => $event_order_id];
                $statement_order = $connection->prepare($sql_order);
                $statement_order->execute($params_order);
                $order_data = $statement_order->fetch();
                if (isset($order_data['reference'])) {
                    $references[] = $order_data['reference'];
                }
            }
        }

        if (empty($references)) {
            $this->addFlash('error', $translator->trans('No attendee found for join in this event'));
            return $this->redirect($request->headers->get('referer'));
        }
        foreach ($references as $reference) {
            $order[] = $services->getOrders(array("reference" => $reference))->getQuery()->getOneOrNullResult();
        }
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $eventDateTicketReference = $request->query->get('event', 'all');

        $sqlDesc = "SELECT name,description FROM eventic_event_translation WHERE translatable_id = :translatable_id";
        $paramsDesc = ['translatable_id' => $one_event['id']];
        $statementDesc = $connection->prepare($sqlDesc);
        $statementDesc->execute($paramsDesc);
        $description = $statementDesc->fetch();

        $event_time = $event_date['startdate']. ' ' .$event_date['enddate'];

        return $this->render('Dashboard/Shared/SendTicket/attending_users_list.html.twig', [
            'orders' => $order,
            'eventDateTicketReference' => $eventDateTicketReference,
            'link' => $link,
            'event_id' => $one_event['id'],
            "event_ref_id"    => $event_ref,
            'join'            => $join,
            'event_name'      => $description['name'],
            'event_time'      => $event_time,
        ]);
    }
    
    public function attending_users_list_download(Request $request, $event_ref, $join, AppServices $services, EntityManagerInterface $entityManager,TranslatorInterface $translator, Connection $connection){


        $sqlEvent = "
            SELECT e.*, t.name AS translation_name, t.description AS translation_description ,t.slug,edate.startdate,edate.enddate,edate.online
            FROM eventic_event e 
            LEFT JOIN eventic_event_translation t ON e.id = t.translatable_id 
            LEFT JOIN eventic_event_date edate ON e.id = edate.event_id 
            WHERE e.reference = :reference
        ";
        $paramsEvent = ['reference' => $event_ref];
        $statementEvent = $connection->prepare($sqlEvent);
        $statementEvent->execute($paramsEvent);
        $eventDetails = $statementEvent->fetch();

        $orderlists = $services->getOrders(array("event" => $eventDetails['slug']))->getQuery()->getResult();

        $pdfData = array();
        if (count($orderlists)) {
            foreach ($orderlists as $orderlist) {
                $scanner = "SELECT reference,scanned FROM eventic_order_ticket WHERE orderelement_id = :id And scanned = :scanned";
                $params = ['id' => $orderlist->getOrderElements()[0]->getId(), 'scanned' => 1];
                $statement = $connection->prepare($scanner);
                $statement->execute($params);
                $scannerReport = $statement->fetch();

                if ($scannerReport) {
                    array_push($pdfData, [
                        'name' => $orderlist->getUser()->getFirstName() . ' ' . $orderlist->getUser()->getLastName(),
                        'email' => $orderlist->getUser()->getEmail(),
                        'type' => $eventDetails['online'] ? 'Online' : 'Physical',
                        'isJoin' => $scannerReport['scanned'] ? "Joint" : "Not Joint"
                    ]);
                }
            }
        }

        $pdfOptions = new Options();
        $dompdf = new Dompdf($pdfOptions);
        $html = $this->renderView('Dashboard/Shared/SendTicket/attending_users_list_download.html.twig', [
            'order_users' => $pdfData,
            'event_name'      => $eventDetails['translation_name'],
            'event_time'      => $eventDetails['startdate'] ." To ". $eventDetails['enddate'],
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $ticketsPdfFile = $dompdf->output();
        $dompdf->stream();




        // return $this->render('Dashboard/Shared/SendTicket/attending_users_list.html.twig', [
        //     'orders' => $order,
        //     'eventDateTicketReference' => $eventDateTicketReference,
        //     'link' => $link,
        //     'event_id' => $one_event['id'],
        //     "event_ref_id"    => $event_ref,
        //     'join'            => $join,
        //     'event_name'      => $description['name'],
        //     'event_time'      => $event_time,
        // ]);

        // return $this->redirect($request->headers->get('referer'));
        dd(0);
        $request = $this->requestStack->getCurrentRequest();
        $referer = $request->headers->get('referer');

        return new RedirectResponse($referer);


    }



}
