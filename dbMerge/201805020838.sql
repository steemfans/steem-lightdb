/* 08:49:35 localhost */ CREATE DATABASE `steemdb` DEFAULT CHARACTER SET = `utf8mb4`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS `blocks`;

CREATE TABLE `blocks` (
  `id` bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  `block_num` bigint(11) NOT NULL,
  `previous` varchar(40) NOT NULL DEFAULT '',
  `block_id` varchar(40) NOT NULL DEFAULT '',
  `block_info` mediumtext NOT NULL,
  `timestamp` varchar(19) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `block_num` (`block_num`),
  UNIQUE KEY `previous` (`previous`),
  UNIQUE KEY `block_id` (`block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `transactions`;

CREATE TABLE `transactions` (
  `id` bigint(18) unsigned NOT NULL AUTO_INCREMENT,
  `block_id` bigint(11) NOT NULL,
  `content` longtext NOT NULL,
  `block_num` bigint(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `block_id` (`block_id`),
  KEY `block_num` (`block_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

