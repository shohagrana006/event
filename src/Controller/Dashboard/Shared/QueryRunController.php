<?php

namespace App\Controller\Dashboard\Shared;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Annotation\Route;

class QueryRunController extends Controller {

    private $connection;
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @Route("/db_query_run/asdf_1234_wwww", name="db_query_run", methods="GET")
     */
    public function dbQueryRun()
    {
        // $sql = "ALTER TABLE `eventic_payment` ADD `status` VARCHAR(255) NULL DEFAULT NULL AFTER `order_id`";
        // $sql1 = "ALTER TABLE `api_settings` CHANGE `zoom_clint_id` `zoom_client_id` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL, CHANGE `zoom_clint_secret` `zoom_client_secret` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL, CHANGE `teams_clint_id` `teams_client_id` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL, CHANGE `teams_clint_secret` `teams_client_secret` VARCHAR(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NULL DEFAULT NULL";
        // $sql2 = "ALTER TABLE `api_settings` ADD `teams_tenant_id` VARCHAR(255) NULL DEFAULT NULL AFTER `teams_client_secret`";
        // $sql3 = "ALTER TABLE `event_zoom_meeting_list` ADD `type` TINYTEXT NULL DEFAULT NULL AFTER `id`";
        // $sql4 = "ALTER TABLE `api_settings` ADD `sdk_secret` VARCHAR(255) NULL DEFAULT NULL AFTER `sdk_key`";
        // $sql5 = "ALTER TABLE `eventic_order_element` ADD `event_id` INT NULL DEFAULT NULL AFTER `quantity`, ADD `buy_user_info` JSON NULL DEFAULT NULL AFTER `event_id`";
        // $sql6 = "UPDATE `eventic_order` SET `deleted_at` = NULL WHERE `eventic_order`.`id` = 2686";
        $sql7 = "UPDATE `eventic_order` SET `status` = '0' WHERE `eventic_order`.`id` = 2686";

        // try {
        //     $this->connection->executeStatement($sql);
        //     $msg = 'Query Run Successful';
        // } catch (\Exception $e) {
        //     $msg =$e->getMessage();
        // }
        // try {
        //     $this->connection->executeStatement($sql1);
        //     $msg1 = 'Query 1 Run Successful';
        // } catch (\Exception $e) {
        //     $msg1 =$e->getMessage();
        // }

        // try {
        //     $this->connection->executeStatement($sql2);
        //     $msg2 = 'Query 2 Run Successful';
        // } catch (\Exception $e) {
        //     $msg2 = $e->getMessage();
        // }
        
        // try {
        //     $this->connection->executeStatement($sql3);
        //     $msg3 = 'Query 3 Run Successful';
        // } catch (\Exception $e) {
        //     $msg3 = $e->getMessage();
        // }

        // try {
        //     $this->connection->executeStatement($sql4);
        //     $msg4 = 'Query 4 Run Successful';
        // } catch (\Exception $e) {
        //     $msg4 = $e->getMessage();
        // }

        // try {
        //     $this->connection->executeStatement($sql5);
        //     $msg5 = 'Query 5 Run Successful';
        // } catch (\Exception $e) {
        //     $msg5 = $e->getMessage();
        // }

        try {
            $this->connection->executeStatement($sql7);
            $msg7 = 'Query 7 Run Successful';
        } catch (\Exception $e) {
            $msg7 = $e->getMessage();
        }

        // dd($msg, $msg1, $msg2, $msg3, $msg4);
        dd($msg7);

    }




}

