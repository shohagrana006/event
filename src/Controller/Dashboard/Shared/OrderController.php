<?php

namespace App\Controller\Dashboard\Shared;

use Exception;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Payment;
use App\Form\CheckoutType;
use App\Service\AppServices;
use Doctrine\DBAL\Connection;
use App\Entity\TicketReservation;
use Symfony\Component\Asset\Packages;
use Payum\Core\Request\GetHumanStatus;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Query\QueryBuilder;

use FOS\UserBundle\Model\UserManagerInterface;
use Swift_Mailer;

class OrderController extends Controller
{


    private $userManager;

    public function __construct(UserManagerInterface $userManager)
    {
        $this->userManager = $userManager;
    }


    /**
     * @Route("/attendee/checkout", name="dashboard_attendee_checkout")
     * @Route("/pointofsale/checkout", name="dashboard_pointofsale_checkout")
     */
    public function checkout(Request $request, TranslatorInterface $translator, AppServices $services, RouterInterface $router,EntityManagerInterface $entityManager, Swift_Mailer $mailer)
    {
        $em = $this->getDoctrine()->getManager();
        $responseData = [];

        if ($this->isGranted("ROLE_ATTENDEE")) {
            $paymentGateways = $services->getPaymentGateways([])->getQuery()->getResult();
            $form = $this->createForm(CheckoutType::class, null, ['validation_groups' => 'attendee']);
        } else {
            $form = $this->createForm(CheckoutType::class, null, ['validation_groups' => 'pos']);
        }
        $form->handleRequest($request);
        $order = null;
        if ($form->isSubmitted()) {
            $order = $services->getOrders(['status' => 0, 'reference' => $form->getData()['orderReference']])->getQuery()->getOneOrNullResult();
            // dd($order->getUser()->getUsername());
            if ($form->isValid()) {
                if(!$order) {
                    $message = 'The order cannot be found';
                    $this->addFlash('error', $translator->trans($message));
                    return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 404);
                }

                if (!count($order->getOrderelements())) {
                    $message = 'Your order is empty';
                    $this->addFlash('error', $translator->trans($message));
                    return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                }

                foreach ($order->getOrderelements() as $orderelement) {
                    if (!$orderelement->getEventticket()->isOnSale()) {
                        $message = 'Your order has been automatically canceled because one or more events are no longer on sale';
                        $services->handleCanceledPayment($order->getReference(), $translator->trans($message));
                        $this->addFlash('notice', $translator->trans($message));
                        return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                    }

                    if ($orderelement->getEventticket()->getTicketsLeftCount() > 0 && $orderelement->getQuantity() > $orderelement->getEventticket()->getTicketsLeftCount()) {
                        $message = 'Your order has been automatically canceled because one or more event\'s quotas has changed';
                        $services->handleCanceledPayment($order->getReference(), $translator->trans($message));
                        $this->addFlash('notice', $translator->trans($message));
                        return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                    }

                    foreach ($orderelement->getTicketsReservations() as $ticketReservation) {
                        if ($ticketReservation->isExpired()) {
                            $message = 'Your order has been automatically canceled because your ticket reservations have been released';
                            $services->handleCanceledPayment($order->getReference(), $translator->trans($message));
                            $this->addFlash('notice', $translator->trans($message));
                            return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                        }
                    }


                }
      

                $storage = $this->get('payum')->getStorage('App\Entity\Payment');

                $orderTotalAmount = $order->getOrderElementsPriceSum(true);
                if ($orderTotalAmount == 0) {
                    $paymentGateway = $em->getRepository("App\Entity\PaymentGateway")->findOneBySlug("free");
                    $gatewayFactoryName = "offline";
                } elseif ($this->isGranted("ROLE_ATTENDEE")) {
                    if (count($paymentGateways) == 0) {
                        $message = 'No payment gateways are currently enabled';
                        $this->addFlash('error', $translator->trans($message));
                        return $this->handleJsonOrRedirect($request, 'dashboard_attendee_cart', ['success' => false, 'message' => $message], 400);
                    }
                    $gatewayFactoryName = $request->request->get('payment_gateway');
                    $paymentGateway = $services->getPaymentGateways(['gatewayFactoryName' => $gatewayFactoryName])->getQuery()->getOneOrNullResult();
                } else {
                    $paymentGateway = $em->getRepository("App\Entity\PaymentGateway")->findOneBySlug("point-of-sale");
                    $gatewayFactoryName = "offline";
                }

                if (!$paymentGateway) {
                    $message = 'The payment gateway cannot be found';
                    $this->addFlash('error', $translator->trans($message));
                    return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 404);
                }
                if (!$order->getPaymentGateway()) {
                    $order->setPaymentGateway($paymentGateway);
                    $em->persist($order);
                    $em->flush(); // Ensure order is updated in the database
                }

                $payment = $storage->create();
                if (!$order->getPayment()) {
                    $payment->setOrder($order);
                }

                $payment->setNumber($services->generateReference(20));
                $payment->setCurrencyCode($services->getSetting("currency_ccy"));
                $payment->setStatus("pending");
                $payment->setTotalAmount($orderTotalAmount * 100);
                $payment->setDescription($translator->trans("Payment of tickets purchased on %website_name%", ['%website_name%' => $services->getSetting("website_name")]));
                $payment->setClientId($this->getUser()->getId());

                if ($form->getData()['firstname']) {
                    $payment->setFirstname($form->getData()['firstname']);
                }
                if ($form->getData()['lastname']) {
                    $payment->setLastname($form->getData()['lastname']);
                }
                if ($this->isGranted("ROLE_ATTENDEE")) {
                    $payment->setStatus($form->getData()['status']);
                    $payment->setClientEmail($form->getData()['email']);
                    $payment->setCountry($form->getData()['country']);
                    $payment->setState($form->getData()['state']);
                    $payment->setCity($form->getData()['city']);
                    $payment->setPostalcode($form->getData()['postalcode']);
                    $payment->setStreet($form->getData()['street']);
                    $payment->setStreet2($form->getData()['street2']);
                    $payment->setNameOnTicket($form->getData()['name_on_ticket']??$order->getUser()->getUsername());
                }

                if (!$order->getPayment()) {
                    if (!$em->contains($payment)) {
                        $em->persist($payment);
                    }
                    $em->flush();
                    $storage->update($payment);
                    $order->setPayment($payment);
                    $em->flush();
                }else{
                    $paymentId = $order->getPayment()->getId();
                    $paymentUp = $paymentId ? $em->getRepository(Payment::class)->find($paymentId) : null;
                    if ($this->isGranted("ROLE_ATTENDEE")) {
                        $paymentUp->setStatus($form->getData()['status']);
                        $paymentUp->setClientEmail($form->getData()['email']);
                        $paymentUp->setCountry($form->getData()['country']);
                        $paymentUp->setState($form->getData()['state']);
                        $paymentUp->setCity($form->getData()['city']);
                        $paymentUp->setPostalcode($form->getData()['postalcode']);
                        $paymentUp->setStreet($form->getData()['street']);
                        $paymentUp->setStreet2($form->getData()['street2']);
                        $paymentUp->setNameOnTicket($form->getData()['name_on_ticket']??$order->getUser()->getUsername());
                    }

                    // Persist the paymentUp entity if it's new
                    if (!$em->contains($paymentUp)) {
                        $em->persist($paymentUp);
                    }
                    $em->flush();

                    $payment->setId($order->getPayment()->getId());
                }



                // buy ticket user info
                $this->buyTicketUserInfo($request->request->all()['json'], $entityManager, $order, $services, $mailer, $translator);

                if ($this->isGranted("ROLE_ATTENDEE")) {
                    if ($request->request->get('payment_gateway') == "offline") {
                        $this->addFlash('success', $translator->trans('Your order has been successfully placed, please proceed to the payment as explained in the instructions'));
                        return $this->redirectToRoute("dashboard_attendee_order_details", ['reference' => $payment->getOrder()->getReference()]);
                    } else {
                        $captureToken = $this->get('payum')->getTokenFactory()->createCaptureToken(
                            $gatewayFactoryName,
                            $payment,
                            'dashboard_attendee_checkout_done'
                        );
                        $responseData['redirect_url'] = $captureToken->getTargetUrl();
                    }
                } else {
                    $captureToken = $this->get('payum')->getTokenFactory()->createCaptureToken(
                        $gatewayFactoryName,
                        $payment,
                        'dashboard_pointofsale_checkout_done'
                    );
                    $responseData['redirect_url'] = $captureToken->getTargetUrl();
                }

                if ($request->isXmlHttpRequest()) {
                    $responseData['success'] = true;
                    return $this->json($responseData);
                } else {
                    return $this->redirect($captureToken->getTargetUrl());
                }
            } else {
                $responseData['success'] = false;
                foreach ($form->getErrors(true) as $error) {
                    $responseData['errors'][$error->getOrigin()->getName()][] = $error->getMessage();
                }

                if ($request->isXmlHttpRequest()) {
                    return $this->json($responseData, 400);
                } else {
                    $this->addFlash('error', $translator->trans('The form contains invalid data'));
                    if ($this->isGranted("ROLE_ATTENDEE")) {
                        return $this->render('Dashboard/Attendee/Order/checkout.html.twig', [
                            'form' => $form->createView(),
                            'paymentGateways' => $paymentGateways,
                            'order' => $order,
                            'payment' => $order->getPayment()

                        ]);
                    } else {
                        return $this->render('Dashboard/PointOfSale/Order/checkout.html.twig', [
                            'form' => $form->createView(),
                            'order' => $order
                        ]);
                    }
                }
            }
        } else {
            if (!$request->query->get('orderReference')) {
                $referer = $request->headers->get('referer');
                if (!\is_string($referer) || !$referer) {
                    $message = 'You must review your cart before proceeding to checkout';
                    $this->addFlash('info', $translator->trans($message));
                    return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                }
                if ($this->isGranted("ROLE_ATTENDEE")) {
                    if ($router->match(Request::create($referer)->getPathInfo())['_route'] != "dashboard_attendee_cart") {
                        $message = 'You must review your cart before proceeding to checkout';
                        $this->addFlash('info', $translator->trans($message));
                        return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                    }
                }


                if (!count($this->getUser()->getCartelements())) {
                    $message = 'Your cart is empty';
                    $this->addFlash('error', $translator->trans($message));
                    return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                }

                foreach ($this->getUser()->getCartelements() as $cartelement) {
                    if (!$cartelement->getEventticket()->isOnSale()) {
                        $em->remove($cartelement);
                        $em->flush();
                        $message = 'Your cart has been automatically updated because one or more events are no longer on sale';
                        $this->addFlash('notice', $translator->trans($message));
                        return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                    }
                    if ($cartelement->getEventticket()->getTicketsLeftCount() > 0 && $cartelement->getQuantity() > $cartelement->getEventticket()->getTicketsLeftCount()) {
                        $cartelement->setQuantity($cartelement->getEventticket()->getTicketsLeftCount());
                        $em->persist($cartelement);
                        $em->flush();
                        $message = 'Your cart has been automatically updated because one or more event\'s quotas has changed';
                        $this->addFlash('notice', $translator->trans($message));
                        return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 400);
                    }
                }

                if (count($this->getUser()->getTicketreservations())) {
                    foreach ($this->getUser()->getTicketreservations() as $ticketreservation) {
                        $em->remove($ticketreservation);
                    }
                    $em->flush();
                }

                $order = $services->transformCartIntoOrder($this->getUser());
                if (!$order) {
                    $message = 'The order cannot be found';
                    $this->addFlash('error', $translator->trans($message));
                    return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 404);
                }
                $em->persist($order);
                $em->flush();
                $services->emptyCart($this->getUser());

                foreach ($order->getOrderelements() as $orderElement) {
                    $ticketreservation = new TicketReservation();
                    $ticketreservation->setEventticket($orderElement->getEventticket());
                    $ticketreservation->setUser($this->getUser());
                    $ticketreservation->setOrderelement($orderElement);
                    $ticketreservation->setQuantity($orderElement->getQuantity());
                    $expiresAt = new \DateTime;
                    $ticketreservation->setExpiresAt($expiresAt->add(new \DateInterval('PT' . $services->getSetting("checkout_timeleft") . 'S')));
                    $orderElement->addTicketsReservation($ticketreservation);
                    $em->persist($ticketreservation);
                    $em->flush();
                }
            } else {
                $order = $services->getOrders(['status' => 0, 'reference' => $request->query->get('orderReference')])->getQuery()->getOneOrNullResult();

                if (!$order) {
                    $message = 'The order cannot be found';
                    $this->addFlash('error', $translator->trans($message));
                    return $this->handleJsonOrRedirect($request, 'dashboard_index', ['success' => false, 'message' => $message], 404);
                }
            }
        }
        if ($this->isGranted("ROLE_ATTENDEE")) {
            if ($request->isXmlHttpRequest()) {
                $responseData['form'] = $this->renderView('Dashboard/Attendee/Order/checkout.html.twig', [
                    'form' => $form->createView(),
                    'paymentGateways' => $paymentGateways,
                    'order' => $order
                ]);
                $responseData['success'] = true;
                return $this->json($responseData);
            } else {
                return $this->render('Dashboard/Attendee/Order/checkout.html.twig', [
                    'form' => $form->createView(),
                    'paymentGateways' => $paymentGateways,
                    'order' => $order,
                    'payment' => $order->getPayment()
                ]);
            }
        } else {
            if ($request->isXmlHttpRequest()) {
                $responseData['form'] = $this->renderView('Dashboard/PointOfSale/Order/checkout.html.twig', [
                    'form' => $form->createView(),
                    'order' => $order
                ]);
                $responseData['success'] = true;
                return $this->json($responseData);
            } else {
                return $this->render('Dashboard/PointOfSale/Order/checkout.html.twig', [
                    'form' => $form->createView(),
                    'order' => $order
                ]);
            }
        }
    }



    protected function buyTicketUserInfo($user_info, $entityManager, $order, $services, $mailer, $translator)
    {
        $output = [];
        foreach ($user_info as $key => $event) {
            $count = isset($event['guest_name']) ? count($event['guest_name']) : 0;
            for ($i = 0; $i < $count; $i++) {
                $output[] = [
                    "guest_name" => $event['guest_name'][$i] ?? null,
                    "guest_last_name" => $event['guest_last_name'][$i] ?? null,
                    "guest_telephone" => $event['guest_telephone'][$i] ?? null,
                    "guest_email" => $event['guest_email'][$i] ?? null,
                    "guest_country" => $event['guest_country'][$i] ?? null,
                    "guest_info_event_id" => $event['guest_info_event_id'] ?? null,
                ];
            }

            $jsonOutput = json_encode($output);
            $sql = "UPDATE eventic_order_element SET event_id = :event_id, buy_user_info = :buy_user_info WHERE id = :id";
            $params = [
                'id' => $event['guest_order_element_id'],
                'event_id' => $event['guest_info_event_id'],
                'buy_user_info' => $jsonOutput,
            ];
            $statement = $entityManager->getConnection()->prepare($sql);
            $statement->execute($params);
        }

        foreach ($output as $value) {
            $name = $value['guest_name'] ?? null;
            $last_name = $value['guest_last_name'] ?? null;
            $telephone = $value['guest_telephone'] ?? null;
            $email = $value['guest_email'] ?? null;
            $country = $value['guest_country'] ?? null;

            if ($email != null) {
                // Check if the email exists in the user table
                $sqlCheckEmail = "SELECT * FROM eventic_user WHERE email = :email";
                $check_params = ['email' => $email];
                $statement = $entityManager->getConnection()->prepare($sqlCheckEmail);
                $statement->execute($check_params);
                $user_exist = $statement->fetch();

                if ($user_exist == false) {
                    // user registration
                    $parts = explode("@", $email);
                    $username = $parts[0];
                    $user_name = $username . strtotime('now');
                    $user = $this->userManager->createUser();
                    $user->setEnabled(true);
                    $user->setFirstname($name);
                    $user->setLastname($last_name);
                    $user->setUsername($user_name);
                    $user->setUsernameCanonical($user_name);
                    $user->setEmail($email);
                    $user->setEmailCanonical($email);
                    $user->setPlainPassword('12345678');
                    $user->setSlug($name . $last_name . strtotime('now'));
                    $user->addRole('ROLE_ATTENDEE');
                    $this->userManager->updateUser($user);
                    $user_slug = $user->getSlug();
                    $userFirstName = $user->getFirstname();
                    $userLastName = $user->getLastname();
                } else {
                    $user = $user_exist;
                    $user_slug = $user['slug'];
                    $userFirstName = $user['firstname'];
                    $userLastName = $user['lastname'];
                }

                $get_event = "SELECT * FROM eventic_event WHERE id = :id";
                $event_params = ['id' => $value['guest_info_event_id']];
                $event_statement = $entityManager->getConnection()->prepare($get_event);
                $event_statement->execute($event_params);
                $event_info = $event_statement->fetch();

                $get_event1 = "SELECT * FROM eventic_organizer WHERE id = :org_id";
                $event_params1 = ['org_id' => $event_info['organizer_id']];
                $event_statement1 = $entityManager->getConnection()->prepare($get_event1);
                $event_statement1->execute($event_params1);
                $organizer = $event_statement1->fetch();

                $get_user = "SELECT * FROM eventic_user WHERE id = :user_id";
                $user_params = ['user_id' => $organizer['user_id']];
                $user_statement = $entityManager->getConnection()->prepare($get_user);
                $user_statement->execute($user_params);
                $orgName = $user_statement->fetch();

                $org_name = $orgName['firstname'] . ' ' . $orgName['lastname'];

                $ref_id = $event_info['reference'];

                $sqlDesc = "SELECT name, description FROM eventic_event_translation WHERE translatable_id = :translatable_id";
                $paramsDesc = ['translatable_id' => $value['guest_info_event_id']];
                $statementDesc = $entityManager->getConnection()->prepare($sqlDesc);
                $statementDesc->execute($paramsDesc);
                $descriptions = $statementDesc->fetch();

                $description = $descriptions != null && isset($descriptions['description']) ? $descriptions['description'] : null;

                $pdfOptions = new Options();
                $dompdf = new Dompdf($pdfOptions);
                $user_ticket_name = $name ?? ''. ' ' . $last_name ?? '';
                $link = $_ENV['MAIN_DOMAIN'] . 'join_event_meeting/' . $ref_id . '/' . $user_slug . '/' . $order->getReference();

                if ($event_info['tickettemplate'] == 1 || $event_info['tickettemplate'] == null) {
                    $html = $this->renderView('Dashboard/Shared/Order/ticket-pdf.html.twig', [
                        'order' => $order,
                        'eventDateTicketReference' => 'all',
                        'link' => $link,
                        'user_ticket_name' => $user_ticket_name,
                    ]);
                } else {
                    $html = $this->renderView('Dashboard/Shared/Order/ticket-pdf-2.html.twig', [
                        'order' => $order,
                        'eventDateTicketReference' => 'all',
                        'link' => $link,
                        'user_ticket_name' => $user_ticket_name,
                    ]);
                }

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $ticketsPdfFile = $dompdf->output();

                $emailTo = $email;
                $email_subject_title = $userFirstName . ' ' . $userLastName . ', te estamos esperando en ' . $order->getOrderelements()[0]->getEventticket()->getEventdate()->getEvent()->getName();

                $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[_a-z0-9-]+)*(\.[a-z]{2,})$/i";
                // check email is valid or not 
                if ((preg_match($pattern, $emailTo))) {
                    $send_mail = (new \Swift_Message($email_subject_title))
                        ->setFrom($services->getSetting('no_reply_email'))
                        ->setTo($emailTo)
                        ->setBody(
                            $this->renderView('Dashboard/Shared/Order/confirmation-email.html.twig', ['order' => $order, 'org_name' => $org_name, 'description' => $description]),
                            'text/html'
                        )
                        ->attach(new \Swift_Attachment($ticketsPdfFile, $order->getReference() . "-" . $translator->trans("tickets") . '.pdf', 'application/pdf'));

                    $mailer->send($send_mail);
                }
            }
        }
        // return true ;
    }

    private function handleJsonOrRedirect(Request $request, string $route, array $jsonData, int $statusCode)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->json($jsonData, $statusCode);
        } else {
            return $this->redirectToRoute($route);
        }
    }


    /**
     * @Route("/attendee/checkout/done", name="dashboard_attendee_checkout_done")
     * @Route("/pointofsale/checkout/done", name="dashboard_pointofsale_checkout_done")
     */
    public function done(Request $request, AppServices $services, TranslatorInterface $translator)
    {
        // Remove ticket reservations
        $em = $this->getDoctrine()->getManager();
        if (count($this->getUser()->getTicketreservations())) {
            foreach ($this->getUser()->getTicketreservations() as $ticketreservation) {
                $em->remove($ticketreservation);
            }
            $em->flush();
        }

        try {
            $token = $this->get('payum')->getHttpRequestVerifier()->verify($request);
            $gateway = $this->get('payum')->getGateway($token->getGatewayName());
        } catch (Exception $e) {
            $this->addFlash('error', $translator->trans('An error has occured while processing your request'));
            return $this->redirectToRoute("dashboard_index");
        }
        $gateway->execute($status = new GetHumanStatus($token));
        $payment = $status->getFirstModel();
        $this->get('payum')->getHttpRequestVerifier()->invalidate($token);

        if ($status->isCaptured() || $status->isAuthorized() || $status->isPending()) {
            $services->handleSuccessfulPayment($payment->getOrder()->getReference(), $services);
            if ($payment->getOrder()->getOrderElementsPriceSum() > 0) {
                $this->addFlash('success', $translator->trans('Your payment has been successfully processed'));
            } else {
                $this->addFlash('success', $translator->trans('Your registration has been successfully processed'));
            }
            if ($this->isGranted("ROLE_ATTENDEE")) {
                return $this->redirectToRoute("dashboard_attendee_order_details", ['reference' => $payment->getOrder()->getReference()]);
            } else {
                return $this->redirectToRoute("dashboard_pointofsale_order_details", ['reference' => $payment->getOrder()->getReference()]);
            }
        } elseif ($status->isFailed()) {
            $services->handleFailedPayment($payment->getOrder()->getReference());
            $this->addFlash('error', $translator->trans('Your payment could not be processed at this time'));
            if ($this->isGranted("ROLE_ATTENDEE")) {
                return $this->redirectToRoute("dashboard_attendee_checkout_failure", ["number" => $payment->getNumber()]);
            } else {
                return $this->redirectToRoute("dashboard_pointofsale_index");
            }
        } elseif ($status->isCanceled()) {
            $services->handleCanceledPayment($payment->getOrder()->getReference());
            $this->addFlash('error', $translator->trans('Your payment operation was canceled'));
            if ($this->isGranted("ROLE_ATTENDEE")) {
                return $this->redirectToRoute("dashboard_attendee_orders");
            } else {
                return $this->redirectToRoute("dashboard_pointofsale_index");
            }
        } else {
            return $this->redirectToRoute("dashboard_index");
        }
        if ($this->isGranted("ROLE_ATTENDEE")) {
            return $this->render('Dashboard/Attendee/Order/failure.html.twig', [
                'status' => $status->getValue(),
                'paymentdetails' => $payment->getDetails()
            ]);
        } else {
            return $this->redirectToRoute("dashboard_index");
        }
    }

    /**
     * @Route("/attendee/checkout/failure/{number}", name="dashboard_attendee_checkout_failure")
     */
    public function failure($number, Request $request, AppServices $services, TranslatorInterface $translator)
    {
        $referer = $request->headers->get('referer');
        if (!\is_string($referer) || !$referer || $referer != "dashboard_attendee_checkout_done") {
            return $this->redirectToRoute("dashboard_attendee_orders");
        }

        $payment = $services->getPayments(array("number" => $number))->getQuery()->getOneOrNullResult();
        if (!$payment) {
            $this->addFlash('error', $translator->trans('The payment can not be found'));
            return $this->redirectToRoute("dashboard_attendee_orders");
        }

        return $this->render('Dashboard/Attendee/Order/failure.html.twig', [
            'paymentdetails' => $payment->getDetails()
        ]);
    }

    /**
     * @Route("/attendee/my-tickets", name="dashboard_attendee_orders")
     * @Route("/pointofsale/my-orders", name="dashboard_pointofsale_orders")
     * @Route("/organizer/manage-orders", name="dashboard_organizer_orders")
     * @Route("/administrator/manage-orders", name="dashboard_administrator_orders")
     */
    public function orders(Request $request, AppServices $services, PaginatorInterface $paginator, AuthorizationCheckerInterface $authChecker, TranslatorInterface $translator, Connection $connection)
    {

        //$upcomingtickets = ($request->query->get('upcomingtickets')) == "" ? 1 : intval($request->query->get('upcomingtickets'));
        $reference = ($request->query->get('reference')) == "" ? "all" : $request->query->get('reference');
        $event = ($request->query->get('event')) == "" ? "all" : $request->query->get('event');
        $eventdate = ($request->query->get('eventdate')) == "" ? "all" : $request->query->get('eventdate');
        $eventticket = ($request->query->get('eventticket')) == "" ? "all" : $request->query->get('eventticket');
        $user = ($request->query->get('user')) == "" ? "all" : $request->query->get('user');
        $organizer = ($request->query->get('organizer')) == "" ? "all" : $request->query->get('organizer');
        $datefrom = ($request->query->get('datefrom')) == "" ? "all" : $request->query->get('datefrom');
        $dateto = ($request->query->get('dateto')) == "" ? "all" : $request->query->get('dateto');
        $status = ($request->query->get('status')) == "" ? "all" : $request->query->get('status');
        $paymentgateway = ($request->query->get('paymentgateway')) == "" ? "all" : $request->query->get('paymentgateway');

        $authChecker->isGranted("ROLE_ATTENDEE") ? $status = "all" : $upcomingtickets = "all";

        $ordersQuery = $services->getOrders(array("reference" => $reference, "event" => $event, "eventdate" => $eventdate, "eventticket" => $eventticket, "user" => $user, "organizer" => $organizer, "datefrom" => $datefrom, "dateto" => $dateto, "status" => $status, "paymentgateway" => $paymentgateway))->getQuery();

        // Export current orders query results into Excel / Csv
        if (($authChecker->isGranted("ROLE_ADMINISTRATOR") || $authChecker->isGranted("ROLE_ORGANIZER") || $authChecker->isGranted("ROLE_POINTOFSALE")) && (($request->query->get('excel') == "1" || $request->query->get('csv') == "1" || $request->query->get('pdf') == "1"))) {
            $orders = $ordersQuery->getResult();
            if (!count($orders)) {
                $this->addFlash('error', $translator->trans('No orders found to be included in the report'));
                return $services->redirectToReferer("orders");
            }
            if ($request->query->get('excel') == "1" || $request->query->get('csv') == "1") {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle($services->getSetting("website_name") . " " . $translator->trans("orders summary"));
                $fileName = $services->getSetting("website_name") . " " . $translator->trans("orders summary") . ".xlsx";
                $temp_file = tempnam(sys_get_temp_dir(), $fileName);

                $sheet->setCellValue('A3', $translator->trans("Order reference"));
                $sheet->setCellValue('B3', $translator->trans("Order status"));
                $sheet->setCellValue('C3', $translator->trans("Order Date"));
                $sheet->setCellValue('D3', $translator->trans("Organizer / Event / Date / Ticket"));
                $sheet->setCellValue('E3', $translator->trans("First Name"));
                $sheet->setCellValue('F3', $translator->trans("Last Name"));
                $sheet->setCellValue('G3', $translator->trans("Email"));
                $sheet->setCellValue('H3', $translator->trans("Quantity"));
                $sheet->setCellValue('I3', $translator->trans("Amount") . "(" . $services->getSetting("currency_ccy") . ")");
                $sheet->setCellValue('J3', $translator->trans("Payment"));
                $sheet->setCellValue('K3', $translator->trans("Street"));
                $sheet->setCellValue('L3', $translator->trans("Street 2"));
                $sheet->setCellValue('M3', $translator->trans("City"));
                $sheet->setCellValue('N3', $translator->trans("State"));
                $sheet->setCellValue('O3', $translator->trans("Zip / Postal code"));
                $sheet->setCellValue('P3', $translator->trans("Country"));
                $sheet->setCellValue('Q3', $translator->trans("Attendee status"));

                $i = 5;
                $totalSales = 0;
                $totalAttendees = 0;

                foreach ($orders as $order) {
                    foreach ($order->getOrderelements() as $orderElement) {
                        if ($authChecker->isGranted("ROLE_ADMINISTRATOR") || ($authChecker->isGranted("ROLE_ORGANIZER") && $this->getUser()->getOrganizer() == $orderElement->getEventticket()->getEventdate()->getEvent()->getOrganizer()) || $this->isGranted("ROLE_POINTOFSALE")) {
                            if (($event == "all" || $event != "all" && $orderElement->getEventticket()->getEventdate()->getEvent()->getSlug())) {
                                if (($event == "all" || ($event != "all" && $event == $orderElement->getEventticket()->getEventdate()->getEvent()->getSlug())) && ($eventdate == "all" || ($eventdate != "all" && $eventdate == $orderElement->getEventticket()->getEventdate()->getReference())) && ($eventticket == "all" || ($eventticket != "all" && $eventticket == $orderElement->getEventticket()->getReference()))) {
                                    $sheet->setCellValue('A' . $i, $orderElement->getOrder()->getReference());
                                    $sheet->setCellValue('B' . $i, $orderElement->getOrder()->stringifyStatus());
                                    $sheet->setCellValue('C' . $i, date_format($orderElement->getOrder()->getCreatedAt(), $this->getParameter("date_format_simple")));
                                    $sheet->setCellValue('D' . $i, $orderElement->getEventticket()->getEventdate()->getEvent()->getOrganizer()->getName() . " > " . $orderElement->getEventticket()->getEventdate()->getEvent()->getName() . " > " . date_format($orderElement->getEventticket()->getEventdate()->getStartdate(), $this->getParameter("date_format_simple")) . " > " . $orderElement->getEventticket()->getName());
                                    $sheet->setCellValue('E' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getFirstname() : $orderElement->getOrder()->getUser()->getFirstname());
                                    $sheet->setCellValue('F' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getLastname() : $orderElement->getOrder()->getUser()->getFirstname());
                                    $sheet->setCellValue('G' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getClientEmail() : $orderElement->getOrder()->getUser()->getEmail());
                                    $sheet->setCellValue('H' . $i, $orderElement->getQuantity());
                                    $sheet->setCellValue('I' . $i, $orderElement->getPrice());
                                    $sheet->setCellValue('J' . $i, $orderElement->getOrder()->getPaymentgateway() ? $orderElement->getOrder()->getPaymentgateway()->getName() : "");
                                    $sheet->setCellValue('K' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getStreet() : $orderElement->getOrder()->getUser()->getStreet());
                                    $sheet->setCellValue('L' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getStreet2() : $orderElement->getOrder()->getUser()->getStreet2());
                                    $sheet->setCellValue('M' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getCity() : $orderElement->getOrder()->getUser()->getCity());
                                    $sheet->setCellValue('N' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getState() : $orderElement->getOrder()->getUser()->getState());
                                    $sheet->setCellValue('O' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getPostalcode() : $orderElement->getOrder()->getUser()->getPostalcode());
                                    $sheet->setCellValue('P' . $i, $orderElement->getOrder()->getPayment() ? $orderElement->getOrder()->getPayment()->getCountry() : ($orderElement->getOrder()->getUser()->getCountry() ? $orderElement->getOrder()->getUser()->getCountry()->getName() : ""));
                                    $sheet->setCellValue('Q' . $i, $order->getStatus() == 1 ? $orderElement->getScannedTicketsCount() . " / " . $orderElement->getQuantity() : "");
                                    if ($order->getStatus() == 1) {
                                        $totalSales += $orderElement->getPrice();
                                        $totalAttendees += $orderElement->getQuantity();
                                    }
                                    $i++;
                                }
                            }
                        }
                    }
                }

                $sheet->setCellValue('A1', $translator->trans("Generation date") . ": " . date_format(new \Datetime, $this->getParameter("date_format_simple")));
                $sheet->setCellValue('B1', $translator->trans("Total sales") . ": " . $totalSales . " " . $services->getSetting("currency_ccy"));
                $sheet->setCellValue('C1', $translator->trans("Total orders") . ": " . count($orders));
                $sheet->setCellValue('D1', $translator->trans("Total attendees") . ": " . $totalAttendees);

                if ($request->query->get('excel') == "1") {
                    $writer = new Xlsx($spreadsheet);
                } elseif ($request->query->get('csv') == "1") {
                    $writer = new Csv($spreadsheet);
                }
                $writer->save($temp_file);
                return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
            } else if ($request->query->get('pdf') == "1") {
                if (!$request->query->get('event')) {
                    $this->addFlash('error', $translator->trans('You must choose an event in order to export the attendees list'));
                    return $services->redirectToReferer("orders");
                }
                if ($request->query->get('status') != "1") {
                    $this->addFlash('error', $translator->trans('You must set the status to paid in order to export the attendees list'));
                    return $services->redirectToReferer("orders");
                }
                $organizer = "all";
                if ($authChecker->isGranted('ROLE_ORGANIZER')) {
                    $organizer = $this->getUser()->getOrganizer()->getSlug();
                }
                $event = $services->getEvents(array("slug" => $request->query->get('event'), "published" => "all", "elapsed" => "all", "organizer" => $organizer))->getQuery()->getOneOrNullResult();
                if (!$event) {
                    $this->addFlash('error', $translator->trans('The event can not be found'));
                    return $services->redirectToReferer("orders");
                }
                $pdfOptions = new Options();
                //$pdfOptions->set('defaultFont', 'Arial');
                $dompdf = new Dompdf($pdfOptions);
                $html = $this->renderView('Dashboard/Shared/Order/attendees-pdf.html.twig', [
                    'event' => $event,
                    'orders' => $orders
                ]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $dompdf->stream($event->getName() . ": " . $translator->trans("Attendees list"), [
                    "Attachment" => false
                ]);
            }
        }

        $ordersPagination = $paginator->paginate($ordersQuery, $request->query->getInt('page', 1), 10, array('wrap-queries' => true));


        $sqlEvent55 = "SELECT * FROM eventic_event_date WHERE reference = :reference55";
        $paramsEvent55 = ['reference55' => $eventdate];
        $statementEvent55 = $connection->prepare($sqlEvent55);
        $statementEvent55->execute($paramsEvent55);
        $one_event55 = $statementEvent55->fetch();

        if ($one_event55) {
            $sqlEvent66 = "SELECT * FROM eventic_event WHERE id = :id66";
            $paramsEvent66 = ['id66' => $one_event55['event_id']];
            $statementEvent66 = $connection->prepare($sqlEvent66);
            $statementEvent66->execute($paramsEvent66);
            $one_event66 = $statementEvent66->fetch();
        }

        return $this->render('Dashboard/Shared/Order/orders.html.twig', [
            'orders' => $ordersPagination,
            'event_reference' => isset($one_event66['reference']) ? $one_event66['reference'] : false,
        ]);
    }

    /**
     * @Route("/attendee/my-tickets/{reference}", name="dashboard_attendee_order_details")
     * @Route("/pointofsale/my-orders/{reference}", name="dashboard_pointofsale_order_details")
     * @Route("/organizer/recent-orders/{reference}", name="dashboard_organizer_order_details")
     * @Route("/administrator/manage-orders/{reference}", name="dashboard_administrator_order_details")
     */
    public function details($reference, Request $request, TranslatorInterface $translator, AppServices $services)
    {

        $order = $services->getOrders(array("reference" => $reference, "status" => "all"))->getQuery()->getOneOrNullResult();
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }

        $status = null;

        if ($order->getStatus() == 1) {
            $gateway = $this->get('payum')->getGateway($order->getPaymentGateway()->getGatewayName());
            $gateway->execute($status = new GetHumanStatus($order->getPayment()));
        }

        return $this->render('Dashboard/Shared/Order/details.html.twig', [
            'order' => $order,
            'status' => $status
        ]);
    }

    /**
     * @Route("/administrator/manage-orders/{reference}/cancel", name="dashboard_administrator_order_cancel")
     */
    public function cancel($reference, Request $request, TranslatorInterface $translator, AppServices $services)
    {

        $order = $services->getOrders(array("reference" => $reference, "status" => "all"))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }

        if ($order->getDeletedAt()) {
            $this->addFlash('error', $translator->trans('The order has been soft deleted, restore it before canceling it'));
            return $services->redirectToReferer('orders');
        }

        if ($order->getStatus() != 0 && $order->getStatus() != 1) {
            $this->addFlash('error', $translator->trans('The order status must be paid or awaiting payment'));
            return $services->redirectToReferer('orders');
        }

        $services->handleCanceledPayment($order->getReference(), $request->query->get('note'));

        $this->addFlash('error', $translator->trans('The order has been permanently canceled'));

        return $services->redirectToReferer('orders');
    }

    /**
     * @Route("/administrator/manage-orders/{reference}/delete", name="dashboard_administrator_order_delete")
     */
    public function delete($reference, Request $request, TranslatorInterface $translator, AppServices $services)
    {

        $order = $services->getOrders(array("reference" => $reference, "status" => "all"))->getQuery()->getOneOrNullResult();
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }
        $em = $this->getDoctrine()->getManager();

        if ($order->getDeletedAt()) {
            $this->addFlash('error', $translator->trans('The order has been permanently deleted'));
        } else {
            $this->addFlash('notice', $translator->trans('The order has been deleted'));
        }

        if ($order->getPayment()) {
            $order->getPayment()->setOrder(null);
            $em->persist($order);
            $em->persist($order->getPayment());
            $em->flush();
        }

        $em->remove($order);
        $em->flush();

        if ($request->query->get('forceRedirect') == "1") {
            return $this->redirectToRoute("dashboard_administrator_orders");
        }

        return $services->redirectToReferer('orders');
    }

    /**
     * @Route("/administrator/manage-orders/{reference}/restore", name="dashboard_administrator_order_restore")
     */
    public function restore($reference, Request $request, TranslatorInterface $translator, AppServices $services)
    {

        $order = $services->getOrders(array("reference" => $reference, "status" => "all"))->getQuery()->getOneOrNullResult();
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }

        $order->setDeletedAt(null);
        foreach ($order->getOrderelements() as $orderelement) {
            $orderelement->setDeletedAt(null);
            foreach ($orderelement->getTickets() as $ticket) {
                $ticket->setDeletedAt(null);
            }
            foreach ($orderelement->getTicketsReservations() as $ticketReservation) {
                $ticketReservation->setDeletedAt(null);
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();
        $this->addFlash('success', $translator->trans('The order has been succesfully restored'));

        return $services->redirectToReferer('orders');
    }

    /**
     * @Route("/print-tickets/{reference}", name="dashboard_tickets_print")
     */
    public function printTickets($reference, Request $request, TranslatorInterface $translator, AppServices $services, Connection $connection, UrlHelper $urlHelper, Packages $assetsManager)
    {

        $order = $services->getOrders(array("reference" => $reference))->getQuery()->getOneOrNullResult();
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirectToRoute("dashboard_attendee_orders");
        }

        if ($request->getLocale() == "ar") {
            return $this->redirectToRoute("dashboard_tickets_print", ["reference" => $reference, "_locale" => "en"]);
        }

        $eventDateTicketReference = $request->query->get('event', 'all');


        $sqlEvent = "SELECT * FROM eventic_order WHERE reference = :ref_id";
        $paramsEvent = ['ref_id' => $reference];
        $statementEvent = $connection->prepare($sqlEvent);
        $statementEvent->execute($paramsEvent);
        $event_order = $statementEvent->fetch();

        if (!$event_order) {
            $this->addFlash('error', $translator->trans('The event order can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $event_order_id = $event_order['id'];
        $event_user_id = $event_order['user_id'] ?? '';

        $sql = "SELECT * FROM eventic_order_element WHERE order_id = :order_id";
        $params = ['order_id' => $event_order_id];
        $statement = $connection->prepare($sql);
        $statement->execute($params);
        $event_order_ele = $statement->fetch();


        if (!$event_order_ele) {
            $this->addFlash('error', $translator->trans('The event order element date can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $event_ticket_id = $event_order_ele['eventticket_id'];

        $sql2 = "SELECT * FROM eventic_event_date_ticket WHERE id = :ticket_id";
        $params2 = ['ticket_id' => $event_ticket_id];
        $statement2 = $connection->prepare($sql2);
        $statement2->execute($params2);
        $event_date_ticket = $statement2->fetch();

        if (!$event_date_ticket) {
            $this->addFlash('error', $translator->trans('The event ticket can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $event_date_id = $event_date_ticket['eventdate_id'];

        $sql3 = "SELECT * FROM eventic_event_date WHERE id = :date_id";
        $params3 = ['date_id' => $event_date_id];
        $statement3 = $connection->prepare($sql3);
        $statement3->execute($params3);
        $event_date = $statement3->fetch();

        if (!$event_date) {
            $this->addFlash('error', $translator->trans('The event date can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }

        $link_id = $event_date['meetinglink'];

        $event_id = $event_date['event_id'];

        $sql4 = "SELECT * FROM eventic_event WHERE id = :id";
        $params4 = ['id' => $event_id];
        $statement4 = $connection->prepare($sql4);
        $statement4->execute($params4);
        $one_event = $statement4->fetch();

        if (!$one_event) {
            $this->addFlash('error', $translator->trans('The event can not be found'));
            return $this->redirect($request->headers->get('referer'));
        }



        $user_sql = "SELECT * FROM eventic_user WHERE id = :user_id";
        $user_params = ['user_id' => $event_user_id];
        $user_statement = $connection->prepare($user_sql);
        $user_statement->execute($user_params);
        $event_user = $user_statement->fetch();
        $user_slug = $event_user['slug'] ?? '';

        $link = $_ENV['MAIN_DOMAIN'] . 'join_event_meeting/' . $one_event['reference'] . '/' . $user_slug . '/' . $order->getReference();

        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');



        $ord_ele_ticket_name_sql = "SELECT * FROM eventic_order_element WHERE order_id = :order_id";
        $ord_ele_ticket_name_params = [
            'order_id' => $order->getId(),
        ];
        $ord_ele_ticket_name_statement = $connection->prepare($ord_ele_ticket_name_sql);
        $ord_ele_ticket_name_statement->execute($ord_ele_ticket_name_params);
        $ord_ele_ticket_name = $ord_ele_ticket_name_statement->fetchAll();

        $user_ticket_name = [];
        foreach ($ord_ele_ticket_name as $item) {
            $event_id = $item['event_id'];
            $item['buy_user_info'] = json_decode($item['buy_user_info'], true);
            if (is_array($item['buy_user_info']) && count($item['buy_user_info']) > 0) {
                foreach ($item['buy_user_info'] as $info) {
                    $user_ticket_name[] = [$event_id, $info['guest_name']];
                }
            }

        }
        // dd($user_ticket_name);

        // return $this->render('Dashboard/Shared/Order/ticket-pdf.html.twig', [
        //     'order' => $order,
        //     'eventDateTicketReference' => 'all',
        //     'link' => $link,
        //     'name_on_tickets' => explode(',', $order->getPayment()->getNameOnTicket()),
        //     'user_ticket_name' => $user_ticket_name,
        // ]);

        $dompdf = new Dompdf($pdfOptions);
        if ($one_event['tickettemplate'] == 1 || $one_event['tickettemplate'] == null) {
            $html = $this->renderView('Dashboard/Shared/Order/ticket-pdf.html.twig', [
                'order' => $order,
                'eventDateTicketReference' => 'all',
                'link' => $link,
                'name_on_tickets' => explode(',', $order->getPayment()->getNameOnTicket()),
                'user_ticket_name' => $user_ticket_name,
            ]);
        } else {
            $html = $this->renderView('Dashboard/Shared/Order/ticket-pdf-2.html.twig', [
                'order' => $order,
                'eventDateTicketReference' => 'all',
                'link' => $link,
                'name_on_tickets' => explode(',', $order->getPayment()->getNameOnTicket()),
                'user_ticket_name' => $user_ticket_name,
            ]);
        }
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($order->getReference() . "-" . $translator->trans("tickets"), [
            "Attachment" => false
        ]);
        exit(0);
    }

    /**
     * @Route("/organizer/recent-orders/{reference}/resend-confirmation-email", name="dashboard_organizer_order_resend_confirmation_email")
     * @Route("/administrator/manage-orders/{reference}/resend-confirmation-email", name="dashboard_administrator_order_resend_confirmation_email")
     */
    public function resendConfirmationEmail($reference, Request $request, TranslatorInterface $translator, AppServices $services)
    {
        $order = $services->getOrders(array("reference" => $reference, "status" => "all"))->getQuery()->getOneOrNullResult();
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }
        $services->sendOrderConfirmationEmail($order, $request->query->get('email'));
        $this->addFlash('success', $translator->trans('The confirmation email has been resent to') . ' ' . $request->query->get('email'));
        return $services->redirectToReferer('orders');
    }

    /**
     * @Route("/attendee/my-tickets/{reference}/contact-organizer", name="dashboard_attendee_order_contactOrganizer")
     */
    public function contactOrganizer($reference, Request $request, TranslatorInterface $translator, AppServices $services, \Twig_Environment $templating, \Swift_Mailer $mailer)
    {

        $order = $services->getOrders(array("reference" => $reference, "status" => "all"))->getQuery()->getOneOrNullResult();
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }

        $message = $request->request->get('message');

        $emailTo = [];

        foreach ($order->getOrderelements() as $orderElement) {
            $emailTo[] = $orderElement->getEventticket()->getEventdate()->getEvent()->getOrganizer()->getUser()->getEmail();
        }

        $email = (new \Swift_Message($services->getSetting('website_name') . ' - ' . $translator->trans('New message regarding the order') . ' #' . $reference))
            ->setFrom($services->getSetting('no_reply_email'))
            ->setTo($emailTo)
            ->setBody(
                $templating->render('Dashboard/Shared/Order/contact-organizer-email.html.twig', ['order' => $order, 'message' => $message]),
                'text/html'
            );

        $mailer->send($email);

        $this->addFlash('success', $translator->trans('Your message has been successfully sent'));
        return $services->redirectToReferer('orders');
    }

    /**
     * @Route("/organizer/recent-orders/{reference}/contact-attendee", name="dashboard_organizer_order_contactAttendee")
     */
    public function contactAttendee($reference, Request $request, TranslatorInterface $translator, AppServices $services, \Twig_Environment $templating, \Swift_Mailer $mailer)
    {

        $order = $services->getOrders(array("reference" => $reference, "status" => "all"))->getQuery()->getOneOrNullResult();
        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }

        $message = $request->request->get('message');

        $email = (new \Swift_Message($services->getSetting('website_name') . ' - ' . $translator->trans('New message regarding the order') . ' #' . $reference))
            ->setFrom($services->getSetting('no_reply_email'))
            ->setTo($order->getUser()->getEmail())
            ->setBody(
                $templating->render('Dashboard/Shared/Order/contact-attendee-email.html.twig', ['order' => $order, 'message' => $message, 'organizer' => $this->getUser()->getOrganizer()]),
                'text/html'
            );

        $mailer->send($email);

        $this->addFlash('success', $translator->trans('Your message has been successfully sent'));
        return $services->redirectToReferer('orders');
    }

    /**
     * @Route("/administrator/manage-orders/{reference}/validate", name="dashboard_administrator_order_validate")
     * @Route("/organizer/recent-orders/{reference}/validate", name="dashboard_organizer_order_validate")
     */
    public function validate($reference, Request $request, TranslatorInterface $translator, AppServices $services, Connection $connection)
    {

        $order = $services->getOrders(array("reference" => $reference, "status" => 0))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }

        if ($order->getDeletedAt()) {
            $this->addFlash('error', $translator->trans('The order has been soft deleted, restore it before canceling it'));
            return $services->redirectToReferer('orders');
        }

        $services->handleSuccessfulPayment($order->getReference(), $services);

        foreach ($order->getOrderelements()->toArray() as $order_element) {

            $deletedAt = new \DateTime();
            $deletedAtFormatted = $deletedAt->format('Y-m-d H:i:s');

            $update_order_sql = "UPDATE eventic_ticket_reservation SET deleted_at = :deleted_at WHERE orderelement_id = :orderelement_id";
            $update_order_params = [
                'deleted_at'    => $deletedAtFormatted,
                'orderelement_id' => $order_element->getId(),
            ];
            $statement_order = $connection->prepare($update_order_sql);
            $statement_order->execute($update_order_params);
        }

        $this->addFlash('success', $translator->trans('The order has been successfully validated'));

        return $services->redirectToReferer('orders');
    }


    /**
     * @Route("/attendee/tickets/list", name="dashboard_attendee_tickets_list")
     */
    public function dashboard_attendee_tickets_list(Request $request, EntityManagerInterface $entityManager)
    {
        // Single order get
        $sqlSelect = "SELECT id FROM eventic_order WHERE reference = :reference";
        $paramsSelect = [
            'reference' => $request->query->get('orderReference'),
        ];
        $statementSelect = $entityManager->getConnection()->prepare($sqlSelect);
        $statementSelect->execute($paramsSelect);
        $order_id = $statementSelect->fetch()['id'];

        // Multiple order element gets
        $sqlSelect1 = "SELECT * FROM eventic_order_element WHERE order_id = :order_id";
        $paramsSelect1 = [
            'order_id' => $order_id,
        ];
        $statementSelect1 = $entityManager->getConnection()->prepare($sqlSelect1);
        $statementSelect1->execute($paramsSelect1);
        $orders = $statementSelect1->fetchAll();

        // Create an array to store event IDs
        $eventIds = [];
        foreach ($orders as &$item) {
            if (isset($item['buy_user_info'])) {
                $item['buy_user_info'] = json_decode($item['buy_user_info'], true);
            }
            $eventIds[] = $item['event_id'];
        }

        // Get event details for the retrieved event IDs
        if (!empty($eventIds)) {
            $sqlSelect2 = "SELECT translatable_id, name, slug FROM eventic_event_translation WHERE translatable_id IN (" . implode(',', array_map('intval', $eventIds)) . ")";
            $statementSelect2 = $entityManager->getConnection()->prepare($sqlSelect2);
            $statementSelect2->execute();
            $events = $statementSelect2->fetchAll();

            // Create a map of event details for easy lookup
            $eventMap = [];
            foreach ($events as $event) {
                $eventMap[$event['translatable_id']] = $event;
            }

            // Add event details to orders
            foreach ($orders as &$item) {
                if (isset($eventMap[$item['event_id']])) {
                    $item['event_name'] = $eventMap[$item['event_id']]['name'];
                    $item['event_slug'] = $eventMap[$item['event_id']]['slug'];
                }
            }
        }

        return $this->render('Dashboard/Shared/Order/attendee_tickets_list.html.twig', [
            'orders' => $orders,
        ]);
    }

    /**
     * @Route("/attendee/tickets/unpaid_ticket_delete", name="unpaid_ticket_delete")
    */

    public function unpaid_ticket_delete(Request $request, EntityManagerInterface $entityManager, AppServices $services, Connection $connection,TranslatorInterface $translator)
    {
        $orderReference = $request->query->get('orderReference');

        $order = $services->getOrders(array("reference" => $orderReference , "status" => 0))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $services->redirectToReferer('orders');
        }

        if ($order->getDeletedAt()) {
            $this->addFlash('error', $translator->trans('The order has been soft deleted, restore it before canceling it'));
            return $services->redirectToReferer('orders');
        }else{

            $queryBuilder = new QueryBuilder($connection);
            $queryBuilder
                ->update('eventic_order')
                ->set('status', ':status')
                ->where('reference = :reference')
                ->setParameters([
                    'status' => -1,
                    'reference' => $orderReference,
                ]);
            $affectedRows = $queryBuilder->execute();

        }

        $this->addFlash('success', $translator->trans('The order has been successfully deleted'));

        return $services->redirectToReferer('dashboard_attendee_orders');
    }





















}
