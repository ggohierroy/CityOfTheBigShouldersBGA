
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- CityOfTheBigShoulders implementation : © Gabriel Gohier-Roy <ggohierroy@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- Example 2: add a custom field to the standard "player" table
-- ALTER TABLE `player` ADD `player_my_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `player` ADD `treasury` SMALLINT UNSIGNED NOT NULL DEFAULT '175';
ALTER TABLE `player` ADD `number_partners` SMALLINT UNSIGNED NOT NULL DEFAULT '2';
ALTER TABLE `player` ADD `current_number_partners` SMALLINT UNSIGNED NOT NULL DEFAULT '2';
ALTER TABLE `player` ADD `player_order` TINYINT(3) UNSIGNED;
ALTER TABLE `player` ADD `appeal_partner_gained` BOOLEAN NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `company_partner_gained` BOOLEAN NOT NULL DEFAULT '0';

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