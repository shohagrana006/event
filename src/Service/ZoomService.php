<?php

namespace App\Service;


use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class ZoomService
{
  private $client_id;
  private $client_secret;
  private $account_id;
  private $client;
  private $accessToken;

  public function __construct($zoom_account_id, $zoom_client_id, $zoom_client_secret)
  {
    $this->client_id     = $zoom_client_id;
    $this->client_secret = $zoom_client_secret;
    $this->account_id    = $zoom_account_id;

    $this->accessToken = $this->getAccessToken();

    $this->client = new Client([
      'base_uri' => 'https://api.zoom.us/v2/',
      'headers' => [
        'Authorization' => 'Bearer ' . $this->accessToken,
        'Content-Type' => 'application/json',
      ],
    ]);
  }


  protected function getAccessToken()
  {

    $client = new Client([
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
        'Host' => 'zoom.us',
      ],
    ]);
    try {
      $response = $client->request('POST', "https://zoom.us/oauth/token", [
        'form_params' => [
          'grant_type' => 'account_credentials',
          'account_id' => $this->account_id,
        ],
      ]);
      $responseBody = json_decode($response->getBody(), true);
      return $responseBody['access_token'];
    } catch (\Throwable $th) {
      //throw $th;
    }

   

    
  }

//  meeting create
  public function createMeeting($data)
  {
    try {
      $response = $this->client->request('POST', 'users/me/meetings', [
        'json' => $data,
      ]);
      $res = json_decode($response->getBody(), true);
      return [
        'status' => true,
        'data' => $res,
      ];
    } catch (\Throwable $th) {
      return [
        'status' => false,
        'message' => $th->getMessage(),
      ];
    }
  }

  // get all meeting
  public function getAllMeeting()
  {
    try {
      $response = $this->client->request('GET', 'users/me/meetings');
      $data = json_decode($response->getBody(), true);
      return [
        'status' => true,
        'data' => $data,
      ];
    } catch (\Throwable $th) {
      return [
        'status' => false,
        'message' => $th->getMessage(),
      ];
    }
  }








}
