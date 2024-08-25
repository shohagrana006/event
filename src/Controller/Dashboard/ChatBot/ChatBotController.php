<?php

namespace App\Controller\Dashboard\ChatBot;

use PDO;
use Exception;
use Throwable;
use GuzzleHttp\Psr7;
use GuzzleHttp\Utils;
use GuzzleHttp\Client;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ChatBotController extends Controller
{

    private $client;
    private $security;
    public function __construct(HttpClientInterface $client, Security $security)
    {
        $this->client = $client;
    }

    public function chatbot_train_text(TranslatorInterface $translator,  EntityManagerInterface $entityManager)
    {
        $chat_bot_lists = [];
        $user = $this->getUser();
        $authId = $user->getId();
        // Get parameters
        $searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? $_GET['perPage'] : 5;

        $perPage = filter_var($perPage, FILTER_VALIDATE_INT);

        // Ensure that perPage is within a valid range
        $validPerPageValues = [5, 10, 20];
        if (!in_array($perPage, $validPerPageValues)) {
            $perPage = 5;
        }

        // Calculate the offset
        $offset = ($page - 1) * $perPage;

        // Query to fetch paginated data
        $sql = "SELECT * FROM chatbot_lists WHERE org_id = :org_id";
        $params = ['org_id' => $authId];
        if (!empty($searchQuery)) {
            $sql .= " AND custom_name LIKE :search";
            $params['search'] = '%' . $searchQuery . '%';
        }

        $sql .= " ORDER BY id DESC";
        $sql .= " LIMIT :limit OFFSET :offset";
        $statement = $entityManager->getConnection()->prepare($sql);
        $statement->bindValue('org_id', $authId);
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        if (!empty($searchQuery)) {
            $statement->bindValue('search', '%' . $searchQuery . '%');
        }
        $statement->execute();
        $chat_bot_lists = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Count total number of records
        $countSql = "SELECT COUNT(*) FROM chatbot_lists WHERE org_id = :org_id";
        $countStatement = $entityManager->getConnection()->prepare($countSql);
        $countStatement->bindValue('org_id', $authId);
        $countStatement->execute();
        $totalRecords = $countStatement->fetchColumn();

        // Calculate total pages
        $totalPages = ceil($totalRecords / $perPage);

        return $this->render('Dashboard/ChatBot/train-text-bot.html.twig', [
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'page' => $page,
            'chat_bot_lists' => $chat_bot_lists,
            'api_host' => $_ENV['CHATBOT_APIHOST'],
            'chatbot_flowise' => $_ENV['CHATBOT_FLOWISE'],
        ]);
    }

    public function add_chatbot_train_text(TranslatorInterface $translator,  EntityManagerInterface $entityManager)
    {
        $bots = [];
        try {
            $response = $this->client->request("GET", $_ENV['CHATBOT_BASEURL'] . '/template-chatbots-sys');
            $bots = $response->toArray();
        } catch (\Exception $exception) {
            $this->addFlash('error', $translator->trans('Chatbot cannot procced right now'));
        }

        return $this->render('Dashboard/ChatBot/add-train-text-bot.html.twig', [
            'bots' => $bots
        ]);
    }

    public function store_chatbot_train_text(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $client = new Client();
        $data = $request->request->all();
        if ($data['bot_text'] != '' && $data['custom_name'] != '') {
            try {
                $user = $this->getUser();
                $authId = $user->getId();

                $response = $client->request("POST", $_ENV['CHATBOT_BASEURL'] . '/create-chatbot-sys/' . $authId, [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        "bot_text" => $data['bot_text']
                    ]
                ]);

                $body = $response->getBody()->getContents();
                $responseData = json_decode($body, true);

                $sql = "INSERT INTO chatbot_lists (org_id, template_id, custom_name, type, description, chatbot_id, chatbot_name, status) 
                VALUES (:org_id, :template_id, :custom_name, :type, :description, :chatbot_id, :chatbot_name, :status)";

                $params = [
                    'org_id'       => $authId,
                    'template_id'  => trim($data['bot_select']),
                    'custom_name'  => trim($data['custom_name']),
                    'type'         => 'text',
                    'description'  => trim($data['bot_text']),
                    'chatbot_id'   => $responseData['chatbotId'],
                    'chatbot_name' => $responseData['chatbotName'],
                    'status'       => 0,
                ];

                $statement = $entityManager->getConnection()->prepare($sql);
                $statement->execute($params);

                $this->addFlash('success', $responseData['chatbotName'] . $translator->trans(' chatbot has been created successfully'));
            } catch (RequestException $e) {
                $this->addFlash('error', $translator->trans('Chatbot cannot proceed right now'));
            }
        } else {
            $this->addFlash('error', $translator->trans('Please Fill up all the required field'));
        }

        return $this->redirectToRoute('chatbot_train_text');
    }

    public function retrain_chatbot_train_text(Request $request, $chatbotId, TranslatorInterface $translator,  EntityManagerInterface $entityManager)
    {
        $bots = [];
        $chatbot = [];
        $sql = "SELECT * FROM chatbot_lists WHERE chatbot_id = :chatbot_id";
        $params = [
            'chatbot_id' => $chatbotId,
        ];
        $statement = $entityManager->getConnection()->prepare($sql);
        $statement->execute($params);
        $chatbot = $statement->fetch();

        try {
            $response = $this->client->request("GET", $_ENV['CHATBOT_BASEURL'] . '/template-chatbots-sys');
            $bots = $response->toArray();
        } catch (\Exception $exception) {
           
        }

        return $this->render('Dashboard/ChatBot/retrain-train-text-bot.html.twig', [
            'chatbot' => $chatbot,
            'bots'    => $bots
        ]);
    }

    public function retrain_store_chatbot_train_text(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $client = new Client();
        $data = $request->request->all();
        if ($data['bot_text'] != '') {
            try {
                $response = $client->request("PUT", $_ENV['CHATBOT_BASEURL'] . '/retrain-sys', [
                    'headers' => [
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        "bot_id" => $data['bot_id'],
                        "status" => $data['status'] == 'true' ? true : false,
                        "bot_text" => $data['bot_text'],
                        "userId" => $data['userId'],
                    ]
                ]);
                $this->addFlash('success', $translator->trans('Chatbot re-train has been successfully'));
            } catch (RequestException $e) {
                $this->addFlash('error', $translator->trans('Chatbot cannot proceed right now'));
            }
        } else {
            $this->addFlash('error', $translator->trans('Please Fill up the required field'));
        }
        return $this->redirectToRoute('chatbot_train_text');
    }








    public function chatbot_train_attachment(TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        $chat_bot_lists = [];
        $user = $this->getUser();
        $authId = $user->getId();

        // Get parameters
        $searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        $perPage = isset($_GET['perPage']) ? $_GET['perPage'] : 5;

        $perPage = filter_var($perPage, FILTER_VALIDATE_INT);

        // Ensure that perPage is within a valid range
        $validPerPageValues = [5, 10, 20];
        if (!in_array($perPage, $validPerPageValues)) {
            $perPage = 5;
        }

        // Calculate the offset
        $offset = ($page - 1) * $perPage;

        // Query to fetch paginated data
        $sql = "SELECT * FROM chatbot_lists WHERE org_id = :org_id";
        $params = ['org_id' => $authId];
        if (!empty($searchQuery)) {
            $sql .= " AND custom_name LIKE :search";
            $params['search'] = '%' . $searchQuery . '%';
        }
        $sql .= " ORDER BY id DESC";
        $sql .= " LIMIT :limit OFFSET :offset";
        $statement = $entityManager->getConnection()->prepare($sql);
        $statement->bindValue('org_id', $authId);
        $statement->bindValue('limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue('offset', $offset, PDO::PARAM_INT);
        if (!empty($searchQuery)) {
            $statement->bindValue('search', '%' . $searchQuery . '%');
        }
        $statement->execute();
        $chat_bot_lists = $statement->fetchAll(PDO::FETCH_ASSOC);

        // Count total number of records
        $countSql = "SELECT COUNT(*) FROM chatbot_lists WHERE org_id = :org_id";
        $countStatement = $entityManager->getConnection()->prepare($countSql);
        $countStatement->bindValue('org_id', $authId);
        $countStatement->execute();
        $totalRecords = $countStatement->fetchColumn();

        // Calculate total pages
        $totalPages = ceil($totalRecords / $perPage);

        return $this->render('Dashboard/ChatBot/train-attachment-bot.html.twig', [
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'page' => $page,
            'chat_bot_lists' => $chat_bot_lists,
            'api_host' => $_ENV['CHATBOT_APIHOST'],
            'chatbot_flowise' => $_ENV['CHATBOT_FLOWISE'],
        ]);
    }


    public function add_chatbot_train_attachment(TranslatorInterface $translator,  EntityManagerInterface $entityManager)
    {
        $bots = [];
        try {
            $response = $this->client->request("GET", $_ENV['CHATBOT_BASEURL'] . '/template-chatbots-sys');
            $bots = $response->toArray();
        } catch (\Exception $exception) {
            $this->addFlash('error', $translator->trans('Chatbot cannot procced right now'));
        }

        return $this->render('Dashboard/ChatBot/add-train-attachment-bot.html.twig', [
            'bots' => $bots
        ]);
    }

    public function store_chatbot_train_attachment(Request $request,  EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $client = new Client();
        $data = $request->request->all();

        if ($request->files->get('files') !== null) {


            $files = $request->files->get('files');
            $options = [
                'multipart' => [],
            ];
            foreach ($files as $file) {
                $options['multipart'][] = [
                    'name'     => 'files',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                    'headers'  => [
                        'Content-Type' => $file->getClientMimeType()
                    ]
                ];
            }

            try {
                $user = $this->getUser();
                $authId = $user->getId();

                $response = $client->request("POST", $_ENV['CHATBOT_BASEURL'] . '/create-chatbot-weavi/' . $authId, $options);
                $body = $response->getBody()->getContents();
                $responseData = json_decode($body, true);
                $sql = "INSERT INTO chatbot_lists (org_id, template_id, custom_name, type, description, chatbot_id, chatbot_name, status) 
                VALUES (:org_id, :template_id, :custom_name, :type, :description, :chatbot_id, :chatbot_name, :status)";

                $params = [
                    'org_id'       => $authId,
                    'template_id'  => trim($data['bot_select']),
                    'custom_name'  => trim($data['custom_name']),
                    'type'         => 'file',
                    'description'  => trim($data['text']?? null),
                    'chatbot_id'   => $responseData['chatbotId'],
                    'chatbot_name' => $responseData['chatbotName'],
                    'status'       => 0,
                ];

                $statement = $entityManager->getConnection()->prepare($sql);
                $statement->execute($params);

                $this->addFlash('success', $responseData['chatbotName'] . $translator->trans(' chatbot has been created successfully'));
            } catch (RequestException $e) {
                $this->addFlash('error', $translator->trans('Chatbot cannot procced right now'));
            }
        } else {
            $this->addFlash('error', $translator->trans('Please insert file'));
        }
        return $this->redirectToRoute('chatbot_train_attachment');
    }


    public function retrain_chatbot_train_attachment(Request $request, $chatbotId, TranslatorInterface $translator,  EntityManagerInterface $entityManager)
    {
        $bots = [];
        $chatbot = [];
        $sql = "SELECT * FROM chatbot_lists WHERE chatbot_id = :chatbot_id";
        $params = [
            'chatbot_id' => $chatbotId,
        ];
        $statement = $entityManager->getConnection()->prepare($sql);
        $statement->execute($params);
        $chatbot = $statement->fetch();

        try {
            $response = $this->client->request("GET", $_ENV['CHATBOT_BASEURL'] . '/template-chatbots-sys');
            $bots = $response->toArray();
        } catch (\Exception $exception) {
        }

        return $this->render('Dashboard/ChatBot/retrain-train-attachment-bot.html.twig', [
            'chatbot' => $chatbot,
            'bots'    => $bots
        ]);
    }

    public function retrain_store_chatbot_train_attachment(Request $request, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $client = new Client();
        $data = $request->request->all();

        if (!empty($request->files->get('files'))) {
            $files = $request->files->get('files');
            $options = [
                'multipart' => [],
            ];
            foreach ($files as $file) {
                $options['multipart'][] = [
                    'name'     => 'files',
                    'contents' => fopen($file->getRealPath(), 'r'),
                    'filename' => $file->getClientOriginalName(),
                    'headers'  => [
                        'Content-Type' => $file->getClientMimeType()
                    ]
                ];
            }

            try {

                $bot_id = $data['bot_id'];
                $userId = $data['userId'];

                $response = $client->request("PUT", $_ENV['CHATBOT_BASEURL'] . '/retrain-txt/' . $userId.'?bot_id='.$bot_id, $options);
         
                $this->addFlash('success', $translator->trans('Chatbot retrain has been successfully'));
            } catch (RequestException $e) {
                $this->addFlash('error', $translator->trans('Chatbot cannot procced right now'));
            }
        } else {
            $this->addFlash('error', $translator->trans('Please insert file'));
        }
        return $this->redirectToRoute('chatbot_train_attachment');
    }



    public function delete_chatbot_list(Request $request, $chatbotId, EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        
        $client = new Client();
        try {
            $user = $this->getUser();
            $authId = $user->getId();
            // database
            $sql = "DELETE FROM chatbot_lists WHERE org_id = :org_id AND chatbot_id = :chatbot_id";
            $params = [
                'org_id' => $authId,
                'chatbot_id' => $chatbotId,
            ];
            $statement = $entityManager->getConnection()->prepare($sql);
            $statement->execute($params);

            $this->addFlash('success', $translator->trans('Chatbot deleted successfully'));
        } catch (RequestException $e) {
            $this->addFlash('error', $translator->trans('Chatbot cannot procced right now'));
        }
        // api
        try {
            $response = $client->request("DELETE", $_ENV['CHATBOT_BASEURL'] . "/delete-chatbot?userId=$authId&chatbotId=$chatbotId");
        } catch (\Throwable $th) {
            //throw $th;
        }

        $referrer = $request->headers->get('referer');
        return $this->redirect($referrer);
    }

    // public function chatbot_train_list()
    // {
    //     return $this->render('Dashboard/ChatBot/chatbot_train_list.html.twig');
    // }



}
