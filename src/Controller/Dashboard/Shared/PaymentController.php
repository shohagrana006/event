<?php

namespace App\Controller\Dashboard\Shared;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    private $hyperswitchApiKey;
    private $hyperswitchApiBaseUrl;

    public function __construct(string $hyperswitchApiKey, string $hyperswitchApiBaseUrl)
    {
        $this->hyperswitchApiKey = $hyperswitchApiKey;
        $this->hyperswitchApiBaseUrl = $hyperswitchApiBaseUrl;
    }

    /**
     * @Route("/order/create-payment", name="order_create_payment", methods={"POST"})
     */
    public function createPayment(Request $request): JsonResponse
    {
        try {
            $jsonStr = $request->getContent();
            $jsonObj = json_decode($jsonStr, true);
            if (!isset($jsonObj['totalPrice']) || !is_numeric($jsonObj['totalPrice'])) {
                return new JsonResponse(["error" => "Invalid or missing totalPrice parameter"], 400);
            }

            if (!isset($jsonObj['currency'])) {
                return new JsonResponse(["error" => "Invalid or missing currency parameter"], 400);
            }

            if (!isset($jsonObj['customerId'])) {
                return new JsonResponse(["error" => "Invalid or missing customerId parameter"], 400);
            }

            $customerId = $jsonObj['customerId'];

            $currency = $jsonObj['currency'];

            $totalPrice = $jsonObj['totalPrice'];

            $payload = json_encode(array(
                "amount" => $totalPrice,
                "currency" => $currency ?? "USD",
                "customer_id" => $customerId ?? "eventos_customer"
            ));

            $ch = curl_init($this->hyperswitchApiBaseUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                'api-key: ' . $this->hyperswitchApiKey
            ));

            $responseFromAPI = curl_exec($ch);
            if ($responseFromAPI === false) {
                return new JsonResponse(array("error" => curl_error($ch)), 403);
            }

            curl_close($ch);

            $decodedResponse = json_decode($responseFromAPI, true);

            return new JsonResponse(array("client_secret" => $decodedResponse['client_secret']));
        } catch (\Exception $e) {
            return new JsonResponse(array("error" => $e->getMessage()), 403);
        }
    }

    private function calculateOrderAmount(array $items): int
    {
        // Replace this constant with a calculation of the order's amount
        // Calculate the order total on the server to prevent
        // people from directly manipulating the amount on the client
        return 1400;
    }
}
