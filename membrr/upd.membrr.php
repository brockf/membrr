<?php

/**
=====================================================
 Membrr for EE
-----------------------------------------------------
 http://www.membrr.com/
-----------------------------------------------------
 Copyright (c) 2010, Electric Function, Inc.
 Use this software at your own risk.  Electric
 Function, Inc. assumes no responsibility for
 loss or damages as a result of using this software.

 This software is copyrighted.
=====================================================
*/

class Membrr_upd {
	var $version = '1.80';
	var $EE;

	function Membrr_upd () {
		$this->EE =& get_instance();

		return TRUE;
	}

	function update ($current = '') {
		if ($current < '1.0.2') {
			$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` ADD COLUMN `expiry_processed` TINYINT NOT NULL AFTER `active`');
			$this->EE->db->query('ALTER TABLE `exp_membrr_config` DROP COLUMN `notification_url`');

			$insert_array = array(
								'class' => 'Membrr',
								'method' => 'post_notify'
							);

			$this->EE->db->insert('exp_actions',$insert_array);
		}

		if ($current < '1.0.3') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` MODIFY COLUMN `end_date` DATETIME');
        }

        if ($current < '1.0.5') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_payments` ADD COLUMN `refunded` TINYINT NOT NULL AFTER `date`');
        }

        if ($current < '1.0.6') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_plans` ADD COLUMN `plan_initial_charge` FLOAT AFTER `plan_price`');
        }

        if ($current < '1.0.8') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_plans` ADD COLUMN `plan_gateway` INT(11) AFTER `plan_initial_charge`');
        }

        if ($current < '1.0.91') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_channel_posts` ADD COLUMN `channel_post_date` DATETIME NOT NULL');
        }

        if ($current < '1.2') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` ADD COLUMN `renewed_recurring_id` INT(11) AFTER `active`');
        }

        if ($current < '1.41') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_address_book` ADD COLUMN `company` VARCHAR(250) AFTER `postal_code`');
        	$this->EE->db->query('ALTER TABLE `exp_membrr_address_book` ADD COLUMN `phone` VARCHAR(250) AFTER `company`');
        }

        if ($current < '1.42') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_channels` CHANGE `one_post` `posts` INT(11)');
        }

        if ($current < '1.51') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_plans` ADD COLUMN `plan_renewal_extend_from_end` TINYINT(1) AFTER `plan_redirect_url`');
        	$this->EE->db->query('UPDATE `exp_membrr_plans` SET `plan_renewal_extend_from_end` = \'1\'');
        }

        if ($current < '1.65') {
        	$this->EE->db->query('CREATE TABLE `exp_membrr_countries` (
								  `country_id` int(11) NOT NULL,
								  `iso2` varchar(2) NOT NULL,
								  `iso3` varchar(3) NOT NULL,
								  `name` varchar(255) NOT NULL,
								  `available` tinyint(1) NOT NULL,
								  PRIMARY KEY  (`country_id`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

			$this->EE->db->query("INSERT INTO `exp_membrr_countries` (`country_id`, `iso2`, `iso3`, `name`, `available`)
									VALUES
								(4,'AF','AFG','Afghanistan','1'),
								(248,'AX','ALA','Aland Islands','1'),
								(8,'AL','ALB','Albania','1'),
								(12,'DZ','DZA','Algeria','1'),
								(16,'AS','ASM','American Samoa','1'),
								(20,'AD','AND','Andorra','1'),
								(24,'AO','AGO','Angola','1'),
								(660,'AI','AIA','Anguilla','1'),
								(10,'AQ','ATA','Antarctica','1'),
								(28,'AG','ATG','Antigua and Barbuda','1'),
								(32,'AR','ARG','Argentina','1'),
								(51,'AM','ARM','Armenia','1'),
								(533,'AW','ABW','Aruba','1'),
								(36,'AU','AUS','Australia','1'),
								(40,'AT','AUT','Austria','1'),
								(31,'AZ','AZE','Azerbaijan','1'),
								(44,'BS','BHS','Bahamas','1'),
								(48,'BH','BHR','Bahrain','1'),
								(50,'BD','BGD','Bangladesh','1'),
								(52,'BB','BRB','Barbados','1'),
								(112,'BY','BLR','Belarus','1'),
								(56,'BE','BEL','Belgium','1'),
								(84,'BZ','BLZ','Belize','1'),
								(204,'BJ','BEN','Benin','1'),
								(60,'BM','BMU','Bermuda','1'),
								(64,'BT','BTN','Bhutan','1'),
								(68,'BO','BOL','Bolivia','1'),
								(70,'BA','BIH','Bosnia and Herzegovina','1'),
								(72,'BW','BWA','Botswana','1'),
								(74,'BV','BVT','Bouvet Island','1'),
								(76,'BR','BRA','Brazil','1'),
								(86,'IO','IOT','British Indian Ocean Territory','1'),
								(96,'BN','BRN','Brunei Darussalam','1'),
								(100,'BG','BGR','Bulgaria','1'),
								(854,'BF','BFA','Burkina Faso','1'),
								(108,'BI','BDI','Burundi','1'),
								(116,'KH','KHM','Cambodia','1'),
								(120,'CM','CMR','Cameroon','1'),
								(124,'CA','CAN','Canada','1'),
								(132,'CV','CPV','Cape Verde','1'),
								(136,'KY','CYM','Cayman Islands','1'),
								(140,'CF','CAF','Central African Republic','1'),
								(148,'TD','TCD','Chad','1'),
								(152,'CL','CHL','Chile','1'),
								(156,'CN','CHN','China','1'),
								(162,'CX','CXR','Christmas Island','1'),
								(166,'CC','CCK','Cocos (Keeling) Islands','1'),
								(170,'CO','COL','Colombia','1'),
								(174,'KM','COM','Comoros','1'),
								(178,'CG','COG','Congo','1'),
								(180,'CD','COD','Congo, Democratic Republic of the','1'),
								(184,'CK','COK','Cook Islands','1'),
								(188,'CR','CRI','Costa Rica','1'),
								(384,'CI','CIV','Côte d\'Ivoire','1'),
								(191,'HR','HRV','Croatia','1'),
								(192,'CU','CUB','Cuba','1'),
								(196,'CY','CYP','Cyprus','1'),
								(203,'CZ','CZE','Czech Republic','1'),
								(208,'DK','DNK','Denmark','1'),
								(262,'DJ','DJI','Djibouti','1'),
								(212,'DM','DMA','Dominica','1'),
								(214,'DO','DOM','Dominican Republic','1'),
								(218,'EC','ECU','Ecuador','1'),
								(818,'EG','EGY','Egypt','1'),
								(222,'SV','SLV','El Salvador','1'),
								(226,'GQ','GNQ','Equatorial Guinea','1'),
								(232,'ER','ERI','Eritrea','1'),
								(233,'EE','EST','Estonia','1'),
								(231,'ET','ETH','Ethiopia','1'),
								(238,'FK','FLK','Falkland Islands (Malvinas)','1'),
								(234,'FO','FRO','Faroe Islands','1'),
								(242,'FJ','FJI','Fiji','1'),
								(246,'FI','FIN','Finland','1'),
								(250,'FR','FRA','France','1'),
								(254,'GF','GUF','French Guiana','1'),
								(258,'PF','PYF','French Polynesia','1'),
								(260,'TF','ATF','French Southern Territories','1'),
								(266,'GA','GAB','Gabon','1'),
								(270,'GM','GMB','Gambia','1'),
								(268,'GE','GEO','Georgia','1'),
								(276,'DE','DEU','Germany','1'),
								(288,'GH','GHA','Ghana','1'),
								(292,'GI','GIB','Gibraltar','1'),
								(300,'GR','GRC','Greece','1'),
								(304,'GL','GRL','Greenland','1'),
								(308,'GD','GRD','Grenada','1'),
								(312,'GP','GLP','Guadeloupe','1'),
								(316,'GU','GUM','Guam','1'),
								(320,'GT','GTM','Guatemala','1'),
								(831,'GG','GGY','Guernsey','1'),
								(324,'GN','GIN','Guinea','1'),
								(624,'GW','GNB','Guinea-Bissau','1'),
								(328,'GY','GUY','Guyana','1'),
								(332,'HT','HTI','Haiti','1'),
								(334,'HM','HMD','Heard Island and McDonald Islands','1'),
								(336,'VA','VAT','Holy See (Vatican City State)','1'),
								(340,'HN','HND','Honduras','1'),
								(344,'HK','HKG','Hong Kong','1'),
								(348,'HU','HUN','Hungary','1'),
								(352,'IS','ISL','Iceland','1'),
								(356,'IN','IND','India','1'),
								(360,'ID','IDN','Indonesia','1'),
								(364,'IR','IRN','Iran, Islamic Republic of','1'),
								(368,'IQ','IRQ','Iraq','1'),
								(372,'IE','IRL','Ireland','1'),
								(833,'IM','IMN','Isle of Man','1'),
								(376,'IL','ISR','Israel','1'),
								(380,'IT','ITA','Italy','1'),
								(388,'JM','JAM','Jamaica','1'),
								(392,'JP','JPN','Japan','1'),
								(832,'JE','JEY','Jersey','1'),
								(400,'JO','JOR','Jordan','1'),
								(398,'KZ','KAZ','Kazakhstan','1'),
								(404,'KE','KEN','Kenya','1'),
								(296,'KI','KIR','Kiribati','1'),
								(408,'KP','PRK','Korea, Democratic People\'s Republic of','1'),
								(410,'KR','KOR','Korea, Republic of','1'),
								(414,'KW','KWT','Kuwait','1'),
								(417,'KG','KGZ','Kyrgyzstan','1'),
								(418,'LA','LAO','Lao People\'s Democratic Republic','1'),
								(428,'LV','LVA','Latvia','1'),
								(422,'LB','LBN','Lebanon','1'),
								(426,'LS','LSO','Lesotho','1'),
								(430,'LR','LBR','Liberia','1'),
								(434,'LY','LBY','Libyan Arab Jamahiriya','1'),
								(438,'LI','LIE','Liechtenstein','1'),
								(440,'LT','LTU','Lithuania','1'),
								(442,'LU','LUX','Luxembourg','1'),
								(446,'MO','MAC','Macao','1'),
								(807,'MK','MKD','Macedonia, the former Yugoslav Republic of','1'),
								(450,'MG','MDG','Madagascar','1'),
								(454,'MW','MWI','Malawi','1'),
								(458,'MY','MYS','Malaysia','1'),
								(462,'MV','MDV','Maldives','1'),
								(466,'ML','MLI','Mali','1'),
								(470,'MT','MLT','Malta','1'),
								(584,'MH','MHL','Marshall Islands','1'),
								(474,'MQ','MTQ','Martinique','1'),
								(478,'MR','MRT','Mauritania','1'),
								(480,'MU','MUS','Mauritius','1'),
								(175,'YT','MYT','Mayotte','1'),
								(484,'MX','MEX','Mexico','1'),
								(583,'FM','FSM','Micronesia, Federated States of','1'),
								(498,'MD','MDA','Moldova','1'),
								(492,'MC','MCO','Monaco','1'),
								(496,'MN','MNG','Mongolia','1'),
								(499,'ME','MNE','Montenegro','1'),
								(500,'MS','MSR','Montserrat','1'),
								(504,'MA','MAR','Morocco','1'),
								(508,'MZ','MOZ','Mozambique','1'),
								(104,'MM','MMR','Myanmar','1'),
								(516,'NA','NAM','Namibia','1'),
								(520,'NR','NRU','Nauru','1'),
								(524,'NP','NPL','Nepal','1'),
								(528,'NL','NLD','Netherlands','1'),
								(530,'AN','ANT','Netherlands Antilles','1'),
								(540,'NC','NCL','New Caledonia','1'),
								(554,'NZ','NZL','New Zealand','1'),
								(558,'NI','NIC','Nicaragua','1'),
								(562,'NE','NER','Niger','1'),
								(566,'NG','NGA','Nigeria','1'),
								(570,'NU','NIU','Niue','1'),
								(574,'NF','NFK','Norfolk Island','1'),
								(580,'MP','MNP','Northern Mariana Islands','1'),
								(578,'NO','NOR','Norway','1'),
								(512,'OM','OMN','Oman','1'),
								(586,'PK','PAK','Pakistan','1'),
								(585,'PW','PLW','Palau','1'),
								(275,'PS','PSE','Palestinian Territory, Occupied','1'),
								(591,'PA','PAN','Panama','1'),
								(598,'PG','PNG','Papua New Guinea','1'),
								(600,'PY','PRY','Paraguay','1'),
								(604,'PE','PER','Peru','1'),
								(608,'PH','PHL','Philippines','1'),
								(612,'PN','PCN','Pitcairn','1'),
								(616,'PL','POL','Poland','1'),
								(620,'PT','PRT','Portugal','1'),
								(630,'PR','PRI','Puerto Rico','1'),
								(634,'QA','QAT','Qatar','1'),
								(638,'RE','REU','RŽunion','1'),
								(642,'RO','ROU','Romania','1'),
								(643,'RU','RUS','Russian Federation','1'),
								(646,'RW','RWA','Rwanda','1'),
								(652,'BL','BLM','Saint BarthŽlemy','1'),
								(654,'SH','SHN','Saint Helena','1'),
								(659,'KN','KNA','Saint Kitts and Nevis','1'),
								(662,'LC','LCA','Saint Lucia','1'),
								(663,'MF','MAF','Saint Martin (French part)','1'),
								(666,'PM','SPM','Saint Pierre and Miquelon','1'),
								(670,'VC','VCT','Saint Vincent and the Grenadines','1'),
								(882,'WS','WSM','Samoa','1'),
								(674,'SM','SMR','San Marino','1'),
								(678,'ST','STP','Sao Tome and Principe','1'),
								(682,'SA','SAU','Saudi Arabia','1'),
								(686,'SN','SEN','Senegal','1'),
								(688,'RS','SRB','Serbia','1'),
								(690,'SC','SYC','Seychelles','1'),
								(694,'SL','SLE','Sierra Leone','1'),
								(702,'SG','SGP','Singapore','1'),
								(703,'SK','SVK','Slovakia','1'),
								(705,'SI','SVN','Slovenia','1'),
								(90,'SB','SLB','Solomon Islands','1'),
								(706,'SO','SOM','Somalia','1'),
								(710,'ZA','ZAF','South Africa','1'),
								(239,'GS','SGS','South Georgia and the South Sandwich Islands','1'),
								(724,'ES','ESP','Spain','1'),
								(144,'LK','LKA','Sri Lanka','1'),
								(736,'SD','SDN','Sudan','1'),
								(740,'SR','SUR','Suriname','1'),
								(744,'SJ','SJM','Svalbard and Jan Mayen','1'),
								(748,'SZ','SWZ','Swaziland','1'),
								(752,'SE','SWE','Sweden','1'),
								(756,'CH','CHE','Switzerland','1'),
								(760,'SY','SYR','Syrian Arab Republic','1'),
								(158,'TW','TWN','Taiwan, Province of China','1'),
								(762,'TJ','TJK','Tajikistan','1'),
								(834,'TZ','TZA','Tanzania, United Republic of','1'),
								(764,'TH','THA','Thailand','1'),
								(626,'TL','TLS','Timor-Leste','1'),
								(768,'TG','TGO','Togo','1'),
								(772,'TK','TKL','Tokelau','1'),
								(776,'TO','TON','Tonga','1'),
								(780,'TT','TTO','Trinidad and Tobago','1'),
								(788,'TN','TUN','Tunisia','1'),
								(792,'TR','TUR','Turkey','1'),
								(795,'TM','TKM','Turkmenistan','1'),
								(796,'TC','TCA','Turks and Caicos Islands','1'),
								(798,'TV','TUV','Tuvalu','1'),
								(800,'UG','UGA','Uganda','1'),
								(804,'UA','UKR','Ukraine','1'),
								(784,'AE','ARE','United Arab Emirates','1'),
								(826,'GB','GBR','United Kingdom','1'),
								(840,'US','USA','United States','1'),
								(581,'UM','UMI','United States Minor Outlying Islands','1'),
								(858,'UY','URY','Uruguay','1'),
								(860,'UZ','UZB','Uzbekistan','1'),
								(548,'VU','VUT','Vanuatu','1'),
								(862,'VE','VEN','Venezuela','1'),
								(704,'VN','VNM','Viet Nam','1'),
								(92,'VG','VGB','Virgin Islands, British','1'),
								(850,'VI','VIR','Virgin Islands, U.S.','1'),
								(876,'WF','WLF','Wallis and Futuna','1'),
								(732,'EH','ESH','Western Sahara','1'),
								(887,'YE','YEM','Yemen','1'),
								(894,'ZM','ZMB','Zambia','1'),
								(716,'ZW','ZWE','Zimbabwe','1');");
        }

        if ($current < '1.66') {
        	$this->EE->db->query('INSERT INTO `exp_membrr_countries` (`country_id`, `iso2`, `iso3`, `name`, `available`)
				VALUES
					(895, \'CW\', \'CW\', \'Curaçao\', \'1\'),
					(896, \'SX\', \'SX\', \'Sint Maarten\', \'1\');');
        }

        if ($current < '1.67') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` ADD COLUMN `coupon` VARCHAR(250)');
        }

        if ($current < '1.68') {
        	$this->EE->db->query('CREATE TABLE `exp_membrr_temp` (
									  `temp_id` int(11) unsigned NOT NULL auto_increment,
									  `temp_data` text,
									  PRIMARY KEY  (`temp_id`)
									) ENGINE=MyISAM DEFAULT CHARSET=utf8;');
        }

        if ($current < '1.69') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_config` ADD COLUMN `update_email` TINYINT(1) NOT NULL');
        }

        if ($current < '1.71') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_config` ADD COLUMN `use_captcha` TINYINT(1) NOT NULL');
        }

        if ($current < '1.72') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` ADD COLUMN `card_last_four` INT(11) NOT NULL AFTER `next_charge_date`');
        }
        
        if ($current < '1.77') {
	        $this->EE->db->query('ALTER TABLE  `exp_membrr_address_book` ADD INDEX (`member_id`)');
        }
        
        if ($current < '1.79') {
        	$this->EE->db->query('ALTER TABLE `exp_membrr_subscriptions` ADD COLUMN `renewal` INT(11) NOT NULL');
        
	        $renewals = $this->EE->db->select('renewed_recurring_id')
	        						 ->where('renewed_recurring_id !=','0')
	        						 ->get('exp_membrr_subscriptions');
	        						 
			foreach ($renewals->result_array() as $renewal) {
				$this->EE->db->update('exp_membrr_subscriptions', array('renewal' => '1'), array('recurring_id' => $renewal['renewed_recurring_id']));
			}	        						 
        }

		return TRUE;
	}

	function install () {
		$sql = array();

        $sql[] = "INSERT INTO `exp_modules` (module_id,
                                           module_name,
                                           module_version,
                                           has_cp_backend)
                                           VALUES
                                           ('',
                                           'Membrr',
                                           '" . $this->version . "',
                                           'y')";

        $sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_config` (
				  `api_url` varchar(250) NOT NULL,
				  `api_id` varchar(80) NOT NULL,
				  `secret_key` varchar(80) NOT NULL,
				  `currency_symbol` varchar(10) NOT NULL,
				  `gateway` varchar(25) NOT NULL,
				  `update_email` TINYINT(1) NOT NULL,
				  `use_captcha` TINYINT(1) NOT NULL,
				  PRIMARY KEY  (`api_url`)
				  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_plans` (
				  `plan_id` int(11) auto_increment ,
				  `api_plan_id` int(11) NOT NULL,
				  `plan_name` varchar(250) NOT NULL,
				  `plan_description` text NOT NULL,
				  `plan_free_trial` int(11) NOT NULL,
				  `plan_occurrences` int(11) NOT NULL,
				  `plan_price` float NOT NULL,
				  `plan_initial_charge` float,
				  `plan_gateway` int(11) NOT NULL,
				  `plan_interval` int(11) NOT NULL,
				  `plan_import_date` datetime NOT NULL,
				  `plan_redirect_url` varchar(250) NOT NULL,
				  `plan_renewal_extend_from_end` tinyint(1) NOT NULL,
				  `plan_member_group` int(11) NOT NULL,
				  `plan_member_group_expire` int(11) NOT NULL,
				  `plan_active` tinyint(4) NOT NULL,
				  `plan_deleted` tinyint(4) NOT NULL,
				  PRIMARY KEY  (`plan_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_subscriptions` (
				 `recurring_id` int(11) PRIMARY KEY ,
				 `member_id` INT NOT NULL ,
				 `plan_id` INT NOT NULL ,
				 `subscription_price` float NOT NULL,
				 `date_created` DATETIME NOT NULL ,
				 `date_cancelled` DATETIME NOT NULL ,
				 `end_date` DATETIME NOT NULL ,
				 `next_charge_date` DATE NOT NULL ,
				 `card_last_four` INT(11) NOT NULL ,
				 `expired` TINYINT NOT NULL ,
				 `cancelled` TINYINT NOT NULL ,
				 `active` TINYINT NOT NULL ,
				 `renewed_recurring_id` INT(11) NOT NULL,
				 `expiry_processed` TINYINT NOT NULL,
				 `coupon` VARCHAR(250) NOT NULL,
				 `renewal` INT(11) NOT NULL
				 ) ENGINE = MYISAM DEFAULT CHARSET=latin1 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_payments` (
				 `payment_id` INT AUTO_INCREMENT PRIMARY KEY ,
				 `charge_id` INT NOT NULL ,
				 `recurring_id` INT NOT NULL ,
				 `amount` float NOT NULL ,
				 `date` DATETIME NOT NULL,
				 `refunded` TINYINT NOT NULL
				 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1000 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_address_book` (
				 `address_id` INT AUTO_INCREMENT PRIMARY KEY ,
				 `member_id` INT NOT NULL,
				 `first_name` varchar(250) NOT NULL,
				 `last_name` varchar(250) NOT NULL,
				 `address` varchar(250) NOT NULL,
				 `address_2` varchar(250) NOT NULL,
				 `city` varchar(250) NOT NULL,
				 `region` varchar(250) NOT NULL,
				 `region_other` varchar(250) NOT NULL,
				 `country` varchar(250) NOT NULL,
				 `postal_code` varchar(250) NOT NULL,
				 `company` varchar(250),
				 `phone` varchar(250)
				 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_channels` (
				 `protect_channel_id` INT AUTO_INCREMENT PRIMARY KEY ,
				 `channel_id` int(11) NOT NULL,
				 `plans` varchar(250) NOT NULL,
				 `posts` INT NOT NULL,
				 `expiration_status` INT NOT NULL,
				 `order_form` text NOT NULL
				 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ;";

		$sql[] = "CREATE TABLE IF NOT EXISTS `exp_membrr_channel_posts` (
				 `post_id` INT AUTO_INCREMENT PRIMARY KEY,
				 `channel_id` int(11) NOT NULL,
				 `channel_entry_id` INT NOT NULL,
				 `recurring_id` INT NOT NULL,
				 `active` TINYINT NOT NULL,
				 `channel_post_date` DATETIME NOT NULL
				 ) ENGINE = MYISAM DEFAULT CHARSET=utf8 ;";

		$sql[] = 'CREATE TABLE `exp_membrr_countries` (
								  `country_id` int(11) NOT NULL,
								  `iso2` varchar(2) NOT NULL,
								  `iso3` varchar(3) NOT NULL,
								  `name` varchar(255) NOT NULL,
								  `available` tinyint(1) NOT NULL,
								  PRIMARY KEY  (`country_id`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8;';

		$sql[] = "INSERT INTO `exp_actions` (action_id,
                                           class,
                                           method)
                                           VALUES
                                           ('',
                                           'Membrr',
                                           'post_notify');";

		$sql[] = "INSERT INTO `exp_membrr_countries` (`country_id`, `iso2`, `iso3`, `name`, `available`)
									VALUES
								(4,'AF','AFG','Afghanistan','1'),
								(248,'AX','ALA','Aland Islands','1'),
								(8,'AL','ALB','Albania','1'),
								(12,'DZ','DZA','Algeria','1'),
								(16,'AS','ASM','American Samoa','1'),
								(20,'AD','AND','Andorra','1'),
								(24,'AO','AGO','Angola','1'),
								(660,'AI','AIA','Anguilla','1'),
								(10,'AQ','ATA','Antarctica','1'),
								(28,'AG','ATG','Antigua and Barbuda','1'),
								(32,'AR','ARG','Argentina','1'),
								(51,'AM','ARM','Armenia','1'),
								(533,'AW','ABW','Aruba','1'),
								(36,'AU','AUS','Australia','1'),
								(40,'AT','AUT','Austria','1'),
								(31,'AZ','AZE','Azerbaijan','1'),
								(44,'BS','BHS','Bahamas','1'),
								(48,'BH','BHR','Bahrain','1'),
								(50,'BD','BGD','Bangladesh','1'),
								(52,'BB','BRB','Barbados','1'),
								(112,'BY','BLR','Belarus','1'),
								(56,'BE','BEL','Belgium','1'),
								(84,'BZ','BLZ','Belize','1'),
								(204,'BJ','BEN','Benin','1'),
								(60,'BM','BMU','Bermuda','1'),
								(64,'BT','BTN','Bhutan','1'),
								(68,'BO','BOL','Bolivia','1'),
								(70,'BA','BIH','Bosnia and Herzegovina','1'),
								(72,'BW','BWA','Botswana','1'),
								(74,'BV','BVT','Bouvet Island','1'),
								(76,'BR','BRA','Brazil','1'),
								(86,'IO','IOT','British Indian Ocean Territory','1'),
								(96,'BN','BRN','Brunei Darussalam','1'),
								(100,'BG','BGR','Bulgaria','1'),
								(854,'BF','BFA','Burkina Faso','1'),
								(108,'BI','BDI','Burundi','1'),
								(116,'KH','KHM','Cambodia','1'),
								(120,'CM','CMR','Cameroon','1'),
								(124,'CA','CAN','Canada','1'),
								(132,'CV','CPV','Cape Verde','1'),
								(136,'KY','CYM','Cayman Islands','1'),
								(140,'CF','CAF','Central African Republic','1'),
								(148,'TD','TCD','Chad','1'),
								(152,'CL','CHL','Chile','1'),
								(156,'CN','CHN','China','1'),
								(162,'CX','CXR','Christmas Island','1'),
								(166,'CC','CCK','Cocos (Keeling) Islands','1'),
								(170,'CO','COL','Colombia','1'),
								(174,'KM','COM','Comoros','1'),
								(178,'CG','COG','Congo','1'),
								(180,'CD','COD','Congo, Democratic Republic of the','1'),
								(184,'CK','COK','Cook Islands','1'),
								(188,'CR','CRI','Costa Rica','1'),
								(384,'CI','CIV','Côte d\'Ivoire','1'),
								(191,'HR','HRV','Croatia','1'),
								(192,'CU','CUB','Cuba','1'),
								(196,'CY','CYP','Cyprus','1'),
								(203,'CZ','CZE','Czech Republic','1'),
								(208,'DK','DNK','Denmark','1'),
								(262,'DJ','DJI','Djibouti','1'),
								(212,'DM','DMA','Dominica','1'),
								(214,'DO','DOM','Dominican Republic','1'),
								(218,'EC','ECU','Ecuador','1'),
								(818,'EG','EGY','Egypt','1'),
								(222,'SV','SLV','El Salvador','1'),
								(226,'GQ','GNQ','Equatorial Guinea','1'),
								(232,'ER','ERI','Eritrea','1'),
								(233,'EE','EST','Estonia','1'),
								(231,'ET','ETH','Ethiopia','1'),
								(238,'FK','FLK','Falkland Islands (Malvinas)','1'),
								(234,'FO','FRO','Faroe Islands','1'),
								(242,'FJ','FJI','Fiji','1'),
								(246,'FI','FIN','Finland','1'),
								(250,'FR','FRA','France','1'),
								(254,'GF','GUF','French Guiana','1'),
								(258,'PF','PYF','French Polynesia','1'),
								(260,'TF','ATF','French Southern Territories','1'),
								(266,'GA','GAB','Gabon','1'),
								(270,'GM','GMB','Gambia','1'),
								(268,'GE','GEO','Georgia','1'),
								(276,'DE','DEU','Germany','1'),
								(288,'GH','GHA','Ghana','1'),
								(292,'GI','GIB','Gibraltar','1'),
								(300,'GR','GRC','Greece','1'),
								(304,'GL','GRL','Greenland','1'),
								(308,'GD','GRD','Grenada','1'),
								(312,'GP','GLP','Guadeloupe','1'),
								(316,'GU','GUM','Guam','1'),
								(320,'GT','GTM','Guatemala','1'),
								(831,'GG','GGY','Guernsey','1'),
								(324,'GN','GIN','Guinea','1'),
								(624,'GW','GNB','Guinea-Bissau','1'),
								(328,'GY','GUY','Guyana','1'),
								(332,'HT','HTI','Haiti','1'),
								(334,'HM','HMD','Heard Island and McDonald Islands','1'),
								(336,'VA','VAT','Holy See (Vatican City State)','1'),
								(340,'HN','HND','Honduras','1'),
								(344,'HK','HKG','Hong Kong','1'),
								(348,'HU','HUN','Hungary','1'),
								(352,'IS','ISL','Iceland','1'),
								(356,'IN','IND','India','1'),
								(360,'ID','IDN','Indonesia','1'),
								(364,'IR','IRN','Iran, Islamic Republic of','1'),
								(368,'IQ','IRQ','Iraq','1'),
								(372,'IE','IRL','Ireland','1'),
								(833,'IM','IMN','Isle of Man','1'),
								(376,'IL','ISR','Israel','1'),
								(380,'IT','ITA','Italy','1'),
								(388,'JM','JAM','Jamaica','1'),
								(392,'JP','JPN','Japan','1'),
								(832,'JE','JEY','Jersey','1'),
								(400,'JO','JOR','Jordan','1'),
								(398,'KZ','KAZ','Kazakhstan','1'),
								(404,'KE','KEN','Kenya','1'),
								(296,'KI','KIR','Kiribati','1'),
								(408,'KP','PRK','Korea, Democratic People\'s Republic of','1'),
								(410,'KR','KOR','Korea, Republic of','1'),
								(414,'KW','KWT','Kuwait','1'),
								(417,'KG','KGZ','Kyrgyzstan','1'),
								(418,'LA','LAO','Lao People\'s Democratic Republic','1'),
								(428,'LV','LVA','Latvia','1'),
								(422,'LB','LBN','Lebanon','1'),
								(426,'LS','LSO','Lesotho','1'),
								(430,'LR','LBR','Liberia','1'),
								(434,'LY','LBY','Libyan Arab Jamahiriya','1'),
								(438,'LI','LIE','Liechtenstein','1'),
								(440,'LT','LTU','Lithuania','1'),
								(442,'LU','LUX','Luxembourg','1'),
								(446,'MO','MAC','Macao','1'),
								(807,'MK','MKD','Macedonia, the former Yugoslav Republic of','1'),
								(450,'MG','MDG','Madagascar','1'),
								(454,'MW','MWI','Malawi','1'),
								(458,'MY','MYS','Malaysia','1'),
								(462,'MV','MDV','Maldives','1'),
								(466,'ML','MLI','Mali','1'),
								(470,'MT','MLT','Malta','1'),
								(584,'MH','MHL','Marshall Islands','1'),
								(474,'MQ','MTQ','Martinique','1'),
								(478,'MR','MRT','Mauritania','1'),
								(480,'MU','MUS','Mauritius','1'),
								(175,'YT','MYT','Mayotte','1'),
								(484,'MX','MEX','Mexico','1'),
								(583,'FM','FSM','Micronesia, Federated States of','1'),
								(498,'MD','MDA','Moldova','1'),
								(492,'MC','MCO','Monaco','1'),
								(496,'MN','MNG','Mongolia','1'),
								(499,'ME','MNE','Montenegro','1'),
								(500,'MS','MSR','Montserrat','1'),
								(504,'MA','MAR','Morocco','1'),
								(508,'MZ','MOZ','Mozambique','1'),
								(104,'MM','MMR','Myanmar','1'),
								(516,'NA','NAM','Namibia','1'),
								(520,'NR','NRU','Nauru','1'),
								(524,'NP','NPL','Nepal','1'),
								(528,'NL','NLD','Netherlands','1'),
								(530,'AN','ANT','Netherlands Antilles','1'),
								(540,'NC','NCL','New Caledonia','1'),
								(554,'NZ','NZL','New Zealand','1'),
								(558,'NI','NIC','Nicaragua','1'),
								(562,'NE','NER','Niger','1'),
								(566,'NG','NGA','Nigeria','1'),
								(570,'NU','NIU','Niue','1'),
								(574,'NF','NFK','Norfolk Island','1'),
								(580,'MP','MNP','Northern Mariana Islands','1'),
								(578,'NO','NOR','Norway','1'),
								(512,'OM','OMN','Oman','1'),
								(586,'PK','PAK','Pakistan','1'),
								(585,'PW','PLW','Palau','1'),
								(275,'PS','PSE','Palestinian Territory, Occupied','1'),
								(591,'PA','PAN','Panama','1'),
								(598,'PG','PNG','Papua New Guinea','1'),
								(600,'PY','PRY','Paraguay','1'),
								(604,'PE','PER','Peru','1'),
								(608,'PH','PHL','Philippines','1'),
								(612,'PN','PCN','Pitcairn','1'),
								(616,'PL','POL','Poland','1'),
								(620,'PT','PRT','Portugal','1'),
								(630,'PR','PRI','Puerto Rico','1'),
								(634,'QA','QAT','Qatar','1'),
								(638,'RE','REU','RŽunion','1'),
								(642,'RO','ROU','Romania','1'),
								(643,'RU','RUS','Russian Federation','1'),
								(646,'RW','RWA','Rwanda','1'),
								(652,'BL','BLM','Saint BarthŽlemy','1'),
								(654,'SH','SHN','Saint Helena','1'),
								(659,'KN','KNA','Saint Kitts and Nevis','1'),
								(662,'LC','LCA','Saint Lucia','1'),
								(663,'MF','MAF','Saint Martin (French part)','1'),
								(666,'PM','SPM','Saint Pierre and Miquelon','1'),
								(670,'VC','VCT','Saint Vincent and the Grenadines','1'),
								(882,'WS','WSM','Samoa','1'),
								(674,'SM','SMR','San Marino','1'),
								(678,'ST','STP','Sao Tome and Principe','1'),
								(682,'SA','SAU','Saudi Arabia','1'),
								(686,'SN','SEN','Senegal','1'),
								(688,'RS','SRB','Serbia','1'),
								(690,'SC','SYC','Seychelles','1'),
								(694,'SL','SLE','Sierra Leone','1'),
								(702,'SG','SGP','Singapore','1'),
								(703,'SK','SVK','Slovakia','1'),
								(705,'SI','SVN','Slovenia','1'),
								(90,'SB','SLB','Solomon Islands','1'),
								(706,'SO','SOM','Somalia','1'),
								(710,'ZA','ZAF','South Africa','1'),
								(239,'GS','SGS','South Georgia and the South Sandwich Islands','1'),
								(724,'ES','ESP','Spain','1'),
								(144,'LK','LKA','Sri Lanka','1'),
								(736,'SD','SDN','Sudan','1'),
								(740,'SR','SUR','Suriname','1'),
								(744,'SJ','SJM','Svalbard and Jan Mayen','1'),
								(748,'SZ','SWZ','Swaziland','1'),
								(752,'SE','SWE','Sweden','1'),
								(756,'CH','CHE','Switzerland','1'),
								(760,'SY','SYR','Syrian Arab Republic','1'),
								(158,'TW','TWN','Taiwan, Province of China','1'),
								(762,'TJ','TJK','Tajikistan','1'),
								(834,'TZ','TZA','Tanzania, United Republic of','1'),
								(764,'TH','THA','Thailand','1'),
								(626,'TL','TLS','Timor-Leste','1'),
								(768,'TG','TGO','Togo','1'),
								(772,'TK','TKL','Tokelau','1'),
								(776,'TO','TON','Tonga','1'),
								(780,'TT','TTO','Trinidad and Tobago','1'),
								(788,'TN','TUN','Tunisia','1'),
								(792,'TR','TUR','Turkey','1'),
								(795,'TM','TKM','Turkmenistan','1'),
								(796,'TC','TCA','Turks and Caicos Islands','1'),
								(798,'TV','TUV','Tuvalu','1'),
								(800,'UG','UGA','Uganda','1'),
								(804,'UA','UKR','Ukraine','1'),
								(784,'AE','ARE','United Arab Emirates','1'),
								(826,'GB','GBR','United Kingdom','1'),
								(840,'US','USA','United States','1'),
								(581,'UM','UMI','United States Minor Outlying Islands','1'),
								(858,'UY','URY','Uruguay','1'),
								(860,'UZ','UZB','Uzbekistan','1'),
								(548,'VU','VUT','Vanuatu','1'),
								(862,'VE','VEN','Venezuela','1'),
								(704,'VN','VNM','Viet Nam','1'),
								(92,'VG','VGB','Virgin Islands, British','1'),
								(850,'VI','VIR','Virgin Islands, U.S.','1'),
								(876,'WF','WLF','Wallis and Futuna','1'),
								(732,'EH','ESH','Western Sahara','1'),
								(887,'YE','YEM','Yemen','1'),
								(894,'ZM','ZMB','Zambia','1'),
								(716,'ZW','ZWE','Zimbabwe','1');";

    	$sql[] = 'INSERT INTO `exp_membrr_countries` (`country_id`, `iso2`, `iso3`, `name`, `available`)
				VALUES
					(895, \'CW\', \'CW\', \'Curaçao\', \'1\'),
					(896, \'SX\', \'SX\', \'Sint Maarten\', \'1\');';

		$sql[] = 'CREATE TABLE `exp_membrr_temp` (
					  `temp_id` int(11) unsigned NOT NULL auto_increment,
					  `temp_data` text,
					  PRIMARY KEY  (`temp_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
					
		$sql[] = 'ALTER TABLE  `exp_membrr_address_book` ADD INDEX (`member_id`)';			

        foreach ($sql as $query)
        {
            $this->EE->db->query($query);
        }

        return TRUE;
	}

	function uninstall () {
		$sql = array();

        $sql[] = "DELETE FROM `exp_modules` WHERE `module_name`='Membrr'";
        $sql[] = "DROP TABLE `exp_membrr_plans`";
        $sql[] = "DROP TABLE `exp_membrr_config`";
        $sql[] = "DROP TABLE `exp_membrr_subscriptions`";
        $sql[] = "DROP TABLE `exp_membrr_address_book`";
        $sql[] = "DROP TABLE `exp_membrr_payments`";
        $sql[] = "DROP TABLE `exp_membrr_channels`";
        $sql[] = "DROP TABLE `exp_membrr_channel_posts`";
        $sql[] = "DROP TABLE `exp_membrr_countries`";
        $sql[] = "DROP TABLE `exp_membrr_temp`";
        $sql[] = 'DELETE FROM `exp_actions` WHERE `class` = \'Membrr\'';

        foreach ($sql as $query)
        {
            $this->EE->db->query($query);
        }

        return TRUE;
	}
}