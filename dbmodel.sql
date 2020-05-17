
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- CityOfTheBigShoulders implementation : © Gabriel Gohier-Roy <ggohierroy@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

ALTER TABLE `player` ADD `treasury` SMALLINT UNSIGNED NOT NULL DEFAULT '175';
ALTER TABLE `player` ADD `number_partners` SMALLINT UNSIGNED NOT NULL DEFAULT '2';
ALTER TABLE `player` ADD `current_number_partners` SMALLINT UNSIGNED NOT NULL DEFAULT '2';
ALTER TABLE `player` ADD `player_order` TINYINT(3) UNSIGNED;
ALTER TABLE `player` ADD `appeal_partner_gained` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `company_partner_gained` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `initial_company_id` INT(10) UNSIGNED;

CREATE TABLE IF NOT EXISTS `company` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `treasury` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    `income` SMALLINT UNSIGNED NOT NULL DEFAULT '0',
    `short_name` VARCHAR(16) NOT NULL,
    `owner_id` INT(10) UNSIGNED NOT NULL,
    `share_value_step` TINYINT(3) UNSIGNED,
    `next_company_id` INT(10) UNSIGNED,
    `appeal` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
    `extra_goods` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',

    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `sold_shares` (
    `player_id` INT(10) UNSIGNED NOT NULL,
    `round` TINYINT(3) UNSIGNED NOT NULL,
    `company_short_name` VARCHAR(16) NOT NULL,
    PRIMARY KEY (`player_id`, `round`, `company_short_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `primary_type` varchar(16) NOT NULL,
  `owner_type` varchar(16),
  `card_type` varchar(30) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(40) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;