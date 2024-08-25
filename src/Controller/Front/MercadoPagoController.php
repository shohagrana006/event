<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Service\AppServices;
use Payum\Core\Security\CypherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use MercadoPago;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class MercadoPagoController extends Controller {

    private $cypher;

    public function __construct(CypherInterface $cypher = null) {
        $this->cypher = $cypher;
    }

    /**
     * @Route("/dashboard/attendee/checkout/{orderReference}/mercadopago/create-preference", name="dashboard_attendee_checkout_mercadopago_create_preference")
     */
    public function createPreference($orderReference, Request $request, UploaderHelper $uploadHelper, TranslatorInterface $translator, AppServices $services, UrlGeneratorInterface $router) {

        $order = $services->getOrders(array('status' => 0, 'reference' => $orderReference))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirectToRoute("dashboard_index");
        }

        $paymentGateway = $order->getPaymentgateway();
        $paymentGateway->decrypt($this->cypher);
        $paymentGatewaySettings = $paymentGateway->getSettings();

        MercadoPago\SDK::setAccessToken($paymentGatewaySettings["mercadopago_access_token"]);
        $preference = new MercadoPago\Preference();

        //Iem
        $item = new MercadoPago\Item();
        $item->id = $order->getReference();
        $item->picture_url = $services->getSetting("website_url") . $uploadHelper->asset($services->getAppLayoutSettings(), 'logoFile');
        $item->currency_id = $services->getSetting("currency_ccy");
        $item->title = $translator->trans("Payment of tickets purchased on %website_name%", array('%website_name%' => $services->getSetting("website_name")));
        $item->quantity = 1;
        $item->unit_price = $order->getOrderElementsPriceSum(true);
        $preference->items = array($item);

        //Payer
        $payer = new MercadoPago\Payer();
        $payer->name = $order->getPayment()->getFirstname();
        $payer->surname = $order->getPayment()->getLastname();
        $payer->email = $order->getPayment()->getClientEmail();
        $preference->payer = $payer;

        $preference->back_urls = array(
            "success" => $router->generate('dashboard_attendee_checkout_mercadopago_success', ['orderReference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL),
            "failure" => $router->generate('dashboard_attendee_checkout_mercadopago_failure', ['orderReference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL),
            "pending" => $router->generate('dashboard_attendee_checkout_mercadopago_pending', ['orderReference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL)
        );
        $preference->auto_return = "approved";
        $preference->save();

        if ($preference->error) {
            $this->addFlash('error', "MercadoPago error: " . $preference->error->message);
            return $this->redirectToRoute("dashboard_index");
        }

        return $this->render('Dashboard/Attendee/Order/showMercadoPagoCheckoutButton.html.twig', [
                    'preferenceId' => $preference->id,
                    'mercadoPagoPublicKey' => $paymentGatewaySettings["mercadopago_public_key"]
        ]);
    }

    /**
     * @Route("/dashboard/attendee/checkout/{orderReference}/mercadopago/success", name="dashboard_attendee_checkout_mercadopago_success")
     */
    public function success($orderReference, Request $request, AppServices $services, TranslatorInterface $translator) {

        $order = $services->getOrders(array('status' => 0, 'reference' => $orderReference))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirectToRoute("dashboard_index");
        }

        $order->getPayment()->setDetails(json_encode($request->query->all()));
        $em = $this->getDoctrine()->getManager();
        $em->persist($order->getPayment());
        $em->flush();

        if ($request->query->get('status') == "approved") {
            $services->handleSuccessfulPayment($order->getReference());
            $this->addFlash('success', $translator->trans('Your payment has been successfully processed'));
        } else {
            $services->handleFailedPayment($orderReference, $translator->trans('Your order could not be processed because the MercadoPago payment was not approved'));
            $this->addFlash('error', $translator->trans('Your order could not be processed because the MercadoPago payment was not approved'));
        }

        return $this->redirectToRoute("dashboard_attendee_order_details", ['reference' => $order->getReference()]);
    }

    /**
     * @Route("/dashboard/attendee/checkout/{orderReference}/mercadopago/failure", name="dashboard_attendee_checkout_mercadopago_failure")
     */
    public function failure($orderReference, Request $request, AppServices $services, TranslatorInterface $translator) {

        $order = $services->getOrders(array('status' => 0, 'reference' => $orderReference))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirectToRoute("dashboard_index");
        }

        $services->handleFailedPayment($orderReference, $translator->trans('Your order could not be processed because the MercadoPago payment failed'));
        $this->addFlash('error', $translator->trans('Your order could not be processed because the MercadoPago payment failed'));
        return $this->redirectToRoute("dashboard_attendee_order_details", ['reference' => $orderReference]);
    }

    /**
     * @Route("/dashboard/attendee/checkout/{orderReference}/mercadopago/pending", name="dashboard_attendee_checkout_mercadopago_pending")
     */
    public function pending($orderReference, Request $request, AppServices $services, TranslatorInterface $translator) {

        $order = $services->getOrders(array('status' => 0, 'reference' => $orderReference))->getQuery()->getOneOrNullResult();

        if (!$order) {
            $this->addFlash('error', $translator->trans('The order can not be found'));
            return $this->redirectToRoute("dashboard_index");
        }

        $services->handleCanceledPayment($order->getReference(), $translator->trans('Your order has been automatically canceled because the MercadoPago payment is still pending'));
        $this->addFlash('error', $translator->trans('Your order has been automatically canceled because the MercadoPago payment is still pending'));
        return $this->redirectToRoute("dashboard_index");
    }
}
