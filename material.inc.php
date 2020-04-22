<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CityOfTheBigShoulders implementation : © Gabriel Gohier-Roy <ggohierroy@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * CityOfTheBigShoulders game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/

$this->companies = [
  "brunswick" => [
    "name" => "Brunswick-Balke-Collender Co.",
    "short_name" => "brunswick",
    "type" => "dry_goods",
    "initial_appeal" => 1,
    "factories" => [
    ]
  ],
  "spalding" => [
    "name" => "A.G. Spalding and Bros.",
    "short_name" => "spalding",
    "type" => "dry_goods",
    "initial_appeal" => 1,
    "factories" => [
    ]
  ],"swift" => [
    "name" => "Swift and Company",
    "short_name" => "swift",
    "type" => "meat_packing",
    "initial_appeal" => 3,
    "factories" => [
    ]
  ],"fairbank" => [
    "name" => "N.K. Fairbank & Co.",
    "short_name" => "fairbank",
    "type" => "dry_goods",
    "initial_appeal" => 3,
    "factories" => [
    ]
  ],"henderson" => [
    "name" => "C. M. Henderson & Co.",
    "short_name" => "henderson",
    "type" => "shoes",
    "initial_appeal" => 1,
    "factories" => [
    ]
  ],"libby" => [
    "name" => "Libby, McNeill, & Libby",
    "short_name" => "libby",
    "type" => "food_and_dairy",
    "initial_appeal" => 1,
    "factories" => [
    ]
  ],"anglo" => [
    "name" => "Anglo-American Provision Co.",
    "short_name" => "anglo",
    "type" => "meat_packing",
    "initial_appeal" => 2,
    "factories" => [
    ]
  ],"cracker" => [
    "name" => "The Cracker Jack Co.",
    "short_name" => "cracker",
    "type" => "food_and_dairy",
    "initial_appeal" => 1,
    "factories" => [
    ]
  ],"doggett" => [
    "name" => "Doggett, Basset & Hills Co.",
    "short_name" => "doggett",
    "type" => "shoes",
    "initial_appeal" => 1,
    "factories" => [
    ]
  ],"elgin" => [
    "name" => "Elgin National Watch Co.",
    "short_name" => "elgin",
    "type" => "dry_goods",
    "initial_appeal" => 2,
    "factories" => [
    ]
  ],
];


