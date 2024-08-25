<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Service\AppServices;
use Payum\Core\Security\CypherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpClient\HttpClient;

class FlutterwaveController extends Controller {

    private $cypher;

    public function __construct(CypherInterface $cypher = null) {
        $this->cypher = $cypher;
    }

    /**
     * @Route("/dashboard/attendee/checkout/{orderReference}/flutterwave/redirect-to-payment-url", name="dashboard_attendee_checkout_flutterwave_redirect_to_payment_url")
     */
    public function redirectToPaymentUrl($orderReference, Request $request, TranslatorInterface $translator, AppServices $services, UrlGeneratorInterface $router) {

        $order = $services->getOrders(array('status' => 0, 'reference' => $orderReference))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirectToRoute("dashboard_index");
        }

        $paymentGateway = $order->getPaymentgateway();
        $paymentGateway->decrypt($this->cypher);
        $paymentGatewaySettings = $paymentGateway->getSettings();

        $payload = [];
        $payload[] = ['fieldName' => 'public_key', 'fieldValue' => $paymentGatewaySettings['flutterwave_public_key']];
        $payload[] = ['fieldName' => 'tx_ref', 'fieldValue' => $order->getReference()];
        $payload[] = ['fieldName' => 'amount', 'fieldValue' => $order->getOrderElementsPriceSum(true)];
        $payload[] = ['fieldName' => 'currency', 'fieldValue' => $services->getSetting("currency_ccy")];
        $payload[] = ['fieldName' => 'redirect_url', 'fieldValue' => $router->generate('flutterwave_verify_transaction', ['orderReference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL)];
        $payload[] = ['fieldName' => 'customer[name]', 'fieldValue' => $order->getPayment()->getFirstname() . ' ' . $order->getPayment()->getLastname()];
        $payload[] = ['fieldName' => 'customer[email]', 'fieldValue' => $order->getPayment()->getClientEmail()];

        return $this->render('Dashboard/Attendee/Order/redirect-to-payment-gateway.html.twig', [
                    'payload' => $payload,
                    'postUrl' => $paymentGatewaySettings['flutterwave_checkout_url']
        ]);
    }

    /**
     * @Route("/flutterwave/{orderReference}/verify-transaction", name="flutterwave_verify_transaction")
     */
    public function verifyTransaction($orderReference, Request $request, AppServices $services, TranslatorInterface $translator) {

        $order = $services->getOrders(array('status' => 0, 'reference' => $orderReference))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirectToRoute("dashboard_index");
        }

        $token = new UsernamePasswordToken($order->getUser(), $order->getUser()->getPassword(), "main", $order->getUser()->getRoles());
        $this->get("security.token_storage")->setToken($token);
        $event = new InteractiveLoginEvent($request, $token);
        $this->get("event_dispatcher")->dispatch("security.interactive_login", $event);

        if ($request->query->get('status') == "cancelled") {
            $services->handleCanceledPayment($order->getReference(), $translator->trans('Your order has been automatically canceled'));
            $this->addFlash('error', $translator->trans('Your order has been automatically canceled'));
            return $this->redirectToRoute("dashboard_attendee_order_details", ['reference' => $orderReference]);
        } elseif ($request->query->get('status') == "successful") {
            if ($request->query->get('transaction_id') != null) {
                $httpClient = HttpClient::create();
                $paymentGateway = $order->getPaymentgateway();
                $paymentGateway->decrypt($this->cypher);
                $paymentGatewaySettings = $paymentGateway->getSettings();
                $flutterwaveTransactionVerificationUrl = $paymentGatewaySettings['flutterwave_transaction_verification_url'];
                $flutterwaveTransactionVerificationUrl = str_replace('{transactionId}', $request->query->get('transaction_id'), $flutterwaveTransactionVerificationUrl);
                try {

                    $requestHeaders = array(
                        'Authorization' => 'Bearer ' . $paymentGatewaySettings['flutterwave_secret_key'],
                        'Content-Type' => 'application/json',
                    );

                    $response = $httpClient->request('GET', $flutterwaveTransactionVerificationUrl, array('headers' => $requestHeaders, 'timeout' => 20));

                    $responseContent = $response->getContent(false);
                    $flutterwaveResponse = json_decode($responseContent);

                    if ($response->getStatusCode() != 200) {
                        $services->handleCanceledPayment($order->getReference(), $translator->trans('Your order has been automatically canceled because there was an error with the payment gateway: response code is not 200'));
                        $this->addFlash('error', $translator->trans('Your order has been automatically canceled because there was an error with the payment gateway: response code is not 200'));
                        return $this->redirectToRoute("dashboard_index");
                    } elseif ($flutterwaveResponse->status == "success") {
                        $order->getPayment()->setDetails($responseContent);
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($order->getPayment());
                        $em->flush();
                        $services->handleSuccessfulPayment($order->getReference());
                        $this->addFlash('success', $translator->trans('Your payment has been successfully processed'));
                        return $this->redirectToRoute("dashboard_attendee_order_details", ['reference' => $order->getReference()]);
                    } else {
                        $services->handleCanceledPayment($order->getReference(), $translator->trans('Your order has been automatically canceled because your payment was rejected by Flutterwave'));
                        $this->addFlash('error', $translator->trans('Your order has been automatically canceled because your payment was rejected by Flutterwave'));
                        return $this->redirectToRoute("dashboard_index");
                    }
                } catch (\Exception $e) {
                    $services->handleCanceledPayment($order->getReference(), $translator->trans('Your order has been automatically canceled because there was an error with the payment gateway: ' . $e->getMessage()));
                    $this->addFlash('error', $translator->trans('Your order has been automatically canceled because there was an error with the payment gateway: ' . $e->getMessage()));
                    return $this->redirectToRoute("dashboard_index");
                }
            } else {
                $services->handleFailedPayment($orderReference, $translator->trans('Your order could not be processed because the transaction_id could not retreived from Flutterwave'));
                $this->addFlash('error', $translator->trans('Your order could not be processed because the transaction_id could not retreived from Flutterwave'));
                return $this->redirectToRoute("dashboard_attendee_order_details", ['reference' => $orderReference]);
            }
        }
        $services->handleFailedPayment($orderReference, $translator->trans('Your order could not be processed because the status could not retreived from Flutterwave'));
        $this->addFlash('error', $translator->trans('Your order could not be processed because the status could not retreived from Flutterwave'));
        return $this->redirectToRoute("dashboard_attendee_order_details", ['reference' => $orderReference]);
    }
}
