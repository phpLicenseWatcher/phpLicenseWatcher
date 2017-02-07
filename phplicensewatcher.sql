

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


/*Table structure for table `feature` */

DROP TABLE IF EXISTS `feature`;

CREATE TABLE `feature` (
  `featureID` bigint(20) NOT NULL AUTO_INCREMENT,
  `feature` varchar(100) DEFAULT NULL,
  `showInLists` tinyint(4) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`featureID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

/*Table structure for table `flexlm_events` */

DROP TABLE IF EXISTS `flexlm_events`;

CREATE TABLE `flexlm_events` (
  `flmevent_date` date NOT NULL,
  `flmevent_time` time NOT NULL,
  `flmevent_type` varchar(20) NOT NULL,
  `flmevent_feature` varchar(40) NOT NULL,
  `flmevent_user` varchar(80) NOT NULL,
  `flmevent_reason` text NOT NULL,
  PRIMARY KEY (`flmevent_date`,`flmevent_time`,`flmevent_feature`,`flmevent_user`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `license_usage` */

DROP TABLE IF EXISTS `license_usage`;

CREATE TABLE `license_usage` (
  `flmusage_server` varchar(80) NOT NULL,
  `flmusage_product` varchar(80) NOT NULL,
  `flmusage_date` date NOT NULL,
  `flmusage_time` time NOT NULL,
  `flmusage_users` int(11) NOT NULL,
  PRIMARY KEY (`flmusage_product`,`flmusage_server`,`flmusage_date`,`flmusage_time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `licenses_available` */

DROP TABLE IF EXISTS `licenses_available`;

CREATE TABLE `licenses_available` (
  `flmavailable_date` date NOT NULL,
  `flmavailable_server` varchar(80) NOT NULL,
  `flmavailable_product` varchar(80) NOT NULL,
  `flmavailable_num_licenses` int(11) NOT NULL,
  PRIMARY KEY (`flmavailable_date`,`flmavailable_server`,`flmavailable_product`,`flmavailable_num_licenses`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `server` */

DROP TABLE IF EXISTS `server`;

CREATE TABLE `server` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `alias` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;

/*Table structure for table `server_status` */

DROP TABLE IF EXISTS `server_status`;

CREATE TABLE `server_status` (
  `server_id` bigint(20) NOT NULL,
  `server_dns` varchar(100) DEFAULT NULL,
  `server_port` bigint(20) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  `lm_hostname` varchar(100) DEFAULT NULL,
  `isMaster` tinyint(4) DEFAULT NULL,
  `lmgrd_version` varchar(20) DEFAULT NULL,
  `last_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
