<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\AppServices;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\User;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Connection;

class UserController extends Controller {

    /**
     * @Route("/get-organizers", name="get_organizers")
     * @Route("/get-users", name="get_users")
     */

    private $eventDispatcher;
    private $formFactory;
    private $userManager;
    private $tokenStorage;
    private $services;
    public $passwordEncoder;

    public function __construct(UserManagerInterface $userManager,UserPasswordEncoderInterface $passwordEncoder,AppServices $services) {
      //  $this->eventDispatcher = $eventDispatcher;
        //$this->formFactory = $formFactory;
        $this->userManager = $userManager;
        $this->passwordEncoder = $passwordEncoder;
        //$this->tokenStorage = $tokenStorage;
        $this->services = $services;
    }

    public function getUsers(Request $request, AppServices $services): Response {

        if (!$this->isGranted("ROLE_ADMINISTRATOR") && !$this->isGranted("ROLE_ORGANIZER")) {
            throw new AccessDeniedHttpException();
        }

        $q = ($request->query->get('q')) == "" ? "all" : $request->query->get('q');
        $limit = ($request->query->get('limit')) == "" ? 10 : $request->query->get('limit');

        if ($request->get('_route') == "get_organizers") {
            $users = $services->getUsers(array('role' => 'organizer', 'organizername' => $q, 'limit' => $limit))->getQuery()->getResult();
        } else if ($request->get('_route') == "get_users") {
            if ($this->isGranted("ROLE_ORGANIZER")) {
                $attendees = $services->getUsers(array('keyword' => $q, 'role' => 'attendee', 'hasboughtticketfororganizer' => $this->getUser()->getOrganizer()->getSlug(), 'limit' => $limit))->getQuery()->getResult();
                $pointsofsale = $services->getUsers(array('keyword' => $q, 'role' => 'pointofsale', 'limit' => $limit))->getQuery()->getResult();
                $users = array_merge($attendees, $pointsofsale);
            } else {
                $attendees = $services->getUsers(array('keyword' => $q, 'role' => 'attendee', 'limit' => $limit))->getQuery()->getResult();
                $pointsofsale = $services->getUsers(array('keyword' => $q, 'role' => 'point_of_sale', 'limit' => $limit))->getQuery()->getResult();
                $users = array_merge($attendees, $pointsofsale);
            }
        }

        $results = array();
        foreach ($users as $user) {
            if ($request->get('_route') == "get_organizers") {
                $result = array('id' => $user->getOrganizer()->getSlug(), 'text' => $user->getOrganizer()->getName());
            } else if ($request->get('_route') == "get_users") {
                $result = array('id' => $user->getSlug(), 'text' => $user->getCrossRoleName());
            }
            array_push($results, $result);
        }

        return $this->json($results);
    }

    /**
     * @Route("/get-organizer/{slug}", name="get_organizer")
     * @Route("/get-user/{slug}", name="get_user")
     */
    public function getUserEntity(Request $request, $slug = null, AppServices $services): Response {

        if ($request->get('_route') == "get_organizer") {
            if (!$this->isGranted("ROLE_ADMINISTRATOR")) {
                throw new AccessDeniedHttpException();
            }
            $user = $services->getUsers(array('role' => 'organizer', 'organizerslug' => $slug))->getQuery()->getOneOrNullResult();
            return $this->json(array("slug" => $user->getOrganizer()->getSlug(), "text" => $user->getOrganizer()->getName()));
        } else if ($request->get('_route') == "get_user") {

            if (!$this->isGranted("ROLE_ADMINISTRATOR") && !$this->isGranted("ROLE_ORGANIZER")) {
                throw new AccessDeniedHttpException();
            }
            $hasboughtticketfororganizer = "all";
            if ($this->isGranted("ROLE_ORGANIZER")) {
                $hasboughtticketfororganizer = $this->getUser()->getOrganizer()->getSlug();
            }

            $user = $services->getUsers(array('role' => 'attendee', 'slug' => $slug, 'hasboughtticketfororganizer' => $hasboughtticketfororganizer))->getQuery()->getOneOrNullResult();

            if (!$user) {
                $user = $services->getUsers(array('role' => 'point_of_sale', 'slug' => $slug, 'createdbyorganizerslug' => $this->getUser()->getOrganizer()->getSlug()))->getQuery()->getOneOrNullResult();
            }

            return $this->json(array("slug" => $user->getSlug(), "text" => $user->getFullName()));
        }
    }

    public function apiCreateUser(Request $request){

        $data = json_decode($request->getContent(), true);

        if (!isset($data['firstname']) || !isset($data['email']) || !isset($data['password']) || !isset($data['lastname'])) {
            return $this->json(['error' => 'firstname,lastname,email and password are required.'], 400);
        }

        if($isCheck == 'true'){
            $user = $this->userManager->createUser();
            $user->setEnabled(true);
            $user->setUsername($data['firstname'].$data['email']);
            $user->setEmail($data['email']);
            $user->setFirstname($data['firstname']);
            $user->setLastname($data['lastname']);
            $user->addRole('ROLE_ATTENDEE');
            
            $user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));
    
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $lastInsertedId = $user->getId();
    
            return $this->json(array("code" => 200, "status" => true,"user_id" => $lastInsertedId));
            die();
        }else{
           return $this->json(array("code" => 200, "status" => failed,"message" => "This email id already taken"));
            die(); 
        }

    }

    public function checkEmailExist(Connection $connection,$email){
        
        $queryBuilder = new QueryBuilder($connection);
         
        $queryBuilder
                ->select('eventic_user.id')
                ->from('eventic_user')
                ->where('email = :email')
                ->setParameters(['email' => $email]);

        $statement = $queryBuilder->execute();
        $resultElement = $statement->fetchColumn();
        if($resultElement == null){
            return 'true'; 
        }else{
            return 'false';
        }
    }

    public function insertOrderData(Connection $connection,$data){

        $queryBuilder = new QueryBuilder($connection);
        $queryBuilder
            ->insert('eventic_order') // Replace 'order_table' with your actual table name
            ->values([
                'user_id' => $queryBuilder->createNamedParameter($data['user_id']),
                'reference' => $queryBuilder->createNamedParameter($this->generateReference(15)),
                'paymentgateway_id' => $queryBuilder->createNamedParameter(3),
                'status' => $queryBuilder->createNamedParameter(0),
                'note' => $queryBuilder->createNamedParameter('By API'),
                'ticket_fee' => $queryBuilder->createNamedParameter(0.00),
                'ticket_price_percentage_cut' => $queryBuilder->createNamedParameter(0),
                'status' => $queryBuilder->createNamedParameter(1),
                'currency_ccy' => $queryBuilder->createNamedParameter('COP'),
                'currency_symbol' => $queryBuilder->createNamedParameter('$'),
                'created_at' => $queryBuilder->createNamedParameter(date('Y-m-d H:i:s')),
                
            ]);

        $queryBuilder->execute();
        $lastInsertId = $connection->lastInsertId();
        return $lastInsertId;

    }

    public function insertOrderElementData(Connection $connection, $lastInsertId, $data)
    {
        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder
            ->insert('eventic_order_element') // Replace 'eventic_order_element' with your actual table name
            ->values([
                'order_id' => $queryBuilder->createNamedParameter($lastInsertId),
                'eventticket_id' => $queryBuilder->createNamedParameter($data['eventticket_id']),
                'unitprice' => $queryBuilder->createNamedParameter($data['unitprice']),
                'quantity' => $queryBuilder->createNamedParameter(1),
            ]);

        $queryBuilder->execute();

        $lastInsertId = $connection->lastInsertId();
        return $lastInsertId;
    }


    public function insertPaymentData(Connection $connection, $orderId, $data,$orderAmount,$services)
    {

        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder
            ->insert('eventic_payment') // Replace 'eventic_payment' with your actual table name
            ->values([
                'order_id' => $queryBuilder->createNamedParameter($orderId),
                'number' => $queryBuilder->createNamedParameter($this->generateReference(20)),
                'country_id' => $queryBuilder->createNamedParameter(123),
                'currency_code' => $queryBuilder->createNamedParameter($services->getSetting("currency_ccy")),
                'total_amount' => $queryBuilder->createNamedParameter($orderAmount),
                'description' => $queryBuilder->createNamedParameter("Payment of tickets purchased by Api"),
                'client_id' => $queryBuilder->createNamedParameter($data['user_id']),
                'firstname' => $queryBuilder->createNamedParameter($data['firstname']),
                'lastname' => $queryBuilder->createNamedParameter($data['lastname']),
                'client_email' => $queryBuilder->createNamedParameter($data['email']),
                'state' => $queryBuilder->createNamedParameter($data['state']),
                'city' => $queryBuilder->createNamedParameter($data['city']),
                'postalcode' => $queryBuilder->createNamedParameter($data['postalcode']),
                'street' => $queryBuilder->createNamedParameter($data['street']),
                'street2' => $queryBuilder->createNamedParameter(isset($data['street2']) ? $data['street2'] : 'null'),
                'created_at' => $queryBuilder->createNamedParameter(date('Y-m-d H:i:s')),
                'updated_at' => $queryBuilder->createNamedParameter(date('Y-m-d H:i:s')),
                'details' => $queryBuilder->createNamedParameter(json_encode($data)),

            ]);

        $queryBuilder->execute();
        $lastInsertId = $connection->lastInsertId();
        return $lastInsertId;
    }

    public function insertOrderTicket(Connection $connection, $orderElementId)
    {
        $reference = $this->generateReference(20);
        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder->insert('eventic_order_ticket')
        ->values([
            'orderelement_id' => $queryBuilder->createNamedParameter($orderElementId),
            'reference' =>  $queryBuilder->createNamedParameter($reference),
            'scanned' => true,
            'created_at' => $queryBuilder->createNamedParameter(date('Y-m-d H:i:s')),
            'updated_at' => $queryBuilder->createNamedParameter(date('Y-m-d H:i:s')),
        ]);

        $queryBuilder->execute();
        $lastInsertId = $connection->lastInsertId();
        return $lastInsertId;

    }

    public function insertTicketReservation(Connection $connection, $orderElementId,$data,$services)
    {  
        $expiresAt = new \DateTime();
        $expiresAt->add(new \DateInterval('PT' . $services->getSetting("checkout_timeleft") . 'S'));
        $queryBuilder = new QueryBuilder($connection);

        $queryBuilder->insert('eventic_ticket_reservation')
        ->values([
            'eventticket_id' => $queryBuilder->createNamedParameter( $data['eventticket_id'] ) ,
            'user_id' => $queryBuilder->createNamedParameter( $data['user_id'] ) ,
            'orderelement_id' => $queryBuilder->createNamedParameter( $orderElementId ) ,
            'quantity' =>  $queryBuilder->createNamedParameter( $data['quantity'] ) ,
            'created_at'  =>$queryBuilder->createNamedParameter( date('Y-m-d H:i:s') ) ,
            'expires_at' =>$queryBuilder->createNamedParameter( $expiresAt->format('Y-m-d H:i:s') )  ,
        ]);
        $queryBuilder->execute();
        $lastInsertId = $connection->lastInsertId();
        return $lastInsertId;

    } 

    public function isOrderHaveCheck(Connection $connection, $data){

        try {

            $queryBuilder = new QueryBuilder($connection);
            $queryBuilder
                    ->select('eventic_order.id') 
                    ->from('eventic_order')
                    ->where('user_id = :user_id')
                    ->andWhere('status = :status')
                    ->setParameters([
                        'user_id' => $data['user_id'],
                        'status' => 0,
                    ]);
            $statement = $queryBuilder->execute();
            $result = $statement->fetchAll();
            if(count( $result) > 0){
                $this->getOrderElement($connection,$result,$data['eventticket_id']);
                return 'update';
            }else{
                return 'true';
            } 
            
        } catch (\Exception $e) {
            return 'false';
        }

    }

    public function getOrderElement( Connection $connection,$result,$eventTicketId){
        
        $queryBuilder = new QueryBuilder($connection);
        if (count($result) > 0) {
            $queryBuilder
                ->select('eventic_order_element.order_id')
                ->from('eventic_order_element')
                ->where('order_id IN (:order_ids)')
                ->andWhere('eventticket_id = :eventticket_id')
                ->setParameters([
                    'order_ids' => array_column($result, 'id'), 
                    'eventticket_id' => $eventTicketId, 
                ], [
                    'order_ids' => \Doctrine\DBAL\Connection::PARAM_INT_ARRAY, 
                ]);
    
            $statement = $queryBuilder->execute();
            $resultElement = $statement->fetchAll();
            if(count($resultElement) > 0){
                $this->paymentDataCheck($connection,$resultElement[0]['order_id']);
                return 'update';
            }
        }else{
            return 'true';
        }
    }

    public function paymentDataCheck(Connection $connection,$id){

        $queryBuilder = new QueryBuilder($connection);
         
        $queryBuilder
                ->select('eventic_payment.*')
                ->from('eventic_payment')
                ->where('order_id = :order_id')
                ->setParameters(['order_id' => $id]);

        $statement = $queryBuilder->execute();
        $resultElement = $statement->fetch(\Doctrine\DBAL\FetchMode::ASSOCIATIVE);

        if($resultElement != null && $id){
            $status = 1; // The new status value
            $queryBuilder
                    ->update('eventic_order')
                    ->set('status', ':status')
                    ->set('payment_id', ':payment_id')
                    ->set('paymentgateway_id', ':paymentgateway_id')
                    ->where('id = :id')
                    ->setParameters([
                        'status' => $status,
                        'id' => $id,
                        'payment_id' => isset($resultElement['id']) ? $resultElement['id'] : null,
                        'paymentgateway_id' => isset($resultElement['paymentgateway_id']) && $resultElement['paymentgateway_id'] == null ? $resultElement['paymentgateway_id'] : 3,
                    ]);
            $affectedRows = $queryBuilder->execute();
            return "update";
        }else{
            return 'true' ;
        }
        
    }

    public function updateOrderPaymentID(Connection $connection,$orderId,$paymentId){

        $queryBuilder = new QueryBuilder($connection);

        if($paymentId != null){
            $queryBuilder
            ->update('eventic_order')
            ->set('payment_id', ':payment_id')
            ->where('id = :id')
            ->setParameters([
                'payment_id' => $paymentId,
                'id' => $orderId,
            ]);
            $affectedRows = $queryBuilder->execute();
            return 'true';
        }
        
    }

    public function userTicketAssign(Request $request,TranslatorInterface $translator,Connection $connection){

        $data = json_decode($request->getContent(), true);

        $requiredKeys = ['firstname', 'email', 'user_id', 'lastname', 'unitprice', 'eventticket_id', 'state', 'city', 'postalcode', 'street'];
        
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (!isset($data[$key])) {
                $missingKeys[] = $key;
            }
        }
        // If there are missing keys, return an error
        if (!empty($missingKeys)) {
            return $this->json(['error' => 'Missing required keys: ' . implode(', ', $missingKeys)], 400);
        }

        $isTrue = $this->isOrderHaveCheck($connection,$data);
        
        if($isTrue == 'true' ){

            $totalAmount = $data['unitprice'] * $data['quantity'] ;
            $orderAmount = intval(bcmul($totalAmount, 100));
            $services=$this->services ;
            $user =  $data['user_id'];
            $orderId = $this->insertOrderData($connection,$data);
            $orderElimentId = $this->insertOrderElementData($connection, $orderId, $data); 
            $orderTicketId = $this->insertOrderTicket($connection, $orderElimentId);
            $paymentid = $this->insertPaymentData($connection, $orderId, $data,$orderAmount,$services);
            $this->updateOrderPaymentID($connection,$orderId,$paymentid);
            // $ticketReservationId = $this->insertTicketReservation($connection,$orderElimentId ,$data,$services);
            return $this->json(array("code" => 200, "status" => true, "user_ticket" => true)); 
            die();
        }elseif($isTrue == 'update' ){
            return $this->json(array("code" => 200, "status" => true, "user_ticket" => true , "message" => "You card ticket is paid")); 
            die();
        }else{
            return $this->json(array("code" => 202, "status" => false, "user_ticket" => false)); 
            die();
        }
        
    }

    public function generateReference($length) {
        $reference = implode('', [
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),
            bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),
            bin2hex(random_bytes(2))
        ]);
        return strlen($reference) > $length ? substr($reference, 0, $length) : $reference;
    }

}
