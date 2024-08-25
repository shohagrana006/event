// Zoom sdk key (general app client id) add column

  - ALTER TABLE `api_settings` ADD `sdk_secret` VARCHAR(255) NULL DEFAULT NULL AFTER `sdk_key`;


// this sql for buy ticket user info save

  - ALTER TABLE `eventic_order_element` ADD `event_id` INT NULL DEFAULT NULL AFTER `quantity`, ADD `buy_user_info` JSON NULL DEFAULT NULL AFTER `event_id`;