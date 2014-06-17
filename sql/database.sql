SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
CREATE DATABASE `dota2_d2moddin` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `dota2_d2moddin`;

CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int(255) NOT NULL AUTO_INCREMENT,
  `steam_id` bigint(255) NOT NULL,
  `level` tinyint(1) NOT NULL DEFAULT '1',
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `invite_key` (
  `queue_id` bigint(255) NOT NULL AUTO_INCREMENT,
  `steam_id` bigint(255) NOT NULL,
  `invited` tinyint(1) NOT NULL DEFAULT '0',
  `permament` tinyint(1) NOT NULL DEFAULT '0',
  `banned` tinyint(1) NOT NULL DEFAULT '0',
  `banned_reason` text,
  `donated` tinyint(1) NOT NULL DEFAULT '0',
  `donation` decimal(5,2) DEFAULT NULL,
  `donation_fee` decimal(5,2) DEFAULT NULL,
  `donation_email` varchar(255) DEFAULT NULL,
  `donation_txn_id` varchar(30) DEFAULT NULL,
  `donation_ipn_id` varchar(30) DEFAULT NULL,
  `date_invited` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`queue_id`),
  UNIQUE KEY `index_steamid` (`steam_id`),
  KEY `donated` (`donated`),
  KEY `permament` (`permament`),
  KEY `invited` (`invited`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
