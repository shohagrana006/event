<?php

namespace App\Controller\Dashboard\ZoomSdk;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Service\AppServices;


class ZoomSdkController extends Controller
{

  private $tokenManager;
  private $userRepository;
  private $tokenStorage;

  public function __construct(CsrfTokenManagerInterface $tokenManager = null,TokenStorageInterface $tokenStorage, UserRepository $userRepository)
  {
    $this->tokenManager = $tokenManager;
    $this->userRepository = $userRepository;
    $this->tokenStorage = $tokenStorage;
  }

  public function zoomSdkPlayer(Request $request, Connection $connection, AppServices $services, TranslatorInterface $translator,AuthorizationCheckerInterface $authChecker, $reference, $join_user_slug, $order_id)
  {
    $ErrorBackUrl = $_ENV['MAIN_DOMAIN'].'en/dashboard/attendee/my-tickets';
    $ud = $request->query->get('ud') ?? null;

    if($ud != null){

      $sql3 = "SELECT slug, email, firstname, lastname FROM eventic_user WHERE slug = :slug";
        $params3 = ['slug' => $ud];
        $statement3 = $connection->prepare($sql3);
        $statement3->execute($params3);
        $user = $statement3->fetch();

        if($user == null){
          $this->addFlash('error', $translator->trans('Invalid Link!!!'));
          return new RedirectResponse($ErrorBackUrl);
        }

        $csrfToken = $this->tokenManager ? $this->tokenManager->getToken('authenticate')->getValue() : null;

      return $this->render('Front/Luminous/set_password.html.twig',[
        'user' => $user,
        'csrf_token' => $csrfToken
      ]);
    }

    if (!$authChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
      $sql_user = "SELECT id, slug, email, firstname, lastname FROM eventic_user WHERE slug = :slug";
      $params_user = ['slug' => $join_user_slug];
      $statement_user = $connection->prepare($sql_user);
      $statement_user->execute($params_user);
      $user_get = $statement_user->fetch();

      $user = $this->userRepository->find($user_get['id']);
      if (!$user) {
        throw new UsernameNotFoundException(sprintf('User with ID "%s" not found.', 100));
      }
      $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
      $this->tokenStorage->setToken($token);
    }

    $sqlEvent = "SELECT * FROM eventic_event WHERE reference = :reference";
    $paramsEvent = ['reference' => $reference];
    $statementEvent = $connection->prepare($sqlEvent);
    $statementEvent->execute($paramsEvent);
    $one_event = $statementEvent->fetch();

    if (!$one_event) {
        $this->addFlash('error', $translator->trans('The event can not be found'));
        return new RedirectResponse($ErrorBackUrl);
    }

    $event_id = $one_event['id'];

    $org_id = $one_event['organizer_id'];

    $sql = "SELECT * FROM eventic_event_date WHERE event_id = :id";
    $params = ['id' => $event_id];
    $statement = $connection->prepare($sql);
    $statement->execute($params);
    $event_date = $statement->fetch();

    if (!$event_date) {
        $this->addFlash('error', $translator->trans('The event date can not be found'));
        return new RedirectResponse($ErrorBackUrl);
    }

    $link_id = $event_date['meetinglink'];
    if (!$link_id) {
      $this->addFlash('error', $translator->trans('The organizer not set the valid link. Please talk to organizer.'));
      return new RedirectResponse($ErrorBackUrl);
    }

    $sql5 = "SELECT * FROM event_zoom_meeting_list WHERE id = :id";
    $params5 = ['id' => $link_id];
    $statement5 = $connection->prepare($sql5);
    $statement5->execute($params5);
    $event_meeting = $statement5->fetch();

    if (!$event_meeting) {
        $this->addFlash('error', $translator->trans('The event Meeting can not be found'));
        return new RedirectResponse($ErrorBackUrl);
    }

    $sql7 = "SELECT * FROM eventic_organizer WHERE id = :id";
    $params7 = ['id' => $org_id];
    $statement7 = $connection->prepare($sql7);
    $statement7->execute($params7);
    $organizer = $statement7->fetch();

    if (!$organizer) {
        $this->addFlash('error', $translator->trans('The event Organizer can not be found'));
        return new RedirectResponse($ErrorBackUrl);
    }

    $userId = $organizer['user_id'];
  
    if (!$userId) {
      $this->addFlash('error', $translator->trans('The event Meeting Credential can not be found'));
      return new RedirectResponse($ErrorBackUrl);
    }

    $sql6 = "SELECT * FROM api_settings WHERE user_id = :user_id";
    $params6 = ['user_id' => $userId];
    $statement6 = $connection->prepare($sql6);
    $statement6->execute($params6);
    $api_setting = $statement6->fetch();


    if (!$api_setting) {
        $this->addFlash('error', $translator->trans('The event Meeting Credential can not be found'));
        return new RedirectResponse($ErrorBackUrl);
    }

    $sql7 = "SELECT * FROM  chatbot_lists WHERE id = :id";
    $params7 = ['id' => $one_event['chatbot_list']];
    $statement7 = $connection->prepare($sql7);
    $statement7->execute($params7);
    $chatbot = $statement7->fetch();

    // who join the meeting
    $attendee = $this->getUser();
    $attendee_id = $attendee->getId();

    $order_sql_order = "SELECT * FROM eventic_order WHERE reference = :order_id";
    $order_params_order = ['order_id' => $order_id];
    $order_statement_order = $connection->prepare($order_sql_order);
    $order_statement_order->execute($order_params_order);
    $event_order_element_order = $order_statement_order->fetch();

    $order_sql = "SELECT * FROM eventic_order_element WHERE order_id = :order_id";
    $order_params = ['order_id' => $event_order_element_order['id']];
    $order_statement = $connection->prepare($order_sql);
    $order_statement->execute($order_params);
    $event_order_element = $order_statement->fetch();



    $updateSql = "UPDATE eventic_order SET venue = :venue, join_meeting = :join_meeting, join_time = :join_time WHERE user_id = :attendee_id AND event_ref_id = :event_ref_id";
    $updateParams = [
      'venue'        => 'online',
      'join_meeting' => 1,
      'join_time'    => date('Y-m-d H:i:s', time()),
      'attendee_id'  => $attendee_id,
      'event_ref_id'  => $reference,
    ];
    $updateStatement = $connection->prepare($updateSql);
    $updateStatement->execute($updateParams);




    $order_updateSql = "UPDATE eventic_order_ticket SET scanned = :scanned, updated_at = :updated_at WHERE orderelement_id = :orderelement_id";
    $order_updateParams = [
      'scanned'         => 1,
      'updated_at'      => date('Y-m-d H:i:s', time()),
      'orderelement_id' => $event_order_element['id'] ?? null,
    ];
    $order_updateStatement = $connection->prepare($order_updateSql);
    $order_updateStatement->execute($order_updateParams);

      return $this->render('Dashboard/ZoomSdk/zoom-sdk.html.twig',[
        'nodeServer' => $_ENV['NODE_SERVER'],
        'quizApiUrl' => $_ENV['QUIZ_API'],
        'zoomAuthEndPoint' => $_ENV['ZOOM_AUTH_END_POINT'],
        'leaveUrl' => $_ENV['MAIN_DOMAIN'],
        'sdkKey' => $api_setting['sdk_key'] ?? null,
        'sdk_secret' => $api_setting['sdk_secret'] ?? null,
        'event_meeting' => $event_meeting,
        'chatbot_id' => $chatbot['chatbot_id'] ?? null,
        'api_host' => $_ENV['CHATBOT_APIHOST'],
        'chatbot_flowise' => $_ENV['CHATBOT_FLOWISE'],
      ]);

  }
}
