ALTER TABLE `client` ADD `bank_account_number` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `notes` , ADD `bank_code` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `bank_account_number` , ADD `bank_name` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `bank_code` , ADD `bank_account_iban` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `bank_name` , ADD `bank_account_swift` VARCHAR( 255 ) NULL DEFAULT NULL AFTER `bank_account_iban`;