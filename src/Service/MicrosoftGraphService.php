<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Component\HttpClient\HttpClient;

class MicrosoftGraphService
{
    private $client;
    private $redirectUri;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->redirectUri = $_ENV['AZURE_REDIRECT_URI'];
    }

    public function getAuthorizationUrl($teams_api_credentials)
    {
        $query = http_build_query([
            'client_id' => $teams_api_credentials['teams_client_id'],
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'response_mode' => 'query',
            'scope' => 'OnlineMeetings.ReadWrite User.Read offline_access Calendars.ReadWrite',
        ]);

        return 'https://login.microsoftonline.com/' .  $teams_api_credentials['teams_tenant_id'] . '/oauth2/v2.0/authorize?' . $query;
    }

    public function getAccessToken($code, $teams_api_credentials)
    {
        $response = $this->client->request('POST', 'https://login.microsoftonline.com/' . $teams_api_credentials['teams_tenant_id'] . '/oauth2/v2.0/token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $teams_api_credentials['teams_client_id'],
                'client_secret' => $teams_api_credentials['teams_client_secret'],
                'redirect_uri' => $this->redirectUri,
                'code' => $code,
            ],
        ]);

        return $response->toArray();
    }

    public function createMeeting($accessToken, $subject, $startDateTime, $endDateTime)
    {

      // dd($accessToken);
      $client = HttpClient::create();

      $response = $client->request('POST', 'https://graph.microsoft.com/v1.0/me/onlineMeetings', [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'startDateTime' => '2024-06-10T14:30:00Z',
          'endDateTime' => '2024-06-10T15:00:00Z',
          'subject' => 'Test Meeting',
        ],
      ]);


    // $result = [
    //   "@odata.type" => "#microsoft.graph.onlineMeeting",
    //   // "@odata.context" => "https://graph.microsoft.com/v1.0/$metadata#users('f4053e7-85f4-f0389ac980d6')/onlineMeetings/$entity",
    //   "audioConferencing" => [
    //     "tollNumber" => "+1252578",
    //     "tollFreeNumber" => "+18588",
    //     "ConferenceId" => "24999",
    //     "dialinUrl" => "https://dialin.teams.microsoft.com/22f12faf--bc69-b8de580ba330?id=24299"
    //   ],
    //   "chatInfo" => [
    //     "threadId" => "19:meeting_M2IzYzczNTItYzUtYmFiMjNlOTY4MGEz@thread.skype",
    //     "messageId" => "0",
    //     "replyChainMessageId" => "0"
    //   ],
    //   "creationDateTime" => "2019-07-11T02:17:17.6491364Z",
    //   "startDateTime" => "2019-07-11T02:17:17.6491364Z",
    //   "endDateTime" => "2019-07-11T02:47:17.651138Z",
    //   "id" => "MSpkYzE3NjctYmZiMi04ZdFpHRTNaR1F6WGhyZWFkLnYy",
    //   "joinWebUrl" => "https://teams.microsoft.com/l/meetup-join/19%3ameeting_M2IzYzczNTItYmY3OC00MDlmLWJjMzUtYmFiMjNlOTY4MGEz%40thread.skype/0?context=%7b%22Tid%22%3a%2272f988bf-87cd011db47%22%2c%22Oid%22%3a%22550fae72-d251-43ec-868c-373732c2704f%22%7d",
    //   "participants" => [
    //     "organizer" => [
    //       "identity" => [
    //         "user" => [
    //           "id" => "550fae72-d25-868c-373732c2704f",
    //           "displayName" => "Heidi Steen"
    //         ]
    //       ],
    //       "upn" => "upn-value"
    //     ]
    //   ],
    //   "subject" => "User Token Meeting",
    //   "joinMeetingIdSettings" => [
    //     "isPasscodeRequired" => false,
    //     "joinMeetingId" => "1234567890",
    //     "passcode" => null
    //   ]
    // ];


      dd($response);

      $statusCode = $response->getStatusCode();
      $content = $response->getContent();
    }
}
