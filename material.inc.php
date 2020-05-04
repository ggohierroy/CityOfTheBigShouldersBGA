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
    "has_asset" => true,
    "salesperson" => [0 => 80, 1 => 90, 2=> 100, 3 => 110],
    "salesperson_number" => 4,
    "factories" => [
      1 => [
        'workers'=> 1,
        'resources'=> ['wood', 'wood', 'steel'],
        'goods'=> 1,
        'automation'=> 1,
      ],
      2 => [
        'workers'=> 3,
        'resources'=> ['wood', 'wood', 'wood', 'coal'],
        'goods'=> 1,
        'automation'=> 3,
      ]
    ]
  ],
  "spalding" => [
    "name" => "A.G. Spalding and Bros.",
    "short_name" => "spalding",
    "type" => "dry_goods",
    "initial_appeal" => 1,
    "has_asset" => true,
    "salesperson" => [0 => 20, 1 => 30],
    "salesperson_number" => 2,
    "factories" => [
      1 => [
        'workers'=> 1,
        'resources'=> ['coal', 'livestock'],
        'goods'=> 2,
        'automation'=> 1,
      ],
      2 => [
        'workers'=> 2,
        'resources'=> ['steel', 'livestock'],
        'goods'=> 3,
        'automation'=> 2,
      ]
    ]
  ],"swift" => [
    "name" => "Swift and Company",
    "short_name" => "swift",
    "type" => "meat_packing",
    "initial_appeal" => 3,
    "has_asset" => true,
    "salesperson" => [0 => 50, 1 => 60, 2=> 70],
    "salesperson_number" => 3,
    "factories" => [
      1 => [
        'workers'=> 2,
        'resources'=> ['steel', 'livestock'],
        'goods'=> 2,
        'automation'=> 2,
      ],
      2 => [
        'workers'=> 3,
        'resources'=> ['steel', 'steel', 'livestock'],
        'goods'=> 1,
        'automation'=> 3,
      ]
    ]
  ],"fairbank" => [
    "name" => "N.K. Fairbank & Co.",
    "short_name" => "fairbank",
    "type" => "dry_goods",
    "initial_appeal" => 3,
    "has_asset" => false,
    "salesperson" => [0 => 40, 1 => 50, 2=> 60],
    "salesperson_number" => 3,
    "factories" => [
      1 => [
        'workers'=> 1,
        'resources'=> ['coal', 'coal'],
        'goods'=> 2,
        'automation'=> 1,
      ],
      2 => [
        'workers'=> 1,
        'resources'=> ['coal', 'coal', 'livestock'],
        'goods'=> 1,
        'automation'=> 1,
      ]
    ]
  ],"henderson" => [
    "name" => "C. M. Henderson & Co.",
    "short_name" => "henderson",
    "type" => "shoes",
    "initial_appeal" => 1,
    "has_asset" => false,
    "salesperson" => [0 => 20, 1 => 30, 2=> 40],
    "salesperson_number" => 3,
    "factories" => [
      1 => [
        'workers'=> 1,
        'resources'=> ['coal', 'livestock'],
        'goods'=> 3,
        'automation'=> 1,
      ],
      2 => [
        'workers'=> 2,
        'resources'=> ['coal', 'steel'],
        'goods'=> 2,
        'automation'=> 2,
      ],
      3 => [
        'workers'=> 1,
        'resources'=> ['coal', 'livestock'],
        'goods'=> 2,
        'automation'=> 1,
      ]
    ]
  ],"libby" => [
    "name" => "Libby, McNeill, & Libby",
    "short_name" => "libby",
    "type" => "food_and_dairy",
    "initial_appeal" => 1,
    "has_asset" => true,
    "salesperson" => [0 => 20, 1 => 30, 2=> 40],
    "salesperson_number" => 3,
    "factories" => [
      1 => [
        'workers'=> 2,
        'resources'=> ['steel', 'livestock'],
        'goods'=> 3,
        'automation'=> 2,
      ],
      2 => [
        'workers'=> 2,
        'resources'=> ['steel', 'livestock', 'livestock'],
        'goods'=> 3,
        'automation'=> 2,
      ]
    ]
  ],"anglo" => [
    "name" => "Anglo-American Provision Co.",
    "short_name" => "anglo",
    "type" => "meat_packing",
    "initial_appeal" => 2,
    "has_asset" => true,
    "salesperson" => [0 => 30, 1 => 40, 2=> 50],
    "salesperson_number" => 3,
    "factories" => [
      1 => [
        'workers'=> 1,
        'resources'=> ['coal', 'livestock'],
        'goods'=> 2,
        'automation'=> 1,
      ],
      2 => [
        'workers'=> 2,
        'resources'=> ['coal', 'coal', 'livestock', 'wood'],
        'goods'=> 3,
        'automation'=> 2,
      ]
    ]
  ],"cracker" => [
    "name" => "The Cracker Jack Co.",
    "short_name" => "cracker",
    "type" => "food_and_dairy",
    "initial_appeal" => 1,
    "has_asset" => false,
    "salesperson" => [0 => 30, 1 => 40, 2=> 100, 3 => 50],
    "salesperson_number" => 4,
    "factories" => [
      1 => [
        'workers'=> 1,
        'resources'=> ['wood'],
        'goods'=> 2,
        'automation'=> 1,
      ],
      2 => [
        'workers'=> 3,
        'resources'=> ['coal', 'steel'],
        'goods'=> 2,
        'automation'=> 3,
      ]
    ]
  ],"doggett" => [
    "name" => "Doggett, Basset & Hills Co.",
    "short_name" => "doggett",
    "type" => "shoes",
    "initial_appeal" => 1,
    "has_asset" => false,
    "salesperson" => [0 => 50, 1 => 60, 2=> 70],
    "salesperson_number" => 3,
    "factories" => [
      1 => [
        'workers'=> 2,
        'resources'=> ['coal', 'livestock'],
        'goods'=> 2,
        'automation'=> 2,
      ],
      2 => [
        'workers'=> 2,
        'resources'=> ['steel', 'livestock', 'wood', 'wood'],
        'goods'=> 1,
        'automation'=> 2,
      ]
    ]
  ],"elgin" => [
    "name" => "Elgin National Watch Co.",
    "short_name" => "elgin",
    "type" => "dry_goods",
    "initial_appeal" => 2,
    "has_asset" => false,
    "salesperson" => [0 => 50, 1 => 60, 2=> 70],
    "salesperson_number" => 3,
    "factories" => [
      1 => [
        'workers'=> 2,
        'resources'=> ['coal', 'steel'],
        'goods'=> 2,
        'automation'=> 2,
      ],
      2 => [
        'workers'=> 2,
        'resources'=> ['coal', 'steel', 'steel'],
        'goods'=> 1,
        'automation'=> 2,
      ]
    ]
  ],
];

$this->demand = [
  "demand1" => [
    "pips" => 1,
    "demand" => 0,
    "min_players" => 2
  ],
  "demand2" => [
    "pips" => 1,
    "demand" => 1,
    "min_players" => 2
  ],
  "demand3" => [
    "pips" => 1,
    "demand" => 1,
    "min_players" => 3
  ],
  "demand4" => [
    "pips" => 1,
    "demand" => 2,
    "min_players" => 2
  ],
  "demand5" => [
    "pips" => 1,
    "demand" => 2,
    "min_players" => 3
  ],
  "demand6" => [
    "pips" => 1,
    "demand" => 3,
    "min_players" => 4
  ],
  "demand7" => [
    "pips" => 2,
    "demand" => 3,
    "min_players" => 2
  ],
  "demand8" => [
    "pips" => 2,
    "demand" => 3,
    "min_players" => 2
  ],
  "demand9" => [
    "pips" => 2,
    "demand" => 2,
    "min_players" => 2
  ],
  "demand10" => [
    "pips" => 2,
    "demand" => 5,
    "min_players" => 2
  ],
  "demand11" => [
    "pips" => 2,
    "demand" => 4,
    "min_players" => 2
  ],
  "demand12" => [
    "pips" => 2,
    "demand" => 4,
    "min_players" => 3
  ],
  "demand13" => [
    "pips" => 3,
    "demand" => 5,
    "min_players" => 2
  ],
  "demand14" => [
    "pips" => 3,
    "demand" => 4,
    "min_players" => 2
  ],
  "demand15" => [
    "pips" => 3,
    "demand" => 4,
    "min_players" => 4
  ],
  "demand16" => [
    "pips" => 3,
    "demand" => 6,
    "min_players" => 2
  ],
  "demand17" => [
    "pips" => 3,
    "demand" => 5,
    "min_players" => 4
  ],
  "demand18" => [
    "pips" => 3,
    "demand" => 5,
    "min_players" => 2
  ],
  "demand19" => [
    "pips" => 4,
    "demand" => 6,
    "min_players" => 2
  ],
  "demand20" => [
    "pips" => 4,
    "demand" => 6,
    "min_players" => 2
  ],
  "demand21" => [
    "pips" => 4,
    "demand" => 6,
    "min_players" => 3
  ],
  "demand22" => [
    "pips" => 4,
    "demand" => 8,
    "min_players" => 4
  ],
  "demand23" => [
    "pips" => 4,
    "demand" => 7,
    "min_players" => 2
  ],
  "demand24" => [
    "pips" => 4,
    "demand" => 7,
    "min_players" => 4
  ]
];

$this->capital_asset = [
  "color_catalog" => [
    "starting" => false,
    "name" => clienttranslate("Color Catalog"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to increase its operating revenue by $60. Immediate bonus: gain 1 Worker for the Company that purchased the Asset.")
  ],
  "brand_recognition" => [
    "starting" => false,
    "name" => clienttranslate("Brand Recognition"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to advance 2 steps on the Appeal Track. Appeal Bonuses are gained immediately. Immediate bonus: Automate 1 Worker. A Worker must be employed prior to being automated. Appeal Bonuses are gained immediately.")
  ],
  "catalogue_empire" => [
    "starting" => false,
    "name" => clienttranslate("Catalogue Empire"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to increase its operating revenue by $80. Immediate bonus: gain 1 Worker for the Company that purchased the Asset.")
  ],
  "mail_order_catalogue" => [
    "starting" => false,
    "name" => clienttranslate("Mail Order Catalogue"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to increase its operating revenue by $40. Immediate bonus: gain 1 Worker for the Company that purchased the Asset.")
  ],
  "popular_partners" => [
    "starting" => false,
    "name" => clienttranslate("Popular Partners"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to advance 1 step on the Stock Track after the Production step in the Operating Phase. Immediate bonus: gain 1 Worker for the Company that purchased the Asset.")
  ],
  "price_protection" => [
    "starting" => false,
    "name" => clienttranslate("Price Protection"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to prevent moving backwards on the Stock Track if your company Withholds its profits, or if its shares are sold by another player. This bonus does not protect your share price when issuing shares to emergency fundraise. Immediate bonus: Automate 1 Worker and advance 2 spaces on the Appeal Track. A Worker must be employed prior to being automated and any Appeal Bonuses are gained immediately.")
  ],
  "backroom_deals" => [
    "starting" => false,
    "name" => clienttranslate("Backroom Deals"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to advance 1 step on the Stock Track after the Production step in the Operating Phase. Immediate bonus: gain 1 Worker for the Company that purchased the Asset.")
  ],
  "union_stockyards" => [
    "starting" => true,
    "name" => clienttranslate("Union Stockyards"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset and pay $10 to the bank to receive 2 livestock(pink) resources from Haymarket Square, if available. Immediate bonus: Automate 1 Worker. A Worker must be employed prior to being automated.")
  ],
  "regrigeration" => [
    "starting" => false,
    "name" => clienttranslate("Refrigeration"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to receive 2 livestock(pink) resources from Haymarket Square, if available. Immediate bonus: move your company two spaces on the Appeal track. Appeal Bonuses are gained immediately.")
  ],
  "foundry" => [
    "starting" => false,
    "name" => clienttranslate("Foundry"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to receive 1 steel(blue) and 1 wood(brown) resource from Haymarket Square, if available. Immediate bonus: advance your Company 2 spaces on the Appeal Track. Appeal Bonuses are gained immediately.")
  ],
  "workshop" => [
    "starting" => false,
    "name" => clienttranslate("Foundry"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to receive 1 steel(blue) and 1 coal(black) resource from Haymarket Square, if available. Immediate bonus: advance your Company 2 spaces on the Appeal Track. Appeal Bonuses are gained immediately.")
  ],
  "michigan_lumber" => [
    "starting" => true,
    "name" => clienttranslate("Michigan Lumber"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset and pay $10 to the Bank to receive 2 wood(brown) resources from Haymarket Square, if available. Immediate bonus: Automate 1 Worker. A Worker must be employed prior to being automated.")
  ],
  "abattoir" => [
    "starting" => false,
    "name" => clienttranslate("Abattoir"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to receive 1 wood(brown) and 1 coal(black) resources from Haymarket Square, if available. Immediate bonus: advance your Company 2 spaces on the Appeal Track. Appeal Bonuses are gained immediately.")
  ],
  "pennsylvania_coal" => [
    "starting" => true,
    "name" => clienttranslate("Pennsylvania Coal"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset and pay $10 to the Bank to receive 2 coal(black) resources from Haymarket Square, if available. Immediate bonus: Automate 1 Worker. A Worker must be employed prior to being automated.")
  ],
  "cincinnati_steel" => [
    "starting" => true,
    "name" => clienttranslate("Cincinnati Steel"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset and pay $10 to the Bank to receive 2 steel(blue) resources from Haymarket Square, if available. Immediate bonus: Automate 1 Worker. A Worker must be employed prior to being automated.")
  ],
  "brilliant_marketing" => [
    "starting" => true,
    "name" => clienttranslate("Brilliant Marketing"),
    "tooltip" => clienttranslate("Each Decade your Company may exhaust this asset to go up one space on the Appeal Track. Immediate bonus: Automate 1 Worker. A Worker must be employed prior to being automated. Appeal Bonuses are gained immediately.")
  ],
];

$this->general_action_spaces = [
  "job_market_worker" => [
    "player_limit" => false,
    "cost" => 999,
    "payment" => "companytobank"
  ],
  "advertising" => [
    "player_limit" => false,
    "cost" => 20,
    "payment" => "companytobank"
  ],
  "fundraising_40" => [
    "player_limit" => false,
    "cost" => 40,
    "payment" => "banktocompany"
  ],
  "fundraising_60" => [
    "player_limit" => true,
    "cost" => 60,
    "payment" => "banktocompany"
  ],
  "fundraising_80" => [
    "player_limit" => true,
    "cost" => 80,
    "payment" => "banktocompany"
  ],
  "hire_manager" => [
    "player_limit" => true,
    "cost" => 60,
    "payment" => "companytobank"
  ],
  "hire_salesperson" => [
    "player_limit" => true,
    "cost" => 70,
    "payment" => "companytobank"
  ],
  "capital_investment" => [
    "player_limit" => true,
    "cost" => 999,
    "payment" => "companytobank"
  ],
  "extra_dividends" => [
    "player_limit" => false,
    "cost" => 100,
    "payment" => "companytoshareholders"
  ]
];

$this->building = [
  "building1" => [ //capital asset discount
    "pips" => 1,
    "min_players" => 2,
    "number_of_workers" => 2,
    "cost" => 20,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building may purchase a Capital Asset at a $10 discount.")
  ],
  "building2" => [ //salesperson
    "pips" => 1,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 20,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 Salesperson.")
  ],
  "building3" => [ // wood + livestock
    "pips" => 1,
    "min_players" => 3,
    "number_of_workers" => 0,
    "cost" => 20,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 wood(brown) and 1 livestock(pink) resource from Haymarket Square, if available.")
  ],
  "building4" => [ // appeal +1
    "pips" => 1,
    "min_players" => 4,
    "number_of_workers" => 2,
    "cost" => 10,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will move up 1 space on the Appeal Track. Receive any bonuses immediately.")
  ],
  "building5" => [ // wood + coal
    "pips" => 1,
    "min_players" => 3,
    "number_of_workers" => 0,
    "cost" => 20,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 wood(brown) and 1 coal(black) resource from Haymarket Square, if available.")
  ],
  "building6" => [ // automation
    "pips" => 1,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building may automate 1 Worker in any of their factories. A Worker must be employed in the space prior to being automated.")
  ],
  "building7" => [ // wood + steel
    "pips" => 1,
    "min_players" => 3,
    "number_of_workers" => 0,
    "cost" => 20,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 steel(blue) and 1 wood(brown) resource from Haymarket Square, if available.")
  ],
  "building8" => [ // different resources
    "pips" => 1,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 10,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive two unlike resources from Haymarket Square, if available.")
  ],
  "building9" => [ // appeal +2
    "pips" => 1,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 10,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will move up 2 spaces on the Appeal Track. Receive any bonuses immediately.")
  ],
  "building10" => [ // produce good
    "pips" => 1,
    "min_players" => 2,
    "number_of_workers" => 2,
    "cost" => 20,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building immediately produces 1 good.")
  ],
  "building11" => [ // manager
    "pips" => 1,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 20,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive a Manager.")
  ],
  "building12" => [ // same resources
    "pips" => 1,
    "min_players" => 4,
    "number_of_workers" => 2,
    "cost" => 10,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive two like resources from Haymarket Square, if available.")
  ],
  "building13" => [ // livestock + steel
    "pips" => 2,
    "min_players" => 2,
    "number_of_workers" => 2,
    "cost" => 20,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 livestock(pink) and 1 steel(blue) resource from Haymarket Square, if available.")
  ],
  "building14" => [ // manager
    "pips" => 2,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 30,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 Manager.")
  ],
  "building15" => [ // coal + steel
    "pips" => 2,
    "min_players" => 2,
    "number_of_workers" => 2,
    "cost" => 20,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 coal(black) and 1 steel(blue) resource from Haymarket Square, if available.")
  ],
  "building16" => [ // appeal + 2
    "pips" => 2,
    "min_players" => 3,
    "number_of_workers" => 1,
    "cost" => 30,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will move up 2 spaces on the Appeal Track. Receive any bonuses immediately.")
  ],
  "building17" => [ // coal + livestock
    "pips" => 2,
    "min_players" => 2,
    "number_of_workers" => 2,
    "cost" => 20,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 coal(black) and 1 livestock(pink) resource from Haymarket Square, if available.")
  ],
  "building18" => [ // different resources
    "pips" => 2,
    "min_players" => 3,
    "number_of_workers" => 1,
    "cost" => 10,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive two unlike resources from Haymarket Square, if available.")
  ],
  "building19" => [ // capital asset discount
    "pips" => 2,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 20,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building may purchase a Capital Asset at a $20 discount.")
  ],
  "building20" => [ // same resources
    "pips" => 2,
    "min_players" => 3,
    "number_of_workers" => 2,
    "cost" => 10,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive two like resources from Haymarket Square, if available.")
  ],
  "building21" => [ // double automation
    "pips" => 2,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 90,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building may automate 2 Workers in any of their factories. A Worker must be employed in each space prior to being automated.")
  ],
  "building22" => [ // salesperson
    "pips" => 2,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 Salesperson.")
  ],
  "building23" => [ // double manager
    "pips" => 2,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 60,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 2 Managers.")
  ],
  "building24" => [ // automation
    "pips" => 2,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building may automate 1 Worker in any of their factories. A Worker must be employed in the space prior to being automated.")
  ],
  "building25" => [ // double salesperson
    "pips" => 2,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 80,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 2 Salespeople.")
  ],
  "building26" => [ // double worker
    "pips" => 2,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 50,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building receives 2 Workers from the General Supply.")
  ],
  "building27" => [ // produce 2 goods
    "pips" => 2,
    "min_players" => 3,
    "number_of_workers" => 2,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("immediately produces 2 Goods.")
  ],
  "building28" => [ // appeal + 3
    "pips" => 2,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 50,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will move up 3 spaces on the Appeal Track. Receive any bonuses immediately.")
  ],
  "building29" => [ // 150 dividend
    "pips" => 3,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 30,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The company that uses this Building pays $150 from its treasury to its shareholders at $15 per share. If the company does not have $150 in its treasury it cannot use this space. Adjust the Company's share value on the Stock Track after dividends are paid.")
  ],
  "building30" => [ // 200 dividend
    "pips" => 3,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 40,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The company that uses this Building pays $200 from its treasury to its shareholders at $20 per share. If the company does not have $200 in its treasury it cannot use this space. Adjust the Company's share value on the Stock Track after dividends are paid.")
  ],
  "building31" => [ // 250 dividend
    "pips" => 3,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 50,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The company that uses this Building pays $250 from its treasury to its shareholders at $25 per share. If the company does not have $250 in its treasury it cannot use this space. Adjust the Company's share value on the Stock Track after dividends are paid.")
  ],
  "building32" => [ // 300 dividend
    "pips" => 3,
    "min_players" => 4,
    "number_of_workers" => 0,
    "cost" => 60,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The company that uses this Building pays $300 from its treasury to its shareholders at $30 per share. If the company does not have $300 in its treasury it cannot use this space. Adjust the Company's share value on the Stock Track after dividends are paid.")
  ],
  "building33" => [ // double salesperson
    "pips" => 3,
    "min_players" => 3,
    "number_of_workers" => 0,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 2 Salespeople.")
  ],
  "building34" => [ // 2 coal + 1 livestock
    "pips" => 3,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 2 coal(black) and 1 livestock(pink) resource from Haymarket Square, if available.")
  ],
  "building35" => [ // double manager
    "pips" => 3,
    "min_players" => 3,
    "number_of_workers" => 0,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 2 Managers.")
  ],
  "building36" => [ // 2 livestock + 1 steel
    "pips" => 3,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 2 livestock(pink) and 1 steel(blue) resource from Haymarket Square, if available.")
  ],
  "building37" => [ // 2 wood + 1 coal
    "pips" => 3,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 2 wood(brown) and 1 coal(black) resource from Haymarket Square, if available.")
  ],
  "building38" => [ // 2 steel + 1 wood
    "pips" => 3,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 2 steel(blue) and 1 wood(brown) resource from Haymarket Square, if available.")
  ],
  "building39" => [ // appeal +3
    "pips" => 3,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 60,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will move up 3 spaces on the Appeal Track. Receive any bonuses immediately.")
  ],
  "building40" => [ // capital asset discount
    "pips" => 3,
    "min_players" => 2,
    "number_of_workers" => 2,
    "cost" => 40,
    "payment" => "banktoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building may purchase a Capital Asset at a $30 discount.")
  ],
  "building41" => [ // produce 3 goods
    "pips" => 3,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 60,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will immediately produce 3 Goods.")
  ],
  "building42" => [ // double automation
    "pips" => 3,
    "min_players" => 2,
    "number_of_workers" => 1,
    "cost" => 90,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building may automate 2 Workers in any of their factories. A Worker must be employed in each space prior to being automated.")
  ],
  "building43" => [ // worker + manager
    "pips" => 3,
    "min_players" => 3,
    "number_of_workers" => 0,
    "cost" => 40,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 Worker and 1 Manager from the General Supply to place in any of their factories.")
  ],
  "building44" => [ // worker + salesperson
    "pips" => 3,
    "min_players" => 3,
    "number_of_workers" => 0,
    "cost" => 50,
    "payment" => "companytoplayer",
    "tooltip" => clienttranslate("The Company that uses this Building will receive 1 Worker and 1 Salesperson from the General Supply to place in any of their factories.")
  ]
];

$this->goal = [
  "goal1" => [
    "tooltip" => clienttranslate("The player with the most Managers, across all of their companies, will receive $200.")
  ],
  "goal2" => [
    "tooltip" => clienttranslate("The player with the most Salespeople, across all of their companies, will receive $200.")
  ],
  "goal3" => [
    "tooltip" => clienttranslate("The player whose companies won the most Capital Assets will receive $200.")
  ],
  "goal4" => [
    "tooltip" => clienttranslate("The player who currently has the most Workers employed will receive $200.")
  ],
  "goal5" => [
    "tooltip" => clienttranslate("The player who has the most automation tokens activated across all of their companies, will receive $200.")
  ],
  "goal6" => [
    "tooltip" => clienttranslate("Players with the companies that are highest on the Appeal Track receive $200. For the purposes of this goal, tokens occuppying the same space are tied.")
  ],
  "goal7" => [
    "tooltip" => clienttranslate("The player who has the most money in their Personal Treasury, prior to cashing out their Stock Certificates, will receive $200.")
  ],
  "goal8" => [
    "tooltip" => clienttranslate("The player who has the most Partners will receive $200.")
  ],
  "goal9" => [
    "tooltip" => clienttranslate("The player who owns the most 10% Stock Certificates will receive $200.")
  ],
  "goal10" => [
    "tooltip" => clienttranslate("The player who owns the most 20% Stock Certificates will receive $200.")
  ],
];