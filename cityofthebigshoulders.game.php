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
  * cityofthebigshoulders.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class CityOfTheBigShoulders extends Table
{
    const STEP_TO_VALUE = [0=>10, 1=>15, 2=>20, 3=>25, 4=>35, 5=>40, 6=>50, 7=>60, 8=>80, 9=>100, 10=>120, 11=>140, 12=>160, 13=>190, 14=>220, 15=>250, 16=>280, 17=>320, 18=>360, 19=>400, 20=>450];
    const REFILL_AMOUNT = [0 => 3, 1 => 4, 2 => 4, 3 => 5, 5 => 6];
    const BONUS_LOOKUP = [0 => 0, 1 => 0, 2 => 0, 3 => 1, 4 => 1, 5 => 2, 6 => 2, 7 => 3, 8 => 3, 9 => 4, 10 => 4, 11 => 5, 12 => 5, 13 => 6, 14 => 6, 15 => 7, 16 => 8];
    const BONUS_NAME = [1 => 'worker', 2 => 'manager', 3 => 'automation', 4 => 'partner', 5 => 'good', 6 => 'bump', 7 => 'good', 8 => 'bump'];

	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        
        self::initGameStateLabels( array( 
            "turns_this_phase" => 10,
            "round" => 11,
            "phase" => 12,
            "priority_deal_player_id" => 13,
            "consecutive_passes" => 14,
            "next_company_id" => 15, // we need this because the current company can change appeal during operation phase
            "current_company_id" => 16, // we need this to return company name in the operation phase args
            "last_factory_produced" => 17,
            "resources_gained" => 19,
            "bonus_company_id" => 20,
            "next_appeal_bonus" => 21,
            "final_appeal_bonus" => 22,
            //"round_marker" => 10,
            //"phase_marker" => 11,
            //"workers_in_market" => 12,
            //"priority_deal_player_id" => 13
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ) ); 
        
        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );
	}
	
    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "cityofthebigshoulders";
    }	

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {    
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // initialize card object
        $this->cards = self::getNew( "module.common.deck" );
        $this->cards->init( "card" );

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();
        
        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );
        self::setGameStateInitialValue( 'turns_this_phase', 0 );
        self::setGameStateInitialValue( 'round', 0 );
        self::setGameStateInitialValue( 'consecutive_passes', 0 );
        self::setGameStateInitialValue( 'next_company_id', 0 );
        self::setGameStateInitialValue( 'current_company_id', 0 );
        self::setGameStateInitialValue( 'last_factory_produced', 0 );
        self::setGameStateInitialValue( 'phase', 0 );
        self::setGameStateInitialValue( 'priority_deal_player_id', 0 );
        self::setGameStateInitialValue( 'bonus_company_id', 0 );
        self::setGameStateInitialValue( "next_appeal_bonus", 0);
        self::setGameStateInitialValue( "final_appeal_bonus", 0);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // setup the initial game situation here
        $this->initializeDecks($players);

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();
    
        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!
    
        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id AS id, player_score AS score, treasury AS treasury, number_partners AS number_partners, current_number_partners AS current_number_partners FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // Gather all information about current game situation (visible by player $current_player_id).
        $sql = "SELECT id AS id, treasury AS treasury, appeal AS appeal, next_company_id AS next_company_id, share_value_step AS share_value_step, owner_id AS owner_id, short_name AS short_name FROM company";
        $owned_companies = self::getCollectionFromDb( $sql );
        $result['owned_companies'] = $owned_companies;
        $result['company_order'] = self::getCurrentCompanyOrder($owned_companies);

        $result['all_buildings'] = $this->building;
        $result['all_companies'] = $this->companies;
        $result['general_action_spaces'] = $this->general_action_spaces;
        $result['all_capital_assets'] = $this->capital_asset;
        $result['demand'] = $this->demand;
        $result['goals'] = $this->goal;

        // gather all items in card table that are visible to the player
        $sql = "SELECT card_id AS card_id, owner_type AS owner_type, primary_type AS primary_type, card_type AS card_type, card_type_arg AS card_type_arg, card_location AS card_location, card_location_arg AS card_location_arg
            FROM card
            WHERE
                (card_location <> 'demand_deck' AND
                card_location <> 'asset_deck' AND
                card_location <> 'era_2' AND
                card_location <> 'era_3' AND
                card_location <> 'resource_bag' AND
                card_location <> 'discard' AND
                primary_type <> 'building')
            OR
                ((card_location LIKE 'player_$current_player_id%' OR card_location LIKE 'building_track_%') AND
                primary_type = 'building')";
        $result['items'] = self::getCollectionFromDb( $sql );

        // add a counter for each company (because all counters must exist on setup)
        foreach($this->companies as $short_name => $company){
            $short_name = $company['short_name'];
            $money_id = "money_${short_name}";
            $result ['counters'] [$money_id] = array ('counter_name' => $money_id, 'counter_value' => 0 );
        }

        // update counter for owned companies
        foreach($owned_companies as $id => $company){
            $short_name = $company['short_name'];
            $money_id = "money_${short_name}";
            $result ['counters'] [$money_id] = array ('counter_name' => $money_id, 'counter_value' => $company['treasury'] );
        }

        foreach($result['players'] as $player_id => $player)
        {
            $money_id = "money_${player_id}";
            $partner_id = "partner_${player_id}";
            $partner_current = "partner_current_${player_id}";

            $result ['counters'] [$money_id] = array ('counter_name' => "money_${player_id}", 'counter_value' => $player['treasury'] );
            $result ['counters'] [$partner_current] = array ('counter_name' => $partner_current, 'counter_value' => $player['current_number_partners'] );
            $result ['counters'] [$partner_id] = array ('counter_name' => $partner_id, 'counter_value' => $player['number_partners'] );
        }
  
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


//////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function gainPartner($player_id, $partner_type)
    {
        switch($partner_type)
        {
            case 'round':
                $players = self::getObjectListFromDB("SELECT player_id, number_partners, current_number_partners FROM player");
                $counters = [];

                foreach($players as $player)
                {
                    $player_id = $player['player_id'];
                    self::addCounter($counters, "partner_${player_id}", $player['number_partners'] + 1);
                    self::addCounter($counters, "partner_current_${player_id}", $player['current_number_partners'] + 1);
                }

                self::DbQuery("UPDATE player SET number_partners = number_partners + 1, current_number_partners = current_number_partners + 1");

                self::notifyAllPlayers("countersUpdated", clienttranslate("All players receive a new partner"), [
                    'counters' => $counters
                ]);
                break;
            case 'appeal':
                $player = self::getObjectListFromDB(
                    "SELECT player_name, player_id, number_partners, current_number_partners, appeal_partner_gained FROM player WHERE player_id = $player_id");
                
                if($player['appeal_partner_gained'] == true)
                    return;

                $counters = [];
                self::addCounter($counters, "partner_${player_id}", $player['number_partners'] + 1);
                self::addCounter($counters, "partner_current_${player_id}", $player['current_number_partners'] + 1);

                self::DbQuery("UPDATE player SET 
                    number_partners = number_partners + 1,
                    current_number_partners = current_number_partners + 1,
                    appeal_partner_gained = 1
                    WHERE player_id = $player_id");

                self::notifyAllPlayers("countersUpdated", clienttranslate("${player_name} receives a new partner"), [
                    'counters' => $counters,
                    'player_name' => $player['player_name']
                ]);
                break;
            case 'company':
                $player = self::getObjectListFromDB(
                    "SELECT player_name, player_id, number_partners, current_number_partners, company_partner_gained FROM player WHERE player_id = $player_id");
                
                if($player['company_partner_gained'] == true)
                    return;

                $counters = [];
                self::addCounter($counters, "partner_${player_id}", $player['number_partners'] + 1);
                self::addCounter($counters, "partner_current_${player_id}", $player['current_number_partners'] + 1);

                self::DbQuery("UPDATE player SET 
                    number_partners = number_partners + 1,
                    current_number_partners = current_number_partners + 1,
                    company_partner_gained = 1
                    WHERE player_id = $player_id");

                self::notifyAllPlayers("countersUpdated", clienttranslate("${player_name} receives a new partner"), [
                    'counters' => $counters,
                    'player_name' => $player['player_name']
                ]);
                break;
        }
    }

    function gainGood($company_short_name)
    {
        $self::DbQuery("UPDATE company SET extra_goods = extra_goods + 1 WHERE short_name = '$company_short_name");

        self::notifyAllPlayers( "shareValueChange", clienttranslate( '${company_name} receives an Appeal Bonus Goods token' ), array(
            'company_short_name' => $company_short_name,
            'company_name' => self::getCompanyName($company_short_name)
        ));
    }

    function increaseShareValue($short_name, $value)
    {
        $sql = "SELECT id, treasury, share_value_step FROM company WHERE short_name = '$short_name'";
        $company = self::getNonEmptyObjectFromDB($sql);
        $company_id = $company['id'];   
        $share_value_step = $company['share_value_step'];

        // change value of share
        $new_share_value_step = $share_value_step + $value;
        if($new_share_value_step > 20)
            $new_share_value_step = 20;
        if($new_share_value_step < 0)
            $new_share_value_step = 0;

        self::DbQuery("UPDATE company SET share_value_step = $new_share_value_step WHERE id = $company_id");

        $new_share_value = self::getShareValue($new_share_value_step);
        self::notifyAllPlayers( "shareValueChange", clienttranslate( 'The share value of ${company_name} increased to $${share_value}' ), array(
            'company_short_name' => $short_name,
            'company_name' => self::getCompanyName($short_name),
            'share_value' => $new_share_value,
            'share_value_step' => $new_share_value_step,
            'previous_share_value_step' => $share_value_step
        ));
    }

    function increaseIncome($short_name, $amount)
    {
        self::DbQuery("UPDATE company SET income = income + $amount WHERE short_name = '$short_name'");

        // TODO: notify if income is shown
    }

    function gainResourcesAsset($company_short_name, $company_id, $resources_gained)
    {
        $resource_names = implode("','", $resources_gained);
        $resources = self::getObjectListFromDB("SELECT card_id, card_type FROM card WHERE card_location = 'haymarket' AND primary_type = 'resource' AND card_type IN ('$resource_names')");

        $resources_returned = [];
        $resource_ids = [];
        foreach($resources_gained as $resource_gained)
        {
            foreach($resources as $index => $resource)
            {
                if($resource['card_type'] == $resource_gained)
                {
                    $resource['from'] = 'haymarket';
                    $resource['card_location'] = $company_short_name;
                    $resources_returned[] = $resource;
                    $resource_ids[] = $resource['card_id'];
                    $resources[$index]['card_type'] = 'nothing';
                    break;
                }
            }
        }

        $resource_ids = implode(',', $resource_ids);
        // update resources location
        self::DbQuery("UPDATE card SET 
            owner_type = 'company',
            card_location = '$company_short_name',
            card_location_arg = $company_id
            WHERE card_id IN ($resource_ids)");

        $count = count($resources_returned);
        self::notifyAllPlayers( "resourcesBought", clienttranslate( '${company_name} receives ${count} resources' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'count' => $count,
            'resource_ids' => $resources_returned
        ) );
    }

    function gainResources($company_short_name, $company_id, $resources_gained, $resource_ids)
    {
        // get the resources and make sure they are in haymarket
        $resources = self::getObjectListFromDB("SELECT card_location, card_type, card_id FROM card WHERE card_id IN ($resource_ids)");
        foreach($resources as $index => $resource)
        {
            // check that resource is in haymarket
            if($resource['card_location'] != 'haymarket')
                throw new BgaVisibleSystemException("Resource is not in haymarket");
            
            $found = false;
            foreach($resources_gained as $index => $resource_gained)
            {
                if($resource['card_type'] == $resource_gained)
                {
                    $found = true;
                    $resources_gained[$index] == null;
                    break;
                }
            }

            if(!$found)
                throw new BgaVisibleSystemException("Not a resource that can be gained from this building");

            $resources[$index]['from'] = $resources[$index]['card_location'];
            $resources[$index]['card_location'] = $company_short_name;
        }

        // update resources location
        self::DbQuery("UPDATE card SET 
            owner_type = 'company',
            card_location = '$company_short_name',
            card_location_arg = $company_id
            WHERE card_id IN ($resource_ids)");

        $count = count($resources);
        self::notifyAllPlayers( "resourcesBought", clienttranslate( '${company_name} receives ${count} resources' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'count' => $count,
            'resource_ids' => $resources
        ) );
    }

    function refillDemand()
    {
        // get goods on demand
        $goods = self::getObjectListFromDB(
            "SELECT card_id, card_location FROM card WHERE primary_type = 'good' AND card_location LIKE 'demand%'");
        
        $goods_by_location = [];
        foreach($goods as $good)
        {
            $location = $good['card_location'];
            if(!isset($goods_by_location[$location]))
            {
                $goods_by_location[$location] = [];
                $goods_by_location[$location][] = $good['card_id'];
            }
            else
            {
                $goods_by_location[$location][] = $good['card_id'];
            }
        }

        // get demand tiles
        $demands = self::getObjectListFromDB(
            "SELECT card_id, card_type, card_location FROM card 
            WHERE primary_type = 'demand' AND (card_location <> 'demand_deck' AND card_location <> 'discard')");
        
        $demand_by_location = [];
        // check if demand fulfilled, if it is, discard it
        foreach($demands as $index => $demand)
        {
            $demand_number = $demand['card_type'];
            $demand_material = $this->demand[$demand_number];
            $required_demand = $demand_material['demand'];
            $goods_on_demand = 0;
            if(isset($goods_by_location[$demand_number]))
                $goods_on_demand = count($goods_by_location[$demand_number]);
            
            if($goods_on_demand == $required_demand)
            {
                if($goods_on_demand > 0)
                {
                    // delete goods
                    $good_ids = implode(',', $goods_by_location[$demand_number]);
                    self::DbQuery("DELETE FROM card WHERE card_id IN ($good_ids)");
                }

                // discard tile
                $demand_by_location[$demand['card_location']] = null;
                $demand_id = $demand['card_id'];
                $demands[$index]['card_location'] = 'discard';
                self::DbQuery("UPDATE card SET card_location = 'discard' WHERE card_id = $demand_id");

                // notify
                self::notifyAllPlayers( "demandDiscarded", "", array(
                    'demand_number' => $demand_number
                ));
            }
            else
            {
                $demand_by_location[$demand['card_location']] = $demand;
            }
        }

        // shift demand right
        $rows = ['food_and_dairy', 'dry_goods', 'meat_packing', 'shoes'];
        $bonuses = ['50', '20', '0'];
        foreach($rows as $row)
        {
            foreach($bonuses as $bonus)
            {
                $demand = $demand_by_location["${row}_${bonus}"];
                if($demand != null)
                    break;
                
                // when location empty, shift tiles to the left
                if($bonus == '50')
                {
                    if($demand_by_location["${row}_20"] != null)
                    {
                        // check if 20 not empty => shift
                        $demand = $demand_by_location["${row}_20"];
                        $demand_by_location["${row}_50"] = $demand;
                        $demand_by_location["${row}_20"] = null;
                        $demand_id = $demand['card_id'];
                        $demand['card_location'] = "${row}_50";
                        self::DbQuery("UPDATE card SET card_location = '${row}_50' WHERE card_id = $demand_id");
                        self::notifyAllPlayers( "demandShifted", "", array(
                            'demand' => $demand,
                            'from' => "${row}_20"
                        ));
                    }
                    else if($demand_by_location["${row}_0"] != null)
                    {
                        // else check if 0 not empty => shift
                        $demand = $demand_by_location["${row}_0"];
                        $demand_by_location["${row}_50"] = $demand;
                        $demand_by_location["${row}_0"] = null;
                        $demand_id = $demand['card_id'];
                        $demand['card_location'] = "${row}_50";
                        self::DbQuery("UPDATE card SET card_location = '${row}_50' WHERE card_id = $demand_id");
                        self::notifyAllPlayers( "demandShifted", "", array(
                            'demand' => $demand,
                            'from' => "${row}_0"
                        ));
                    }
                    else
                    {
                        // draw a card
                        $demand = self::getObjectFromDB(
                            "SELECT card_id, card_type FROM card WHERE primary_type = 'demand' AND card_location = 'demand_deck'
                            ORDER BY card_location_arg ASC
                            LIMIT 1");
                        
                        if($demand != null)
                        {
                            $demand_id = $demand['card_id'];
                            $demand['card_location'] = "${row}_50";
                            self::DbQuery("UPDATE card SET card_location = '${row}_50' WHERE card_id = $demand_id");
                            self::notifyAllPlayers( "demandDrawn", "", array(
                                'demand' => $demand
                            ));
                        }
                    }
                }
                else if ($bonus == '20')
                {
                    if($demand_by_location["${row}_0"] != null)
                    {
                        // else check if 0 not empty => shift
                        $demand = $demand_by_location["${row}_0"];
                        $demand_by_location["${row}_20"] = $demand;
                        $demand_by_location["${row}_0"] = null;
                        $demand_id = $demand['card_id'];
                        $demand['card_location'] = "${row}_20";
                        self::DbQuery("UPDATE card SET card_location = '${row}_20' WHERE card_id = $demand_id");
                        self::notifyAllPlayers( "demandShifted", "", array(
                            'demand' => $demand,
                            'from' => "${row}_0"
                        ));
                    }
                    else
                    {
                        $demand = self::getObjectFromDB(
                            "SELECT card_id, card_type FROM card WHERE primary_type = 'demand' AND card_location = 'demand_deck'
                            ORDER BY card_location_arg ASC
                            LIMIT 1");
                        
                        if($demand != null)
                        {
                            $demand_id = $demand['card_id'];
                            $demand['card_location'] = "${row}_20";
                            self::DbQuery("UPDATE card SET card_location = '${row}_20' WHERE card_id = $demand_id");
                            self::notifyAllPlayers( "demandDrawn", "", array(
                                'demand' => $demand
                            ));
                        }
                    }
                }
                else if($bonus == '0')
                {
                    // draw a card
                    $demand = self::getObjectFromDB(
                        "SELECT card_id, card_type FROM card WHERE primary_type = 'demand' AND card_location = 'demand_deck'
                        ORDER BY card_location_arg ASC
                        LIMIT 1");
                    
                    if($demand != null)
                    {
                        $demand_id = $demand['card_id'];
                        $demand['card_location'] = "${row}_0";
                        self::DbQuery("UPDATE card SET card_location = '${row}_0' WHERE card_id = $demand_id");
                        self::notifyAllPlayers( "demandDrawn", "", array(
                            'demand' => $demand
                        ));
                    }
                }
            }
        }
    }

    function refillAssets()
    {
        $assets = self::getObjectListFromDB("SELECT card_id, card_type, card_location FROM card 
            WHERE primary_type = 'asset' AND 
                (card_location = '80' OR 
                card_location = '70' OR 
                card_location = '60' OR 
                card_location = '50' OR
                card_location = '40')");

        $spots = ['40', '50', '60', '70', '80'];
        $empty_spot = '';

        // There's only one spot empty, find it
        foreach($spots as $index => $spot)
        {
            $found = false;
            foreach($assets as $asset)
            {
                if($asset['card_location'] == $spot)
                {
                    $found = true;
                    break;
                }
            }

            if(!$found)
            {
                $empty_spot = $index;
                break;
            }
        }

        // shift all assets once
        foreach($assets as $index => $asset)
        {
            $card_id = $asset['card_id'];
            switch($asset['card_location'])
            {
                case '80':
                    if($empty_spot <= 4)
                    {
                        $assets[$index]['card_location'] = '70';
                        self::DbQuery("UPDATE card SET card_location = '70' WHERE card_id = $card_id");
                    }
                    break;
                case '70':
                    if($empty_spot <= 3)
                    {
                        $assets[$index]['card_location'] = '60';
                        self::DbQuery("UPDATE card SET card_location = '60' WHERE card_id = $card_id");
                    }
                    break;
                case '60':
                    if($empty_spot <= 2)
                    {
                        $assets[$index]['card_location'] = '50';
                        self::DbQuery("UPDATE card SET card_location = '50' WHERE card_id = $card_id");
                    }
                    break;
                case '50':
                    if($empty_spot <= 1)
                    {
                        $assets[$index]['card_location'] = '40';
                        self::DbQuery("UPDATE card SET card_location = '40' WHERE card_id = $card_id");
                    }
                    break;
            }
        }

        // draw new asset
        $new_asset = self::getObjectFromDB(
            "SELECT card_id, card_type, card_location FROM card
            WHERE primary_type = 'asset' AND card_location = 'asset_deck'
            ORDER BY card_location_arg ASC
            LIMIT 1");
        
        if($new_asset != null)
        {
            $id = $new_asset['card_id'];
            $new_asset['card_location'] = '80';
            self::DbQuery("UPDATE card SET card_location = '80' WHERE card_id = $id");
        }

        self::notifyAllPlayers( "assetsShifted", "", array(
            'assets' => $assets,
            'new_asset' => $new_asset
        ));
    }

    function refillSupply($discard_rightmost = false)
    {
        // get all resources from supply chain
        $resources = self::getObjectListFromDB(
            "SELECT card_id, card_location, card_type FROM card WHERE
                card_location = 'x' OR
                card_location = '30' OR
                card_location = '20' OR
                card_location = '10'");
        
        // this is needed to notify players where the resource is coming from
        foreach($resources as $index => $resource)
        {
            $resources[$index]['original_location'] = $resource['card_location'];
        }
        
        $supply_locations = ['10', '20', '30', 'x'];
        $number_empty_locations = 0;
        
        // during cleanup, discard all resources in 10 spot
        if($discard_rightmost == true)
        {
            $resource_ids = [];
            $resource_ids_types = [];

            foreach($resources as $resource_index => $resource)
            {
                if($resource['card_location'] == '10')
                {
                    $resource_ids[] = $resource['card_id'];
                    $resources[$resource_index]['card_location'] = 'haymarket';
                    $resource_ids_types[] = ['id' => $resource['card_id'], 'type' => $resource['card_type']];
                }
            }

            $in_condition = implode(',', $resource_ids);
            self::DbQuery("UPDATE card SET card_location = 'haymarket' WHERE card_id IN ($in_condition)");

            // notify resources discarded
            self::notifyAllPlayers( "resourcesDiscarded", "Discard rightmost supply chain space to Haymarket Square", array(
                'resource_ids_types' => $resource_ids_types
            ));
        }

        self::notifyAllPlayers( "supplyChainRefill", clienttranslate( 'Supply chain refill'), array());

        // then check each location if empty
        foreach($supply_locations as $index => $supply_location)
        {
            self::trace("Check supply location ${supply_location}");

            $empty = true;
            $resource_ids = [];
            $resource_ids_types = [];
            $from = "";

            // check if empty
            foreach($resources as $resource)
            {
                if($resource['card_location'] == $supply_location)
                {
                    // if it's not empty but the resource has been moved, we need to update its location
                    if($resource['card_location'] != $resource['original_location'])
                    {
                        $from = $resource['original_location'];
                        $resource_ids[] = $resource['card_id'];
                        $resource_ids_types[] = ['id' => $resource['card_id'], 'type' => $resource['card_type']];
                    }

                    $empty = false;
                }
            }

            if($empty)
            {
                self::trace("${supply_location} is empty => shift resources right until not empty");
            }

            $i = $index;
            while($empty && $i < 3)
            {
                // shift all resources
                foreach($resources as $resource_index => $resource)
                {
                    if($resource['card_location'] == '20' && $index < 1)
                    {
                        // move all 20 -> 10 when refilling 10 spot
                        $resources[$resource_index]['card_location'] = '10';
                        if($supply_location == '10')
                        {
                            // if refilling 10 and there is a resource on 20 => 10 is no longer empty
                            $from = $resource['original_location'];
                            $resource_ids[] = $resource['card_id'];
                            $resource_ids_types[] = ['id' => $resource['card_id'], 'type' => $resource['card_type']];
                            $empty = false;
                        }
                    }
                    else if ($resource['card_location'] == '30' && $index < 2)
                    {
                        // move all 30 -> 20 when refilling 10 and 20 spot
                        $resources[$resource_index]['card_location'] = '20';
                        if($supply_location == '20')
                        {
                            // if refilling 20 and there is a resource on 30 => 20 is no longer empty
                            $from = $resource['original_location'];
                            $resource_ids[] = $resource['card_id'];
                            $resource_ids_types[] = ['id' => $resource['card_id'], 'type' => $resource['card_type']];
                            $empty = false;
                        }
                    }
                    else if ($resource['card_location'] == 'x' && $index < 3)
                    {
                        // move all x -> 30 when refilling 10, 20, and 30 spot
                        $resources[$resource_index]['card_location'] = '30';
                        if($supply_location == '30')
                        {
                            // if refilling 30 and there is a resource on x => 30 is no longer empty
                            $from = $resource['original_location'];
                            $resource_ids[] = $resource['card_id'];
                            $resource_ids_types[] = ['id' => $resource['card_id'], 'type' => $resource['card_type']];
                            $empty = false;
                        }
                    }
                }

                $i++;
            }

            if($empty)
            {
                $number_empty_locations++;
                self::trace("Supply location still empty");
            }

            if(!$empty && count($resource_ids) > 0)
            {
                // update current location
                $in_condition = implode(',', $resource_ids);
                self::DbQuery("UPDATE card SET card_location = $supply_location WHERE card_id IN ($in_condition)");

                self::notifyAllPlayers( "resourcesShifted", "", array(
                    'resource_ids_types' => $resource_ids_types,
                    'location' => $supply_location,
                    'from' => $from
                ));
            }
        }

        $round = self::getGameStateValue( "round" );
        $refill_amount = self::REFILL_AMOUNT[$round];

        // try to get the right amount of resources to refill
        $resources_to_draw = $refill_amount * $number_empty_locations;
        $drawn_resources = self::getObjectListFromDB(
            "SELECT card_id, card_location, card_location_arg, card_type FROM card
            WHERE card_location = 'resource_bag'
            ORDER BY card_location_arg ASC
            LIMIT $resources_to_draw");

        //self::dump('drawn_resources', $drawn_resources);

        // if not enough, need to reshuffle everything
        $drawn_resources_count = count($drawn_resources);
        $missing_resources_count = $resources_to_draw - $drawn_resources_count;
        if($missing_resources_count != 0)
        {
            $this->cards->shuffle('haymarket');

            // get missing resources if any
            $missing_resources = self::getObjectListFromDB(
                "SELECT TOP $missing_resources_count card_id, card_location, card_type FROM card
                WHERE card_location = 'haymarket'
                ORDER BY card_location_arg ASC");

            $drawn_resources = array_merge($drawn_resources, $missing_resources);

            // move haymarket to resource bag
            $this->cards->moveAllCardsInLocation( 'haymarket', 'resource_bag' );

            // move 2 of each resource if any to haymarket
            self::DbQuery(
                "UPDATE  card
                SET card_location = 'haymarket'
                WHERE card_location = 'resource_bag' AND card_type = 'livestock'
                LIMIT 2");
            
            self::DbQuery(
                "UPDATE  card
                SET card_location = 'haymarket'
                WHERE card_location = 'resource_bag' AND card_type = 'steel'
                LIMIT 2");

            self::DbQuery(
                "UPDATE  card
                SET card_location = 'haymarket'
                WHERE card_location = 'resource_bag' AND card_type = 'wood'
                LIMIT 2");
            
            self::DbQuery(
                "UPDATE  card
                SET card_location = 'haymarket'
                WHERE card_location = 'resource_bag' AND card_type = 'coal'
                LIMIT 2");

            // shuffle resource bag again
            $this->cards->shuffle('resource_bag');
        }

        // then update location of drawn resources for each empty location
        for($i = 0; $i < $number_empty_locations; $i++)
        {
            $resource_ids_types = [];
            $location = $supply_locations[3 - $i];
            $resource_ids = [];
            for($j = 0; $j < $refill_amount; $j++)
            {
                $resource = array_pop($drawn_resources);
                $resource_ids[] = $resource['card_id'];
                $resource_ids_types[] = ['id' => $resource['card_id'], 'type' => $resource['card_type']];
            }

            $in_condition = implode(',', $resource_ids);
            self::DbQuery("UPDATE card SET card_location = '$location' WHERE card_id IN ($in_condition)");

            self::notifyAllPlayers( "resourcesDrawn", "", array(
                'resource_ids_types' => $resource_ids_types,
                'location' => $location
            ));
        }

        if(count($drawn_resources) != 0)
            throw new BgaVisibleSystemException("Didn't process all drawn resources");
    }

    function gainAsset($company_name, $company, $asset, $should_replace)
    {
        $company_short_name = $company['short_name'];
        $company_id = $company['id'];
        $asset_material = $this->capital_asset[$asset['card_type']];
        
        // save current company since we're going in a transit state and we'll need it
        self::setGameStateValue('bonus_company_id', $company_id);

        $asset_id = $asset['card_id'];
        if($should_replace)
        {
            $current_asset = self::getUniqueValueFromDB("SELECT card_id, card_type FROM card WHERE primary_type = 'asset' AND card_location = '$company_short_name'");
            if($current_asset != null)
            {
                $current_asset_id = $current_asset['card_id'];
                self::DbQuery("UPDATE card SET card_location = 'discard', owner_type = NULL WHERE card_id = $current_asset_id");

                self::notifyAllPlayers( "companyAssetDiscarded", clienttranslate('${company_name} discards current Capital Asset'), array(
                    'asset' => $current_asset,
                    'company_name' => $company_name,
                    'short_name' => $company_short_name
                ) );
            }

            self::DbQuery("UPDATE card SET card_location = '$company_short_name', owner_type = 'company' WHERE card_id = $asset_id");
            $asset['card_location'] = $company_short_name;
            self::notifyAllPlayers( "assetGained", clienttranslate('${company_name} gains Capital Asset'), array(
                'asset' => $asset,
                'company_name' => $company_name,
                'short_name' => $company_short_name
            ) );
        }
        else
        {
            self::DbQuery("UPDATE card SET card_location = 'discard', owner_type = NULL WHERE card_id = $asset_id");

            self::notifyAllPlayers( "assetDiscarded", clienttranslate('${company_name} discards Capital Asset'), array(
                'asset_name' => $asset['card_type'],
                'asset_id' => $asset['card_id']
            ) );
        }

        self::refillAssets();
        
        return $asset_material['bonus'];
    }

    function automateWorker($company_short_name, $factory_number, $relocateFactoryNumber)
    {
        self::dump('relocateFactoryNumber', $relocateFactoryNumber);

        // get the automations in the whole company
        // => location = spalding_automation_holder_{factory_number}_{spot_number}
        // some of the might be in worker spots => location = spalding_worker_holder_{factory_number}
        $sql = "SELECT card_id, card_location FROM card WHERE primary_type = 'automation' AND card_location LIKE '$company_short_name%'";
        $automations = self::getObjectListFromDB($sql);

        $to_automate = null;
        $total_automated = 0;
        $automated_in_relocate_factory = 0;
        // get the one automation that is not automated in the current factory
        foreach($automations as $automation)
        {
            $values = explode('_', $automation['card_location']); // brunswick_automation_holder_{factory_number}_0
            if($values[1] == 'automation')
            {
                if($values[3] == $factory_number)
                    $to_automate = $automation;
            }
            else
            {
                if($values[4] == $relocateFactoryNumber)
                    $automated_in_relocate_factory++;
                // automated because value = worker
                $total_automated++;
            }
        }

        self::dump('automated_in_relocate_factory', $automated_in_relocate_factory);

        // check that not all automations have been automated in this factory
        if($to_automate == null)
            throw new BgaVisibleSystemException("The factory is completely automated");

        // get the workers in the company => location = spalding_{factory_number}
        $sql = "SELECT card_id, card_location FROM card WHERE primary_type = 'worker' AND card_location LIKE '$company_short_name%'";
        $workers = self::getObjectListFromDB($sql);
        $total_workers = 0;
        $to_automate_worker = null;
        $workers_in_relocate_factory = 0;
        foreach($workers as $worker)
        {
            $total_workers++;
            $values = explode('_', $worker['card_location']);
            
            if($values[1] == $factory_number){
                $to_automate_worker = $worker;
            }
            
            if ($values[1] == $relocateFactoryNumber){
                $workers_in_relocate_factory++;
            }
        }

        self::dump('workers_in_relocate_factory', $workers_in_relocate_factory);

        // check that there are enough workers in that factory to automate it
        if($to_automate_worker == null)
            throw new BgaVisibleSystemException("The factory has no workers to automate");

        // get the initial number of automations in the factory
        $company_material = $this->companies[$company_short_name];
        $factory_automations = $company_material['factories'][$factory_number]['automation'];
        $total_automations = 0;
        foreach($company_material['factories'] as $factory)
        {
            $total_automations += $factory['automation'];
        }

        // automate => spalding_worker_holder_{factory_number}
        $to_automate_id = $to_automate['card_id'];
        $sql = "UPDATE card
            SET card_location = '${company_short_name}_worker_holder_${factory_number}'
            WHERE card_id = $to_automate_id";
        self::DbQuery($sql);

        // check that there will be a spot left for the worker, otherwise send it to the market
        $worker_relocation = null;
        if($total_workers + $total_automated + 1 == $total_automations)
        {
            $worker_id = $to_automate_worker['card_id'];
            $number_workers_market = self::getUniqueValueFromDB("SELECT COUNT(card_id) FROM card WHERE primary_type = 'worker' AND card_location = 'job_market'");
            if($number_workers_market == 12)
            {
                // worker is sent to supply (destroyed)
                self::DbQuery("DELETE FROM card WHERE card_id = '$worker_id'");
                $worker_relocation = 'supply';
            }
            else
            {
                self::DbQuery("UPDATE card SET card_location = 'job_market' WHERE card_id = '$worker_id'");
                $worker_relocation = 'job_market';
            }
        }
        else
        {
            // otherwise check that $relocateFactoryNumber has an empty spot and send it there
            $automation_in_relocate_factory = $this->companies[$company_short_name]['factories'][$relocateFactoryNumber]['automation'];  
            if($automated_in_relocate_factory + $workers_in_relocate_factory == $automation_in_relocate_factory)
                throw new BgaUserException( self::_("The automated worker cannot be relocated to this factory") );

            // only if relocate factory is different than current factory, move the worker to new factory
            if($relocateFactoryNumber != $factory_number)
            {
                $worker_id = $to_automate_worker['card_id'];
                $sql = "UPDATE card SET card_location = '${company_short_name}_${relocateFactoryNumber}' WHERE card_id = '$worker_id'";
                self::DbQuery($sql);
                $worker_relocation = "${company_short_name}_${relocateFactoryNumber}";
            }
        }

        // notify players
        // -> new worker location
        // -> new automation location
        self::notifyAllPlayers( "factoryAutomated", clienttranslate( '${company_name} automated a factory' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'company_short_name' => $company_short_name,
            'factory_number' => $factory_number,
            'worker_relocation' => $worker_relocation 
        ));
    }

    function getBuildingOwner($building)
    {
        $location = $building['card_location']; // building_track_{player_id}
        $player_id = explode('_', $location)[2];
        $sql = "SELECT player_name, treasury, player_id FROM player WHERE player_id = $player_id";
        return self::getNonEmptyObjectFromDB($sql);
    }

    function companyProduceGoods($company_short_name, $company_id, $number_of_goods)
    {
        // insert goods into database
        $sql = "INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location, card_location_arg) VALUES ";
        
        $values = [];
        for($i = 0; $i < $number_of_goods; $i++)
        {
            $values[] = "('company', 'good', 'good', 0, '$company_short_name', $company_id)";
        }
        $sql .= implode( $values, ',' );

        self::DbQuery($sql);
        
        // get id of company goods
        $sql = "SELECT card_id FROM card WHERE primary_type = 'good' AND card_location_arg = $company_id";
        $good_ids = self::getObjectListFromDB( $sql, true );
        
        // notify players
        self::notifyAllPlayers( "goodsProduced", clienttranslate( '${company_name} produces ${number_of_goods} goods' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'company_short_name' => $company_short_name,
            'number_of_goods' => $number_of_goods,
            'good_ids' => $good_ids
        ));
    }

    function distributeDividends($company_short_name, $money)
    {
        $sql = "SELECT owner_type, card_type, card_type_arg, card_location_arg
            FROM card 
            WHERE primary_type = 'stock' AND card_type LIKE '$company_short_name%'";
        $stocks = self::getObjectListFromDB($sql);

        // round down money to nearest 10
        $money = floor($money/10)*10;

        // announce per share earning
        $per_share_earning = $money/10;
        self::notifyAllPlayers( "earningsAnounced", clienttranslate( '${company_name} is paying $${per_share_earning} per share' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'per_share_earning' => $per_share_earning,
        ));

        $payment_to_company = 0;
        $payment_to_players = [];
        $player_ids = [];
        foreach($stocks as $stock)
        {
            // get stock type (brunswick_director)
            $split = explode('_', $stock['card_type']);
            $stock_type = $split[1];
            $multiplier = 1;
            if($stock_type == 'director'){
                $multiplier = 3;
            } else if($stock_type == 'preferred'){
                $multiplier = 2;
            }

            if($stock['owner_type'] == 'company'){
                $payment_to_company += $multiplier * $per_share_earning;
            } else if ($stock['owner_type'] == 'player'){
                $player_id = $stock['card_location_arg'];
                if(!isset($payment_to_players[$player_id]))
                {
                    array_push($player_ids, $player_id);
                    $payment_to_players[$player_id] = $multiplier * $per_share_earning;
                }
                else
                {
                    $payment_to_players[$player_id] += $multiplier * $per_share_earning;
                }
            }
        }

        // get the players (need name and treasury)
        $in_condition = "(".implode(',', $player_ids).")";
        $sql = "SELECT player_id, player_name, treasury FROM player WHERE player_id IN $in_condition";
        $players = self::getCollectionFromDB($sql);

        // pay the players
        foreach($payment_to_players as $player_id => $payment)
        {
            $new_treasury = $players[$player_id]['treasury'] + $payment;

            $sql = "UPDATE player SET treasury = $new_treasury WHERE player_id = $player_id";
            self::DbQuery($sql);

            self::notifyAllPlayers( "dividendEarned", clienttranslate( '${player_name} earns $${earning}' ), array(
                'player_name' => $players[$player_id]['player_name'],
                'earning' => $payment,
                'counters' => self::getCounter("money_${player_id}", $new_treasury)
            ));
        }

        $sql = "SELECT id, treasury, share_value_step FROM company WHERE short_name = '$company_short_name'";
        $company = self::getNonEmptyObjectFromDB($sql);
        $company_id = $company['id'];   
        $share_value_step = $company['share_value_step'];
        $treasury = $company['treasury'];
        $new_treasury = $treasury;
        $update = false;

        // pay the company
        if($payment_to_company > 0)
        {
            $update = true;
            $new_treasury += $payment_to_company;

            self::notifyAllPlayers( "dividendEarned", clienttranslate( '${company_name} earns $${earning}' ), array(
                'company_name' => self::getCompanyName($company_short_name),
                'earning' => $payment_to_company,
                'counters' => self::getCounter("money_${company_short_name}", $new_treasury)
            ));
        }

        // check for share value increase
        $share_value = self::getShareValue($share_value_step);
        $new_share_value_step = $share_value_step;

        if($money >= 3 * $share_value && $share_value_step > 6)
        {
            $update = true;
            $new_share_value_step += 3;
        } else if ($money >= 2 * $share_value)
        {
            $update = true;
            $new_share_value_step += 2;
        } else if ($money >= $share_value)
        {
            $update = true;
            $new_share_value_step += 1;
        }

        if($new_share_value_step > 20)
            $new_share_value_step = 20;

        if($update)
        {
            $sql = "UPDATE company SET treasury = $new_treasury, share_value_step = $new_share_value_step WHERE id = $company_id";
            self::DbQuery($sql);
        }

        if($new_share_value_step > $share_value_step)
        {
            $new_share_value = self::getShareValue($new_share_value_step);
            self::notifyAllPlayers( "shareValueChange", clienttranslate( 'The share value of ${company_name} increased to $${share_value}' ), array(
                'company_short_name' => $company_short_name,
                'company_name' => self::getCompanyName($company_short_name),
                'share_value' => $new_share_value,
                'share_value_step' => $new_share_value_step,
                'previous_share_value_step' => $share_value_step
            ));
        }

        // TODO: update player scores
    }

    function hire_manager($company_short_name, $factory_number)
    {
        $location = "${company_short_name}_${factory_number}";

        // check that factory doesn't already have a manager
        $sql = "SELECT card_id FROM card WHERE primary_type = 'manager' AND card_location = '$location'";
        $value = self::getUniqueValueFromDB( $sql );
        if($value != null)
            throw new BgaVisibleSystemException("Factory already has a manager");

        // create manager in that factory
        $sql = "INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location, card_location_arg)
            VALUES ('company', 'manager', 'manager', 0, '$location', 0)";
        self::DbQuery($sql);
        $manager_id = self::DbGetLastId();

        // notify
        self::notifyAllPlayers( "managerHired", clienttranslate( '${company_name} hired a manager' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'company_short_name' => $company_short_name,
            'location' => $location,
            'manager_id' => $manager_id
        ) );
    }

    function hire_salesperson($company_short_name)
    {
        // check that company can hire more salesperson
        $sql = "SELECT COUNT(card_id) FROM card WHERE primary_type = 'salesperson' AND card_location = '$company_short_name'";
        $value = self::getUniqueValueFromDB( $sql );
        $salesperson_number = $this->companies[$company_short_name]['salesperson_number'];

        if($salesperson_number <= $value)
            throw new BgaVisibleSystemException("The company can no longer hire salespeople");
        
        // create manager in that factory
        $sql = "INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location, card_location_arg)
            VALUES ('company', 'salesperson', 'salesperson', 0, '$company_short_name', 0)";
        self::DbQuery($sql);
        $salesperson_id = self::DbGetLastId();

        // notify
        self::notifyAllPlayers( "salespersonHired", clienttranslate( '${company_name} hired a salesperson' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'company_short_name' => $company_short_name,
            'location' => $company_short_name,
            'salesperson_id' => $salesperson_id
        ) );
    }

    function increaseCompanyAppeal($company_short_name, $company_id, $current_appeal, $steps)
    {
        if($current_appeal == 16)
            return;
        
        $new_appeal = $current_appeal + $steps;
        if($new_appeal > 16)
            $new_appeal = 16;

        self::DbQuery("UPDATE company SET appeal = $new_appeal WHERE short_name = '$company_short_name'");

        self::notifyAllPlayers( "appealIncreased", clienttranslate( '${company_name} increased its appeal to ${appeal}' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'company_short_name' => $company_short_name,
            'appeal' => $new_appeal,
            'previous_appeal' => $current_appeal,
            'order' => self::getCurrentCompanyOrder()
        ) );

        $last_bonus_gained = self::BONUS_LOOKUP[$current_appeal];
        $new_bonus_gained = self::BONUS_LOOKUP[$new_appeal];

        if($last_bonus_gained != $new_bonus_gained)
        {
            self::setGameStateValue('bonus_company_id', $company_id);
            self::setGameStateValue('next_appeal_bonus', $last_bonus_gained + 1);
            self::setGameStateValue('final_appeal_bonus', $new_bonus_gained);
            return true;
        }
        return false;
    }

    function getCompanyName($company_short_name){
        return $this->companies[$company_short_name]['name'];
    }

    function addCounter(&$counters, $counter_name, $counter_value)
    {
        $counters[$counter_name] = ['counter_name' => $counter_name, 'counter_value' => $counter_value];
    }

    function getCounter($counter_name, $counter_value)
    {
        $counters = [];
        $counters[$counter_name] = ['counter_name' => $counter_name, 'counter_value' => $counter_value];
        return $counters;
    }

    function getCostToHireWorkers($number_of_workers)
    {
        $sql = "SELECT COUNT(card_id) FROM card WHERE primary_type = 'worker' AND card_location ='job_market'";
        $workers_in_market = self::getUniqueValueFromDB($sql);
        $convert = [0 => 50, 1 => 40, 2 => 40, 3 => 40, 4 => 40, 5 => 30, 6 => 30, 7 => 30, 8 => 30, 9 => 20, 10 => 20, 11 => 20, 12 => 20];
        $cost = 0;
        $values = [];
        for($i = 0; $i < $number_of_workers; $i++){
            $cost += $convert[$workers_in_market];
            if($workers_in_market > 0){
                $workers_in_market--;
            }
        }

        return $cost;
    }

    function hireWorkerFromSupply($company_short_name, $factory_number)
    {
        if(!$factory_number)
            throw new BgaVisibleSystemException("Unknown factory number");

        $total_spots = $this->companies[$company_short_name]['factories'][$factory_number]['workers'];
        $location = "${company_short_name}_${factory_number}";
        $sql = "SELECT COUNT(card_id) FROM card WHERE primary_type = 'worker' AND card_location LIKE '$location%'";
        $number_of_workers_in_factory = self::getUniqueValueFromDB($sql);
        
        $available_spots = $total_spots - $number_of_workers_in_factory;
        if($available_spots < 0)
            throw new BgaVisibleSystemException("Negative spots available in factory");
        
        if($available_spots == 0)
            throw new BgaUserException( self::_("No more available spaces in this factory") );
        
        // create a new worker since it's coming from the supply
        self::DbQuery("INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location, card_location_arg) VALUES
            ('company', 'worker', 'worker', 0, '$location', 0)");
        $worker_id = self::DbGetLastId();

        $company_material = $this->companies[$company_short_name];
        $company_name = $company_material['name'];

        self::notifyAllPlayers( "workerReceived", clienttranslate( '${company_name} receives a worker from the supply' ), array(
            'factory_id' => $location,
            'company_name' => $company_name,
            'worker_id' => $worker_id
        ) );
    }

    function hireWorkers($company_short_name, $worker_factories)
    {
        $number_of_workers = count($worker_factories);
        $factories_material = $this->companies[$company_short_name]['factories'];

        // get workers already in factories
        $workers_by_factory = self::getCollectionFromDB(
            "SELECT card_location, COUNT(card_id) AS worker_count FROM card 
            WHERE primary_type = 'worker' AND card_location LIKE '$company_short_name%'
            GROUP BY card_location");

        // get automated workers in factories location = spalding_worker_holder_{factory_number}
        $automations_by_factory = self::getCollectionFromDB(
            "SELECT card_location, COUNT(card_id) AS automation_count FROM card 
            WHERE primary_type = 'automation' AND card_location LIKE '${company_short_name}_worker_holder%'
            GROUP BY card_location");
        
        // regroup workers being added by factory
        $workers_to_hire_by_factory = [];
        foreach($worker_factories as $factory_number)
        {
            $key = intval($factory_number);
            if(array_key_exists($key, $workers_to_hire_by_factory)){
                $workers_to_hire_by_factory[$key]++;
            } else {
                $workers_to_hire_by_factory[$key] = 1;
            }
        }

        // get workers in market
        $workers_in_market = self::getObjectListFromDB("SELECT card_id FROM card WHERE primary_type = 'worker' AND card_location ='job_market'");
        $number_of_workers_in_market = count($workers_in_market);
        
        // hire the workers
        $moved_workers_return = [];
        foreach($workers_to_hire_by_factory as $factory_number => $workers_to_hire_count)
        {
            $automation_count = 0;
            $automation_key = "${company_short_name}_worker_holder_${factory_number}";
            if(array_key_exists($automation_key, $automations_by_factory))
                $automation_count = $automations_by_factory[$automation_key];
            
            $worker_key = "${company_short_name}_${factory_number}";
            $worker_count = 0;
            if(array_key_exists($worker_key, $workers_by_factory))
                $worker_count = $workers_by_factory[$worker_key];
            
            $total_spots = $factories_material[$factory_number]['workers'];

            // check that each factory can hold the workers being hired
            if($automation_count + $worker_count + $workers_to_hire_count > $total_spots)
                throw new BgaVisibleSystemException("Not enough space for hired workers");
            
            $moved_workers = [];
            $new_workers = [];
            $condition = $company_short_name.'_'.$factory_number;
            for($i = 0; $i < $workers_to_hire_count; $i++)
            {
                $worker = array_shift($workers_in_market);
                if($worker != null)
                {
                    $moved_workers[] = $worker['card_id'];
                    $worker['card_location'] = $condition;
                    $moved_workers_return[] = $worker;
                }
                else 
                {
                    $new_workers[] = "('company','worker','worker',0,'$condition',0)";
                }
            }

            if(count($moved_workers) > 0)
            {
                // move these workers to the factory
                $sql = "UPDATE card SET card_location = '$condition', owner_type = 'company' WHERE card_id IN ";
                $sql .= "(".implode( $moved_workers, ',' ).")";
                self::DbQuery($sql);
            }
            
            // create workers if market has less workers than amount being hired
            if(count($new_workers) > 0)
            {
                $card_sql = "INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location, card_location_arg) VALUES ";
                $card_sql .= implode( $new_workers, ',' );
                self::DbQuery($card_sql);
            }
        }

        $all_workers = self::getObjectListFromDB("SELECT * FROM card WHERE primary_type = 'worker' AND card_location LIKE '$company_short_name%'");
        $company_material = $this->companies[$company_short_name];
        $company_name = $company_material['name'];

        self::notifyAllPlayers( "workersHired", clienttranslate( '${company_name} hired ${number_of_workers} workers from the job market' ), array(
            'factory_id' => $condition,
            'company_name' => $company_name,
            'worker_ids' => $moved_workers_return, // moved workers
            'all_worker' => $all_workers,
            'number_of_workers' => $number_of_workers
        ) );
    }

    function getPlayerShares($player_id)
    {
        $sql = "SELECT card_id AS card_id, card_type AS card_type FROM card WHERE primary_type = 'stock' AND card_location_arg = $player_id AND owner_type = 'player'";
        return self::getObjectListFromDB($sql);
    }

    function getShareValues()
    {
        $sql = "SELECT short_name AS short_name, id AS id, share_value_step AS share_value_step FROM company";
        $share_value_steps = self::getCollectionFromDB($sql);

        foreach($share_value_steps as $key => $share_value_step)
        {
            $share_value_steps[$key]['value'] = self::getShareValue($share_value_step['share_value_step']);
        }

        return $share_value_steps;
    }

    function getCurrentCompanyOrder($companies = null)
    {
        if($companies == null)
        {
            $sql = "SELECT id AS id, appeal AS appeal, owner_id AS owner_id, next_company_id AS next_company_id, short_name AS short_name FROM company";
            $companies = self::getCollectionFromDb( $sql );
        }

        $order = [];

        if(count($companies) == 0)
            return $order;
        
        // find last company in the chain (last in turn order and lowest appeal)
        $last_company = null;
        foreach($companies as $company)
        {
            if($company['next_company_id'] == null)
            {
                $last_company = $company;
                break;
            }
        }

        if($last_company == null)
            throw new BgaVisibleSystemException("No company found in last order");

        // go up the chain
        $previous_company = $last_company;

        // order will be processed from last to first
        $i = count($companies);

        // go through the whole chain up to first in order
        while($previous_company != null)
        {
            // add previous company to the order
            array_unshift($order, [
                'id' => $previous_company['id'], 
                'next_company_id' => $previous_company['next_company_id'], 
                'owner_id' => $previous_company['owner_id'], 
                'order' => $i, 
                'short_name' => $previous_company['short_name'], 
                'appeal' => $previous_company['appeal']]);

            // find previous company
            $current_company = $previous_company;
            $previous_company = null;
            foreach($companies as $company)
            {
                if($company['next_company_id'] == $current_company['id'])
                {
                    $previous_company = $company;
                    break;
                }
            }

            $i--;
        }

        if($i != 0)
            throw new BgaVisibleSystemException("Didn't process all companies in the turn order");
        
        return $order;
    }

    function getPreviousAndNextCompany($initial_appeal, $company_short_name, $player_id)
    {
        $current_order = self::getCurrentCompanyOrder();
        $previous_company_id = null;
        $next_company_id = null;
        $new_order = [];

        if(count($current_order) > 0)
        {
            $inserted = false;
            $i = 0;
            foreach($current_order as $ordered_company)
            {
                $appeal = $ordered_company['appeal'];
                if(!$inserted && $appeal < $initial_appeal)
                {
                    $inserted = true;
                    $next_company_id = $ordered_company['id'];
                    if($i > 0)
                    {
                        // if there's at least one company earlier in turn order
                        $previous_company_id = $current_order[$i-1]['id'];
                    }
                    $new_order[] = ['order' => $i + 1, 'owner_id' => $player_id, 'short_name' => $company_short_name, 'appeal' => $initial_appeal];
                    $i++;
                }

                $ordered_company['order'] = $i + 1;
                $new_order[] = $ordered_company;
                $i++;
            }

            if(!$inserted)
            {
                // this means the new company is last in turn order
                $new_order[] = ['order' => $i + 1, 'owner_id' => $player_id, 'short_name' => $company_short_name, 'appeal' => $initial_appeal];
                $previous_company_id = $current_order[$i-1]['id'];
            }
        }
        else
        {
            // there are no companies yet, this is the only one in order
            $new_order[] = ['order' => 1, 'owner_id' => $player_id, 'short_name' => $company_short_name, 'appeal' => $initial_appeal];
        }

        return ['previous_company_id' => $previous_company_id, 'next_company_id' => $next_company_id, 'order' => $new_order];
    }

    function updatePreviousCompany($company_id, $previous_company_id)
    {
        if($previous_company_id == null)
            return;
        
        $sql = "UPDATE company 
            SET next_company_id='$company_id'
            WHERE id='$previous_company_id'";
        
        self::DbQuery( $sql );
    }

    function initializeDecks($players)
    {
        $player_count = count($players);

        // create 4 demand decks depending on number of pips
        $pips1 = [];
        $pips2 = [];
        $pips3 = [];
        $pips4 = [];

        foreach($this->demand as $demand_name => $demand)
        {
            // do not include some demand tiles for higher player counts
            if($player_count < $demand['min_players'])
                continue;

            switch($demand['pips'])
            {
                case 1:
                    array_push($pips1, $demand_name);
                    break;
                case 2:
                    array_push($pips2, $demand_name);
                    break;
                case 3:
                    array_push($pips3, $demand_name);
                    break;
                case 4:
                    array_push($pips4, $demand_name);
                    break;
                default:
                    throw new BgaVisibleSystemException("number of pips should be between 1-4");
            }
        }

        // shuffle the four demands decks
        shuffle($pips1);
        shuffle($pips2);
        shuffle($pips3);
        shuffle($pips4);

        // stack them on top of each other
        $demand_deck = array_merge($pips1, $pips2, $pips3, $pips4);

        // create capital asset deck by seperating by starting and other tiles
        $starting = [];
        $other = [];
        foreach($this->capital_asset as $asset_name => $asset)
        {
            if($asset['starting'])
            {
                // skip brilliant marketing since it should be on the $80 spot
                if($asset_name == 'brilliant_marketing')
                    continue;
                
                array_push($starting, $asset_name);
            }
            else
            {
                array_push($other, $asset_name);
            }
        }

        // shuffle starting and other tiles
        shuffle($starting);
        shuffle($other);

        // create asset deck by making sure starting tiles are on top, then brilliant marketing, then other tiles
        $asset_deck = array_merge($starting, ['brilliant_marketing'], $other);

        // create building decks depnding on era (number of pips)
        $era1 = [];
        $era2 = [];
        $era3 = [];

        foreach($this->building as $building_name => $building)
        {
            // do not include some demand tiles for higher player counts
            if($player_count < $building['min_players'])
                continue;

            switch($building['pips'])
            {
                case 1:
                    array_push($era1, $building_name);
                    break;
                case 2:
                    array_push($era2, $building_name);
                    break;
                case 3:
                    array_push($era3, $building_name);
                    break;
                default:
                    throw new BgaVisibleSystemException("number of pips should be between 1-3");
            }
        }

        // shuffle each era deck
        shuffle($era1);
        shuffle($era2);
        shuffle($era3);

        // create resource bag (include 2 less since they will be put in haymarket square)
        $resource_bag = [];
        for ($i = 1; $i <= 18; $i++) { array_push($resource_bag, 'livestock'); };
        for ($i = 1; $i <= 16; $i++) { array_push($resource_bag, 'steel'); };
        for ($i = 1; $i <= 14; $i++) { array_push($resource_bag, 'coal'); };
        for ($i = 1; $i <= 14; $i++) { array_push($resource_bag, 'wood'); };

        // shuffle the resource bag
        shuffle($resource_bag);

        // create query to insert items in database
        $sql = "INSERT INTO card (owner_type, primary_type, card_type,card_location,card_location_arg) VALUES ";
        $cards = [];

        // insert demand on each of the 12 spots on the board
        $demand_types = ['food_and_dairy','dry_goods','meat_packing','shoes'];
        $bonus_types = ['50','20','0'];
        for($i = 0; $i < 3; $i++)
        {
            for($j = 0; $j < 4; $j++)
            {
                $demand = array_shift($demand_deck);
                $demand_type = $demand_types[$j % 4];
                $bonus_type = $bonus_types[$i % 3];
                $location = $demand_type.'_'.$bonus_type;

                $cards[] = "(NULL,'demand','$demand','$location','0')";
            }
        }

        // insert rest of demand in the demand deck
        $number_of_items = count($demand_deck);
        for($i = 0; $i < $number_of_items; $i++)
        {
            $demand = array_pop($demand_deck);

            $cards[] = "(NULL,'demand','$demand','demand_deck','$i')";
        }

        // insert capital assets on the asset tile market
        $asset_locations = ['40','50','60','70','80'];
        
        for($i = 0; $i < 5; $i++)
        {
            $asset = array_shift($asset_deck);
            $location = $asset_locations[$i];

            $cards[] = "(NULL,'asset','$asset','$location','0')";
        }

        // insert rest of assets in asset deck
        $number_of_items = count($asset_deck);
        for($i = 0; $i < $number_of_items; $i++)
        {
            $asset = array_pop($asset_deck);

            $cards[] = "(NULL,'asset','$asset','asset_deck','$i')";
        }

        // give 3 buildings to each player
        for($i = 0; $i < 3; $i++)
        {
            foreach($players as $player_id => $player)
            {
                $building = array_shift($era1);
                $location = 'player_'.$player_id;

                $cards[] = "('player','building','$building','$location','0')";
            }
        }

        // this is useless since all era 1 buildings should have been dealt
        $number_of_items = count($era1);
        for($i = 0; $i < $number_of_items; $i++)
        {
            $building = array_pop($era1);

            $cards[] = "(NULL,'building','$building','era_1','$i')";
        }

        // insert era 2 buildings in its own deck
        $number_of_items = count($era2);
        for($i = 0; $i < $number_of_items; $i++)
        {
            $building = array_pop($era2);

            $cards[] = "(NULL,'building','$building','era_2','$i')";
        }

        // insert era 3 buildings in its own deck
        $number_of_items = count($era3);
        for($i = 0; $i < $number_of_items; $i++)
        {
            $building = array_pop($era3);

            $cards[] = "(NULL,'building','$building','era_3','$i')";
        }

        // insert 2 resources of each type in haymarket
        $resource_types = ['livestock','steel','coal','wood'];
        for($i = 0; $i < 4; $i++)
        {
            $resource = $resource_types[$i];
            $cards[] = "(NULL,'resource','$resource','haymarket','0')";
            $cards[] = "(NULL,'resource','$resource','haymarket','0')";
        }

        // insert 3 resources in each spot of the supply chain
        $supply_chain_locations = ['x','30','20','10'];
        for($i = 0; $i < 4; $i++)
        {
            $location = $supply_chain_locations[$i];
            $resource = array_shift($resource_bag);
            $cards[] = "(NULL,'resource','$resource','$location','0')";
            $resource = array_shift($resource_bag);
            $cards[] = "(NULL,'resource','$resource','$location','0')";
            $resource = array_shift($resource_bag);
            $cards[] = "(NULL,'resource','$resource','$location','0')";
        }

        // insert 5 random goals
        $goals = array_keys($this->goal);
        shuffle($goals);
        $i = 0;
        foreach($goals as $goal)
        {
            if($i == 5)
                break;
            $cards[] = "(NULL,'goal','$goal','goals','0')";
            $i++;
        }

        // insert rest of resources in the resource bag
        $number_of_items = count($resource_bag);
        for($i = 0; $i < $number_of_items; $i++)
        {
            $resource = array_pop($resource_bag);

            $cards[] = "(NULL,'resource','$resource','resource_bag','$i')";
        }

        // insert 4 workers
        for($i = 0; $i < 4; $i++)
        {
            $cards[] = "(NULL,'worker','worker','job_market','0')";
        }

        $sql .= implode( $cards, ',' );
        self::DbQuery( $sql );
    }

    function getCompanyByShortName($company_short_name)
    {
        return self::getObjectFromDB(
            "SELECT id id, owner_id owner_id, share_value_step share_value_step 
            FROM company 
            WHERE short_name='$company_short_name'"
        );
    }

    function getPlayer($player_id)
    {
        return self::getObjectFromDB(
            "SELECT player_id, treasury, current_number_partners, number_partners
            FROM player 
            WHERE player_id='$player_id'"
        );
    }

    function getShareValue($value_step)
    {
        $value = self::STEP_TO_VALUE[$value_step];
        if($value == null)
            throw new BgaVisibleSystemException("value should be between 0-20, but not {$value_step}");
        
        return $value;
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in cityofthebigshoulders.action.php)
    */

    function gainAppealBonus( $factory_number, $relocate_number )
    {
        self::checkAction( 'gainAppealBonus' );

        $company_id = self::getGameStateValue('bonus_company_id');
        $next_appeal_bonus = self::getGameStateValue('next_appeal_bonus');
        $final_appeal_bonus = self::getGameStateValue('final_appeal_bonus');

        $company = self::getNonEmptyObjectFromDB("SELECT id, short_name, owner_id FROM company WHERE id = $company_id");
        $player_id = $player_id = self::getActivePlayerId();

        if($company['owner_id'] != $player_id)
            throw new BgaVisibleSystemException("Company is not owner by player");

        $company_short_name = $company['short_name'];
        $bonus_name = self::BONUS_NAME[$next_appeal_bonus];
        switch($bonus_name)
        {
            case 'worker':
                self::hireWorkerFromSupply($company_short_name, $factory_number);
                break;
            case 'manager':
                self::hire_manager($company_short_name, $factory_number);
                break;
            case 'automation':
                self::automateWorker($company_short_name, $factory_number, $relocate_number);
                break;
            case 'partner':
                self::gainPartner($player_id, 'appeal');
                break;
            case 'good':
                self::gainGood($company_short_name);
                break;
            case 'bump':
                self::increaseShareValue($company_short_name, 1);
                break;
        }

        if($next_appeal_bonus == $final_appeal_bonus)
        {
            $state = $this->gamestate->state();
            if( $state['name'] == 'managerBonusAppeal')
            {
                $last_factory_produced = self::getGameStateValue( "last_factory_produced" );
                $total_factories = count($this->companies[$company_short_name]['factories']);
                if($last_factory_produced == $total_factories)
                {
                    // go to state distributeGoods
                    $this->gamestate->nextState( 'distributeGoods' );
                }
                else
                {
                    // else go to next factory
                    $this->gamestate->nextState( 'nextFactory' );
                }
            }
            else
            {
                $this->gamestate->nextState( 'next' );
            }
        }
        else
        {
            self::setGameStateValue('next_appeal_bonus', $next_appeal_bonus + 1);
            $this->gamestate->nextState( 'loopback' );
        }
    }

    function forfeitAppealBonus()
    {
        self::checkAction( 'forfeitAppealBonus' );

        $company_id = self::getGameStateValue('bonus_company_id');

        $company = self::GetNonEmptyObjectFromDB("SELECT id, treasury, short_name, owner_id FROM company WHERE id = $company_id");
        $player_id = $player_id = self::getActivePlayerId();

        if($company['owner_id'] != $player_id)
            throw new BgaVisibleSystemException("Company is not owner by player");

        $new_company_treasury = $company['treasury'] + 25;
        $counters = [];
        $short_name = $company['short_name'];
        self::DbQuery("UPDATE company SET treasury = $new_company_treasury WHERE id = $company_id");
        self::addCounter($counters, "money_${short_name}", $new_company_treasury);

        // notify payment
        self::notifyAllPlayers( "countersUpdated", clienttranslate('${company_name} forfeits appeal bonus and receives $25'), array(
            'company_name' => self::getCompanyName($short_name),
            'counters' => $counters
        ) );

        $next_appeal_bonus = self::getGameStateValue('next_appeal_bonus');
        $final_appeal_bonus = self::getGameStateValue('final_appeal_bonus');

        if($next_appeal_bonus == $final_appeal_bonus)
        {
            $this->gamestate->nextState( 'next' );
        }
        else
        {
            self::setGameStateValue('next_appeal_bonus', $next_appeal_bonus + 1);
            $this->gamestate->nextState( 'loopback' );
        }
    }

    function passFreeActions()
    {
        self::checkAction( 'passFreeActions' );

        $this->gamestate->nextState( 'pass' );
    }

    function tradeResources( $haymarket_resource_id, $company_resource_id1, $company_resource_id2 )
    {
        self::checkAction( 'tradeResources' );

        $resources = self::getCollectionFromDB("SELECT card_id, card_location, card_type, card_location_arg FROM card WHERE primary_type = 'resource' AND card_id IN ($haymarket_resource_id, $company_resource_id1, $company_resource_id2)");

        if(count($resources) != 3)
            throw new BgaVisibleSystemException("Missing resources to do the trade");

        $haymarket_resource = $resources[$haymarket_resource_id];
        if($haymarket_resource['card_location'] != 'haymarket')
            throw new BgaVisibleSystemException("Selected resource does not come from haymarket");
        
        $company_resource1 = $resources[$company_resource_id1];
        $company_resource2 = $resources[$company_resource_id2];
        $company_id = $company_resource1['card_location_arg'];
        $company = self::getNonEmptyObjectFromDB("SELECT id, short_name, owner_id FROM company WHERE id = $company_id");

        $player_id = self::getActivePlayerId();
        if($company['owner_id'] != $player_id)
            throw new BgaVisibleSystemException("Player does not own this company");
        
        if($company_resource1['card_location'] != $company_resource2['card_location'])
            throw new BgaVisibleSystemException("Resources do not come from same company");
        
        if($company_resource1['card_type'] != $company_resource2['card_type'])
            throw new BgaVisibleSystemException("Resources are not the same");
        
        $company_location = $company_resource1['card_location'];
        $haymarket_resource['card_location'] = $company_location;
        self::DbQuery("UPDATE card SET card_location = '$company_location', card_location_arg = $company_id, owner_type = 'company'
            WHERE card_id = $haymarket_resource_id");

        $company_resource1['card_location'] = 'haymarket';
        $company_resource2['card_location'] = 'haymarket';
        self::DbQuery("UPDATE card SET card_location = 'haymarket', card_location_arg = 0, owner_type = NULL
            WHERE card_id = $company_resource_id1 OR card_id = $company_resource_id2");
        
        $short_name = $company['short_name'];
        self::notifyAllPlayers( "resourcesTraded", clienttranslate( '${company_name} trades resources with Haymarket Square'), array(
            'company_name' => self::getCompanyName($short_name),
            'company_short_name' => $short_name,
            'to_haymarket1' => $company_resource1,
            'to_haymarket2' => $company_resource2,
            'to_company' => $haymarket_resource
        ) );

        $this->gamestate->nextState( 'loopback' );
    }

    function withholdEarnings($company_id)
    {
        $company = self::getNonEmptyObjectFromDB("SELECT short_name, income, treasury, share_value_step FROM company WHERE id = $company_id");
        $short_name = $company['short_name'];
        $income = $company['income'];
        $treasury = $company['treasury'];
        $newTreasury = $treasury + $income;
        $share_value_step = $company['share_value_step'];
        $previous_share_value_step = $share_value_step;
        if($share_value_step > 0)
            $share_value_step--;
        self::DbQuery("UPDATE company SET treasury = $newTreasury, income = 0, share_value_step = $share_value_step WHERE id = $company_id");

        $counters = [];
        self::addCounter($counters, "money_${short_name}", $newTreasury);

        $share_value = self::getShareValue($share_value_step);

        self::notifyAllPlayers( "earningsWithhold", clienttranslate( '${company_name} withholds earnings ($${income}), share value drops to $${share_value}'), array(
            'company_name' => self::getCompanyName($short_name),
            'company_short_name' => $short_name,
            'income' => $income,
            'share_value_step' => $share_value_step,
            'previous_share_value_step' => $previous_share_value_step,
            'share_value' => $share_value,
            'counters' => $counters
        ) );

        $this->gamestate->nextState( 'gameOperationPhase' );
    }

    function payDividends()
    {
        self::checkAction( 'payDividends' );

        // get current company and factory
        $company_id = self::getGameStateValue( 'current_company_id');
        $company = self::getNonEmptyObjectFromDB("SELECT short_name, income FROM company WHERE id = $company_id");
        $short_name = $company['short_name'];

        self::distributeDividends($short_name, $company['income']);

        self::DbQuery("UPDATE company SET income = 0 WHERE id = $company_id");

        $this->gamestate->nextState( 'gameOperationPhase' );
    }

    function withhold()
    {
        self::checkAction( 'withhold' );

        $company_id = self::getGameStateValue( 'current_company_id');
        
        self::withholdEarnings($company_id);
    }

    function produceGoods()
    {
        self::checkAction( 'produceGoods' );

        $player_id = self::getActivePlayerId();

        // get current company and factory
        $company_id = self::getGameStateValue( 'current_company_id');
        $factory_number = self::getGameStateValue( 'last_factory_produced') + 1;
        $company = self::getNonEmptyObjectFromDB("SELECT short_name, appeal, extra_goods, owner_id FROM company WHERE id = $company_id");
        $short_name = $company['short_name'];
        $company_material = $this->companies[$short_name];
        $factory_material = $company_material['factories'][$factory_number];

        if($company['owner_id'] != $player_id)
            throw new BgaVisibleSystemException("Company not owned by player");

        // check worker requirements for factory
        $location = "${short_name}_${factory_number}";
        $workers_in_factory = self::getUniqueValueFromDB(
            "SELECT COUNT(card_id) FROM card WHERE primary_type = 'worker' AND card_location = '$location'");
        if($factory_material['workers'] != $workers_in_factory)
            throw new BgaUserException( self::_("Not enough workers to produce in this factory") );

        // check resource requirements for factory
        $resources = self::getObjectListFromDB(
            "SELECT card_type, card_id FROM card WHERE primary_type = 'resource' AND card_location = '$short_name'");
        $required_resources = $factory_material['resources'];
        
        if(count($required_resources) > count($resources))
            throw new BgaUserException( self::_("Not enough resources to produce in this factory") );

        $resources_check = $resources;
        $discarded_resources = [];
        $notify_resources = [];
        foreach($required_resources as $required_resource)
        {
            $found = false;
            foreach($resources_check as $index => $resource)
            {
                if($required_resource == $resource['card_type'])
                {
                    $discarded_resources[] = $resource['card_id'];
                    $notify_resources[] = $resource;
                    $resources_check[$index] = null;
                    $found = true;
                    break;
                }
            }

            if($found == false)
                throw new BgaUserException( self::_("The factory doesn't have the required resources to operate") );
        }

        // send resources to haymarket
        $resource_ids = implode( $discarded_resources, ',' );
        self::DbQuery("UPDATE card SET 
            card_location = 'haymarket',
            owner_type = NULL,
            card_location_arg = 0
            WHERE card_id IN (${resource_ids})");

        self::notifyAllPlayers( "resourcesConsumed", clienttranslate( '${company_name} consumes resources and sends them to Haymarket Square'), array(
            'company_name' => self::getCompanyName($short_name),
            'company_short_name' => $short_name,
            'resources' => $notify_resources
        ) );

        // get number of goods to produce
        $goods_produced = $factory_material['goods'];

        // check if factory is fully automated
        $required_automations = $factory_material['automation'];
        $factory_automations = self::getUniqueValueFromDB(
            "SELECT COUNT(card_id) FROM card 
            WHERE primary_type = 'automation' AND card_location LIKE '${short_name}_worker_holder_${factory_number}%'");
        if($required_automations == $factory_automations)
            $goods_produced++;
        
        // produce goods
        self::companyProduceGoods($short_name, $company_id, $goods_produced);

        // check if second factory to gain partner
        if($factory_number == 2 || ($factory_number == 3 && $short_name == 'henderson'))
        {
            $initial_company_id = self::getUniqueValueFromDB("SELECT initial_company_id FROM player WHERE player_id = $player_id");
            if($initial_company_id == $company_id)
                self::gainPartner($player_id, 'company');
        }

        // check if manager and execute action
        $manager = self::getUniqueValueFromDB("SELECT card_id FROM card WHERE primary_type = 'manager' AND card_location = '${short_name}_${factory_number}'");
        
        $appeal_bonus_gained = false;
        $resources_gained = false;
        if($manager != null)
        {
            self::notifyAllPlayers( "notifyManagerBonus", clienttranslate( '${company_name} receives manager bonus'), array(
                'company_name' => self::getCompanyName($short_name)
            ) );

            
            foreach($factory['manager_bonus'] as $bonus_type => $bonus)
            {
                switch($bonus_type)
                {
                    case 'good':
                        self::companyProduceGoods($short_name, $company_id, $bonus);
                        break;
                    case 'resource':
                        self::setGameStateValue( "resources_gained", $bonus );
                        $resources_gained = true;
                        break;
                    case 'appeal':
                        $appeal_bonus_gained = self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], $bonus);
                        break;
                }
            }
        }

        if($factory_number == count($company_material['factories']))
        {
            if($company['extra_goods'] > 0)
            {
                // if last factory, produce extra goods if any
                self::companyProduceGoods($short_name, $company_id, $company['extra_goods']);
            }

            if($resources_gained)
            {
                // go to resource bonus state
                $this->gamestate->nextState( 'managerBonusResources' );
                return;
            }

            if($appeal_bonus_gained)
            {
                $this->gamestate->nextState( 'managerBonusAppeal' );
                return;
            }

            // go to state distributeGoods
            $this->gamestate->nextState( 'distributeGoods' );
        }
        else
        {
            // else go to next factory, state nextFactory
            // update last_factory_produced
            self::setGameStateValue( "last_factory_produced", $factory_number );

            if($resources_gained)
            {
                // go to resource bonus state
                $this->gamestate->nextState( 'managerBonusResources' );
                return;
            }

            if($appeal_bonus_gained)
            {
                $this->gamestate->nextState( 'managerBonusAppeal' );
                return;
            }

            $this->gamestate->nextState( 'nextFactory' );
        }
    }

    function distributeGoods($demand_ids)
    {
        self::checkAction( 'distributeGoods' );

        $demand_ids = explode(',', $demand_ids); // tranform string into array
        if(count($demand_ids) == 0)
            throw new BgaVisibleSystemException("Cannot distribute 0 goods");

        $goods_to_distribute_by_location = [];
        $unique_demand_tiles = [];
        foreach($demand_ids as $demand_id)
        {
            if(!isset($goods_to_distribute_by_location[$demand_id]))
            {
                $unique_demand_tiles[] = "'demand${demand_id}'";
                $goods_to_distribute_by_location[$demand_id] = 1;
            }
            else
            {
                $goods_to_distribute_by_location[$demand_id] += 1;
            }
        }

        // get goods of the current company
        $company_id = self::getGameStateValue('current_company_id');
        $company = self::getNonEmptyObjectFromDb("SELECT id, short_name, income FROM company WHERE id = $company_id");
        $short_name = $company['short_name'];
        $goods = self::getObjectListFromDb("SELECT card_id FROM card WHERE primary_type = 'good' AND card_location = '$short_name'");
        $income = $company['income'];
        $company_material = $this->companies[$short_name];
        $company_type = $company_material['type']; // dry_goods
        $first_type = explode('_', $company_type)[0]; // dry

        // check that demand_ids count = company goods
        if(count($goods) < count($demand_ids))
            throw new BgaVisibleSystemException("Not enough goods to distribute");

        // get goods already on those demand tiles
        $goods_by_location = self::getCollectionFromDB(
            "SELECT card_location, COUNT(card_id) AS total_goods  
            FROM card 
            WHERE primary_type = 'good' AND card_location LIKE 'demand%'
            GROUP BY card_location");
        
        // get the demand tiles to know their locations
        $in_clause = implode(',', $unique_demand_tiles);
        $demand_tiles = self::getCollectionFromDB(
            "SELECT card_type, card_location
            FROM card
            WHERE card_type IN ($in_clause)");

        foreach($demand_tiles as $demand_tile)
        {
            $demand_type = explode('_', $demand_tile['card_location'])[0];
            if($demand_type != $first_type)
                throw new BgaVisibleSystemException("Company type does not match demand type");
        }

        // get value of goods
        $number_salespeople = self::getUniqueValueFromDb(
            "SELECT COUNT(card_id) FROM card
            WHERE primary_type = 'salesperson' AND card_location = '$short_name'");
        
        $value_of_goods = $company_material['salesperson'][$number_salespeople];

        // check that is enough space on demand tiles for distributed goods
        foreach($goods_to_distribute_by_location as $demand_id => $number_of_goods_to_distribute)
        {
            $demand_material = $this->demand["demand${demand_id}"];
            $total_demand = $demand_material['demand'];

            $current_goods = 0;
            if(isset($goods_by_location["demand${demand_id}"]))
            {
                $current_goods = $goods_by_location["demand${demand_id}"]['total_goods'];
            }

            if($current_goods + $number_of_goods_to_distribute > $total_demand)
                throw new BgaVisibleSystemException("Not enough space to distribute all goods");
            
            if($current_goods + $number_of_goods_to_distribute == $total_demand)
            {
                // add bonus to income
                $demand_tile = $demand_tiles["demand${demand_id}"];
                $location = $demand_tile['card_location'];
                $split = explode('_', $location);
                $bonus = $split[count($split) - 1];
                $income += $bonus;
            }

            // add value of goods to income
            $income += $number_of_goods_to_distribute * $value_of_goods;

            // update goods location
            $values = [];
            for($i = 0; $i < $number_of_goods_to_distribute; $i++)
            {
                $good = array_pop($goods);
                $values[] = $good['card_id'];
            }

            $good_ids = implode(',', $values);
            self::DbQuery(
                "UPDATE card SET
                owner_type = NULL,
                card_location = 'demand${demand_id}'
                WHERE card_id IN ($good_ids)");
        }

        // update income of company
        self::DbQuery("UPDATE company SET income = $income WHERE id = '$company_id'");

        // notify players
        $count = count($demand_ids);
        self::notifyAllPlayers( "goodsDistributed", clienttranslate( '${company_name} distributed ${count} goods for an operating revenue of $${income}' ), array(
            'company_name' => self::getCompanyName($short_name),
            'short_name' => $short_name,
            'count' => $count,
            'income' => $income,
            'demand_ids' => $demand_ids
        ) );

        $this->gamestate->nextState('dividends');
    }

    function skipProduceGoods()
    {
        self::checkAction( 'skipProduceGoods' );
        $this->gamestate->nextState( 'distributeGoods' );
    }

    function skipDistributeGoods()
    {
        self::checkAction( 'skipDistributeGoods' );

        $company_id = self::getGameStateValue( 'current_company_id');
        
        self::withholdEarnings($company_id);
    }

    function skipBuyResources()
    {
        self::checkAction( 'skipBuyResources' );
        $this->gamestate->nextState( 'produceGoods' );
    }

    function buyResources($resource_ids)
    {
        self::checkAction( 'buyResources' );
        
        $in_clause = "(${resource_ids})";
        $sql = "SELECT card_id, owner_type, card_location, card_type FROM card WHERE primary_type = 'resource' AND card_id IN $in_clause";
        $resources = self::getObjectListFromDB($sql);

        $cost = 0;
        $count = 0;
        $resource_array = [];
        $company_id = self::getGameStateValue( 'current_company_id');
        $company = self::getNonEmptyObjectFromDB("SELECT treasury, short_name FROM company WHERE id = $company_id");
        $company_short_name = $company['short_name'];
        foreach($resources as $resource)
        {
            if($resource['owner_type'] != null)
                throw new BgaVisibleSystemException("Resource is already owned");
            
            if($resource['card_location'] == 'haymarket')
                throw new BgaVisibleSystemException("Cannot buy resources from Haymarket Square");
            
            if($resource['card_location'] == 'x')
                throw new BgaVisibleSystemException("Cannot buy resources from X space");

            if($resource['card_location'] == '30')
                $cost += 30;
            if($resource['card_location'] == '20')
                $cost += 20;
            if($resource['card_location'] == '10')
                $cost += 10;
            
            $resource_array[$resource['card_id']] = [
                'from' => $resource['card_location'], 
                'card_location' => $company_short_name, 
                'card_id' => $resource['card_id'],
                'card_type' => $resource['card_type']];
            $count++;
        }
        
        $company_treasury = $company['treasury'];

        if($company_treasury < $cost)
            throw new BgaUserException( self::_("The company doesn't have enough money to buy these resources") );
        
        // update company treasury
        $company_treasury -= $cost;
        self::DbQuery("UPDATE company SET treasury = $company_treasury WHERE id = $company_id");

        
        $counters = [];
        self::addCounter($counters, "money_${company_short_name}", $company_treasury);

        // move resources to company
        self::DbQuery("UPDATE card SET 
            owner_type = 'company',
            card_location = '$company_short_name',
            card_location_arg = $company_id
            WHERE card_id IN $in_clause");
        
        // notify all players
        self::notifyAllPlayers( "resourcesBought", clienttranslate( '${company_name} bought ${count} resources for $${cost}' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'count' => $count,
            'cost' => $cost,
            'counters' => $counters,
            'resource_ids' => $resource_array
        ) );

        $this->gamestate->nextState( 'playerProduceGoodsPhase' );
    }

    // this function is called when buying an asset tile and the immediate bonus is an automation
    function automateFactory( $company_short_name, $factory_number, $relocate_number )
    {
        self::checkAction( 'automateFactory' );

        self::automateWorker($company_short_name, $factory_number, $relocate_number);

        $next_appeal_bonus = self::getGameStateValue('next_appeal_bonus');
        $final_appeal_bonus = self::getGameStateValue('final_appeal_bonus');
        if($next_appeal_bonus == $final_appeal_bonus)
        {
            // set state to next game state
            $this->gamestate->nextState( 'freeActions' );
        }
        else
        {
            // this can happen only with price protection
            // first the player receives the automation
            // then if there is an appeal bonus, go to that state
            $this->gamestate->nextState( 'appealBonus' );
        }
    }

    function hireWorker( $company_short_name, $factory_number)
    {
        self::checkAction( 'hireWorker' );

        self::hireWorkerFromSupply($company_short_name, $factory_number);

        // set state to next game state
        $this->gamestate->nextState( 'freeActions' );
    }

    function skipAssetBonus()
    {
        self::checkAction( 'skipAssetBonus' );

        // set state to next game state
        $this->gamestate->nextState( 'gameActionPhase' );
    }

    function useAsset( $asset_name )
    {
        self::checkAction( 'useAsset' );

        $player_id = $this->getCurrentPlayerId(); // CURRENT!!! not active

        $asset = self::getNonEmptyObjectFromDB("SELECT card_id, owner_type, card_location, card_location_arg FROM card WHERE card_type = '$asset_name' AND primary_type = 'asset'");

        if($asset['owner_type'] != 'company')
            throw new BgaVisibleSystemException("Asset is not on a company charter");
        
        if($asset['card_location_arg'] == 1)
            throw new BgaVisibleSystemException("Asset has already been used this decade");

        $short_name = $asset['card_location'];
        $company = self::getNonEmptyObjectFromDB("SELECT id, owner_id, appeal, treasury FROM company WHERE short_name = '$short_name'");
        $company_id = $company['id'];

        if($company['owner_id'] != $player_id)
            throw new BgaVisibleSystemException("Company is not owned by you");

        $asset_material = $this->capital_asset[$asset_name];
        self::notifyAllPlayers( "assetUsed", clienttranslate('${company_name} uses ${asset_name}'), array(
            'company_name' => self::getCompanyName($short_name),
            'asset_name' => $asset_material['name'],
            'asset_short_name' => $asset_name
        ) );
        
        $appeal_bonus_gained = false;
        switch($asset_name)
        {
            case 'color_catalog':
                self::increaseIncome($short_name, 60);
                break;
            case 'brand_recognition':
                $appeal_bonus_gained = self::increaseCompanyAppeal($short_name, $company_id, $company['appeal'], 2);
                break;
            case 'catalogue_empire':
                self::increaseIncome($short_name, 80);
                break;
            case 'mail_order_catalogue':
                self::increaseIncome($short_name, 40);
                break;
            case 'popular_partners':
            case 'backroom_deals':
                $state = $this->gamestate->state();
                if( $state['name'] != 'playerDistributeGoodsPhase' && $state['name'] != 'playerDividendsPhase' )
                    throw new BgaUserException( self::_("Capital Asset can only be used after the production step of the Operation Phase") );
                // increase share value one step
                self::increaseShareValue($short_name, 1);
                break;
            case 'brilliant_marketing':
                $appeal_bonus_gained = self::increaseCompanyAppeal($short_name, $company_id, $company['appeal'], 1);
                break;
            case 'union_stockyards':
            case 'michigan_lumber':
            case 'pennsylvania_coal':
            case 'cincinnati_steel':
                // check enough money
                $cost = 10;
                if($company['treasury'] < $cost)
                    throw new BgaVisibleSystemException("Company doesn't have enough money to pay for this action");
                $new_company_treasury = $company['treasury'] - $cost;
                $counters = [];
                self::DbQuery("UPDATE company SET treasury = $new_company_treasury WHERE id = $company_id");
                self::addCounter($counters, "money_${short_name}", $new_company_treasury);

                // notify payment
                self::notifyAllPlayers( "countersUpdated", clienttranslate('${company_name} pays the bank $${cost}'), array(
                    'cost' => $cost,
                    'company_name' => self::getCompanyName($short_name),
                    'counters' => $counters
                ) );
            case 'refrigeration':
            case 'foundry':
            case 'workshop':
            case 'abattoir':
                $resources_gained = $asset_material['resources'];
                self::gainResourcesAsset($short_name, $company_id, $resources_gained);
                break;
        }

        // exhaust asset tile
        $asset_id = $asset['card_id'];
        self::DbQuery("UPDATE card SET card_location_arg = 1 WHERE card_id = $asset_id");

        if($appeal_bonus_gained)
        {
            // depeding on current state, this leads to a different state
            $this->gamestate->nextState( 'freeAppealBonus' );
        }
        else
        {
            $this->gamestate->nextState( 'loopback' );
        }
    }

    function buildingAction( $building_action, $company_short_name, $factory_number, $action_args )
    {
        self::checkAction( 'buildingAction' );
        $player_id = $this->getCurrentPlayerId(); // CURRENT!!! not active

        $building_material = null;
        $building = null;
        $player_limit = true;
        // check if general action space
        if(isset($this->general_action_spaces[$building_action]))
        {
            $building_material = $this->general_action_spaces[$building_action];
            $player_limit = $building_material['player_limit'];
        }
        else 
        {
            // check if action is available (building has been played)
            $sql = "SELECT card_id, card_location FROM card WHERE primary_type = 'building' AND card_type = '$building_action'";
            $building = self::getObjectFromDB($sql);

            if($building == null){
                throw new BgaVisibleSystemException("Building does not exist");
            }

            $building_material = $this->building[$building_action];
        }

        // check if spot is available
        if($player_limit)
        {
            // get workers in that spot
            $sql = "SELECT COUNT(card_id) FROM card WHERE primary_type = 'partner' AND card_location = '$building_action'";
            $other_partners = self::getUniqueValueFromDB($sql);
            if($other_partners != 0)
                throw new BgaVisibleSystemException("There is already another partner on this action space");
        }

        // check if company owned by player
        $sql = "SELECT id, owner_id, treasury, appeal, short_name FROM company WHERE short_name = '$company_short_name' AND owner_id = '$player_id'";
        $company = self::getObjectFromDB($sql);
        if($company == null)
            throw new BgaVisibleSystemException("The selected company is not owned by current player");
        $company_id = $company['id'];

        // check if player has workers left
        $player = self::getPlayer($player_id);
        $number_of_partners = $player['current_number_partners'];
        if($number_of_partners == 0)
            throw new BgaVisibleSystemException("You don't have any more workers");
        
        // TODO: change the way we create partners to use total number of partners as well so ID is ascending
        $total_number_partners = $player['number_partners'];
        $worker_number = $total_number_partners - $number_of_partners + 1;
        $worker_id = "worker_${player_id}_${worker_number}";
        
        // update player's number of partners
        $number_of_partners--;
        $sql = "UPDATE player SET current_number_partners = $number_of_partners WHERE player_id = $player_id";
        self::DbQuery($sql);

        $counters = [];
        self::addCounter($counters, "partner_current_${player_id}", $number_of_partners);

        $company_material = $this->companies[$company_short_name];
        $company_name = $company_material['name'];
        // notify players that partner was played
        self::notifyAllPlayers( "actionUsed", clienttranslate( '${player_name} used an action on behalf of ${company_name}' ), array(
            'player_name' => self::getActivePlayerName(),
            'company_name' => $company_name,
            'worker_id' => $worker_id,
            'building_action' => $building_action,
            'counters' => $counters,
        ) );
        
        // check if cost can be payed
        $cost = 0;
        $new_company_treasury = $company['treasury'];
        $asset = null;
        if($building_action == 'job_market_worker')
        {
            $worker_factories = explode(',', $action_args);
            $number_of_workers = count($worker_factories);
            $cost = self::getCostToHireWorkers($number_of_workers);
        } 
        else if ($building_action == 'capital_investment' || 
            $building_action == 'building1' ||
            $building_action == 'building19' ||
            $building_action == 'building40')
        {
            $asset_args = explode(',', $action_args);
            $asset_id = intval($asset_args[0]);
            $asset = self::getObjectFromDB(
                "SELECT card_location, card_type, card_id FROM card 
                WHERE primary_type = 'asset' AND card_location <> 'discard' AND card_location <> 'asset_deck' AND card_id = $asset_id");
            
            if($asset == null)
                throw new BgaVisibleSystemException("Could not find selected asset");
            
            $asset_location = $asset['card_location'];
            $asset['card_location'] = $company_short_name; // change asset location
            $asset_cost = intval($asset_location);
            if($building_action == 'capital_investment')
                $cost = $asset_cost;
            
            if($building_action != 'capital_investment')
            {
                $cost = $building_material['cost'];

                // for the buildings, the bank pays the player for the building (dealt with normal logic)
                // and additionally the company pays the bank for the asset
                if($building_action == 'building1')
                    $asset_cost -= 10;
                if($building_action == 'building19')
                    $asset_cost -= 20;
                if($building_action == 'building40')
                    $asset_cost -= 30;
                
                $new_company_treasury -= $asset_cost;
                self::notifyAllPlayers( "additionalCost", clienttranslate('${company_name} pays the bank $${cost}'), array(
                    'cost' => $asset_cost,
                    'company_name' => $company_name,
                ) );
            }
        } 
        else 
        {
            $cost = $building_material['cost'];
        }

        $payment_method = $building_material['payment'];
        if($payment_method == 'companytobank' || 
            $payment_method == 'companytoplayer' || 
            $payment_method == 'companytoshareholders')
        {
            if($company['treasury'] < $cost)
                throw new BgaVisibleSystemException("Company doesn't have enough money to pay for this action");
            $new_company_treasury -= $cost;
        } else if ($payment_method == "banktocompany"){
            $new_company_treasury += $cost;
        }

        // pay for the action (company -> bank, company -> player, bank -> player, bank -> company, company -> shareholders)
        $message = null;
        $player_name = '';
        $payment_method = $building_material['payment'];
        switch($payment_method)
        {
            case 'companytobank':
                $message = clienttranslate('${company_name} pays the bank $${cost}');
                break;
            case 'banktocompany':
                $message = clienttranslate('Bank pays ${company_name} $${cost}');
                break;
            case 'companytoshareholders':
                $message = clienttranslate('${company_name} pays its shareholders $${cost}');
                break;
            case 'companytoplayer':
                $message = clienttranslate('${company_name} pays ${player_name} $${cost}');
                break;
            case 'banktoplayer':
                $message = clienttranslate('Bank pays ${player_name} $${cost}');
                break;
        }

        if($payment_method == 'companytoplayer' || $payment_method == 'banktoplayer')
        {
            $building_owner = self::getBuildingOwner($building);
            $player_id = $building_owner['player_id'];
            $player_name = $building_owner['player_name'];
            $new_player_treasury = $building_owner['treasury'] + $cost;

            $sql = "UPDATE player SET treasury = $new_player_treasury WHERE player_id = $player_id";
            self::DbQuery($sql);

            self::addCounter($counters, "money_${player_id}", $new_player_treasury);
        }

        // update company treasury
        $sql = "UPDATE company SET treasury = $new_company_treasury WHERE id = $company_id";
        self::DbQuery($sql);
        self::addCounter($counters, "money_${company_short_name}", $new_company_treasury);

        // notify payment
        self::notifyAllPlayers( "countersUpdated", $message, array(
            'cost' => $cost,
            'company_name' => $company_name,
            'counters' => $counters,
            'player_name' => $player_name
        ) );

        $values = [];
        $card_sql = "INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location, card_location_arg) VALUES ";
        
        // create partner in building location
        $values[] = "('player','partner','${worker_id}',$player_id,'$building_action',0)";

        $card_sql .= implode( $values, ',' );
        self::DbQuery($card_sql);

        // execute action
        $appeal_bonus_gained = false;
        switch($building_action){
            case 'job_market_worker':
                $worker_factories = explode(',', $action_args);
                self::hireWorkers($company_short_name, $worker_factories);
                break;
            case 'advertising':
                $appeal_bonus_gained = self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], 1);
                break;
            case 'building4':
                $appeal_bonus_gained = self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], 1);
                break;
            case 'building9':
                $appeal_bonus_gained = self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], 2);
                break;
            case 'building16':
                $appeal_bonus_gained = self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], 2);
                break;
            case 'building28':
                $appeal_bonus_gained = self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], 3);
                break;
            case 'building39':
                $appeal_bonus_gained = self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], 3);
                break;
            case 'hire_manager':
            case 'building11':
            case 'building14':
                self::hire_manager($company_short_name, $factory_number);
                break;
            case 'hire_salesperson':
            case "building2":
            case "building22":
                self::hire_salesperson($company_short_name);
                break;
            case 'fundraising_60':
                $round = self::getGameStateValue( "round" );
                if($round < 2)
                    throw new BgaVisibleSystemException("Action cannot be used before 3rd decade");
                break;
            case 'fundraising_80':
                $round = self::getGameStateValue( "round" );
                if($round < 4)
                    throw new BgaVisibleSystemException("Action cannot be used before 5th decade");
                break;
            case 'extra_dividends':
                self::distributeDividends($company_short_name, 100);
                break;
            case 'building29':
                self::distributeDividends($company_short_name, 150);
                break;
            case 'building30':
                self::distributeDividends($company_short_name, 200);
                break;
            case 'building31':
                self::distributeDividends($company_short_name, 250);
                break;
            case 'building32':
                self::distributeDividends($company_short_name, 300);
                break;
            case 'building10':
                self::companyProduceGoods($company_short_name, $company_id, 1);
                break;
            case 'building27':
                self::companyProduceGoods($company_short_name, $company_id, 2);
                break;
            case 'building41':
                self::companyProduceGoods($company_short_name, $company_id, 3);
                break;
            case 'building6':
            case "building24":
                $relocateFactoryNumber = intval($action_args);
                self::automateWorker($company_short_name, $factory_number, $relocateFactoryNumber);
                break;
            case "capital_investment":
            case "building1":
            case "building19":
            case "building40":
                $should_replace = boolval($action_args[1]);
                $bonus = self::gainAsset($company_name, $company, $asset, $should_replace);

                // When buying price protection, there are two bonuses
                // We increase appeal now, but we'll only gain the bonus after the automation takes place
                if($asset['card_type'] == 'price_protection')
                    self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], 2);

                switch($bonus)
                {
                    case 'worker':
                        $this->gamestate->nextState( 'workerBonus' );
                        break;
                    case 'automation':
                        $this->gamestate->nextState( 'automationBonus' );
                        break;
                    case 'appeal':
                        $appeal_bonus_gained = self::increaseCompanyAppeal($company_short_name, $company_id, $company['appeal'], 2);
                        break;
                }
                return;
            case 'building15':
            case "building3":
            case "building5":
            case "building7":
            case "building13":
            case "building17":
            case "building34":
            case "building36":
            case "building37":
            case "building38":
                $resources_gained = $building_material['resources'];
                self::gainResources($company_short_name, $company_id, $resources_gained, $action_args);
            break;
        }

        if($appeal_bonus_gained)
        {   
            $this->gamestate->nextState( 'appealBonus' );
        }
        else
        {
            // set state to next game state
            $this->gamestate->nextState( 'freeActions' );
        }
    }

    function selectBuildings($played_building_id, $discarded_building_id)
    {
        self::checkAction( 'selectBuildings' );
        $player_id = $this->getCurrentPlayerId(); // CURRENT!!! not active

        if($played_building_id == $discarded_building_id)
            throw new BgaVisibleSystemException("Cannot play and discard same building");
       
        // get player's buildings
        $sql = "SELECT card_id AS card_id, card_location AS card_location FROM card WHERE primary_type = 'building' AND card_location LIKE 'player_$player_id%'";
        $buildings = self::getCollectionFromDB($sql);

        if(!isset($buildings[$played_building_id]))
            throw new BgaVisibleSystemException("Building does not belong tu current player");
        
        if(!isset($buildings[$discarded_building_id]))
            throw new BgaVisibleSystemException("Building does not belong tu current player");

        // update building location to 'waiting' location
        foreach($buildings as $building_id => $building)
        {
            $id = $building_id;
            $sql = "";
            if($building_id == $played_building_id)
            {
                $sql = "UPDATE card SET card_location = 'player_${player_id}_play' WHERE card_id = $id";
            }
            else if($building_id == $discarded_building_id)
            {
                $sql = "UPDATE card SET card_location = 'player_${player_id}_discard' WHERE card_id = $id";
            }
            else
            {
                $sql = "UPDATE card SET card_location = 'player_${player_id}' WHERE card_id = $id";
            }

            self::DbQuery($sql);
        }

        // set multiplayer inactive
        $this->gamestate->setPlayerNonMultiactive($player_id, 'gameActionPhaseSetup'); // deactivate player; if none left, transition to 'next' state
    }

    function skipBuy()
    {
        self::checkAction( 'skipBuy' );
        $this->gamestate->nextState( 'gameStockPhase' );
    }

    function skipSell()
    {
        self::checkAction( 'skipSell' );
        $this->gamestate->nextState( 'playerSkipSellBuyPhase' );
    }

    function passStockAction()
    {
        self::checkAction( 'passStockAction' );
        self::incGameStateValue( "consecutive_passes", 1 );
        $this->gamestate->nextState( 'gameStockPhase' );
    }

    function buyCertificate($certificate)
    {
        self::checkAction( 'buyCertificate' );

        if($certificate == '')
            throw new BgaVisibleSystemException("Certificate identifier is empty");

        $split = explode('_', $certificate);
        $short_name = $split[0];
        $stock_type = $split[1];
        $card_id = $split[2];

        if($stock_type == 'director')
            throw new BgaVisibleSystemException("Can't buy director's share");
        
        // check if share available
        $sql = "SELECT card_id AS card_id, owner_type AS owner_type, card_type AS card_type FROM card WHERE card_id = $card_id";
        $stock = self::getObjectFromDB( $sql );
        if($stock == null)
            throw new BgaVisibleSystemException("Share is not available");

        $player_id = self::getActivePlayerId();
        $round = self::getGameStateValue( "round" );
        $sql = "SELECT company_short_name FROM sold_shares WHERE round = $round AND player_id = $player_id";
        $companies_sold_shares = self::getCollectionFromDB($sql);

        if(isset($companies_sold_shares[$short_name]))
        {
            $name = $this->companies[$short_name]['name'];
            throw new BgaUserException( self::_("You can't buy shares from ${name} because you sold shares from that company this decade") );
        }

        $player = self::getPlayer($player_id);
        $share_values = self::getShareValues();
        $share_value = $share_values[$short_name]['value'];

        $multiplier = 1;
        if($stock_type == 'director')
            $multiplier = 3;
        else if($stock_type == 'preferred')
            $multiplier = 2;
        
        // check if player can buy share
        if($player['treasury'] < $share_value * $multiplier)
            throw new BgaUserException( self::_("You don't have enough money to buy this certificate") );
        
        // check if certificate limit reached
        $player_shares = self::getPlayerShares($player_id);
        $player_number = self::getPlayersNumber();
        $certificate_limit = 14;
        if($player_number == 2)
            $certificate_limit = 10;
        else if($player_number == 3)
            $certificate_limit == 12;
        if(count($player_shares) == $certificate_limit)
            throw new BgaUserException( self::_("You have reached your certificate limit") );
        
        // check if 60% limit in single company reached
        $owned_share = $multiplier;
        foreach($player_shares as $player_share)
        {
            $split = explode('_', $player_share['card_type']);
            $owned_short_name = $split[0];
            $owned_stock_type = $split[1];

            if($owned_short_name != $short_name)
                continue;

            if($owned_stock_type == 'director' || $owned_stock_type == 'preferred')
            {
                if($stock_type == 'director' || $stock_type == 'preferred')
                    throw new BgaUserException( self::_("You cannot own both the director's certificate and the preferred certificate") );
            }

            if($owned_stock_type == 'preferred')
            {
                $owned_share += 2;
            }
            else if ($owned_stock_type == 'director')
            {
                $owned_share += 3;
            }
            else if ($owned_stock_type == 'common')
            {
                $owned_share += 1;
            }
            else
            {
                throw new BgaVisibleSystemException("Found impossible stock type when checking player's shares");
            }
        }

        if($owned_share > 6)
            throw new BgaUserException( self::_("You cannot own more than 60% of any one company") );
        
        // TODO advanced game: check if player gains ownership of company

        // update player treasury
        $certificate_cost = $share_value * $multiplier;
        $new_treasury = $player['treasury'] - $certificate_cost;
        $sql = "UPDATE player 
            SET treasury=$new_treasury
            WHERE player_id='$player_id'";
        self::DbQuery( $sql );

        $money_id = "money_${player_id}";
        $counters = [$money_id => ['counter_name' => $money_id, 'counter_value' => $new_treasury]];

        // update company treasury if bought from company
        // also set where the stock comes from for notification purposes
        $from = "";
        if($stock['owner_type'] == 'company')
        {
            $sql = "SELECT treasury AS treasury FROM company WHERE short_name='$short_name'";
            $company_treasury = self::getUniqueValueFromDB($sql);
            $new_company_treasury = $company_treasury + $certificate_cost;

            $sql = "UPDATE company 
            SET treasury=$new_company_treasury
            WHERE short_name='$short_name'";
        
            self::DbQuery( $sql );

            $company_money_id = "money_${short_name}";
            $counters[$company_money_id] =  ['counter_name' => $company_money_id, 'counter_value' => $new_company_treasury];

            $from = "available_shares_company";
        }
        else if($stock['owner_type'] == 'bank')
        {
            $from = "available_shares_bank";
        }
        else
        {
            throw new BgaVisibleSystemException("Can only buy share from bank or company");
        }

        // update stock location
        $card_id = $stock['card_id'];
        $stock['owner_type'] = 'player';
        $stock['card_location'] = 'personal_area_'.$player_id;
        $stock['card_location_arg'] = $player_id;
        $sql = "UPDATE card SET 
                owner_type = 'player',
                card_location = 'personal_area_$player_id',
                card_location_arg = $player_id
            WHERE card_id = $card_id";
        self::DbQuery( $sql );

        // notify all players
        // company and player treasury changed
        // share certificate added to player area
        $string_stock_type = $stock_type;
        if($stock_type == 'director')
            $string_stock_type = "director's";
        $company_material = $this->companies[$short_name];

        self::notifyAllPlayers( "certificateBought", clienttranslate( '${player_name} bought a ${string_stock_type} stock from ${company_name}' ), array(
            'player_id' => $player_id,
            'string_stock_type' => $string_stock_type,
            'player_name' => self::getActivePlayerName(),
            'company_name' => $company_material['name'],
            'short_name' => $company_material['short_name'],
            'stock' => $stock,
            'counters' => $counters,
            'from' => $from // can be bank or company_stock_holder_${short_name}
        ) );
        
        self::setGameStateValue( "consecutive_passes", 0 );
        $this->gamestate->nextState( 'gameStockPhase' );
    }

    function sellShares($selected_shares)
    {
        self::checkAction( 'sellShares' );

        $player_id = self::getActivePlayerId();

        if(count($selected_shares) == 0)
            throw new BgaVisibleSystemException("Cannot sell zero shares");

        $in_clause = "(".implode(",", $selected_shares).")";

        $sql = "SELECT * FROM card WHERE card_id IN $in_clause";
        $stocks = self::getObjectListFromDB($sql);

        $companies_selling = [];

        // check that shares can be sold
        foreach($stocks as $stock)
        {
            if($stock['primary_type'] != 'stock')
                throw new BgaVisibleSystemException("This is not a stock");

            $card_type = $stock['card_type'];
            if($stock['owner_type'] != 'player')
                throw new BgaVisibleSystemException("${card_type} does not belong to any player");
            
            $split = explode('_', $card_type);
            $short_name = $split[0];
            $stock_type = $split[1];

            if($stock['card_location_arg'] != $player_id)
                throw new BgaVisibleSystemException("${card_type} does not belong to active player");
            
            // basic game -> cannot sell director's share
            if($stock_type == 'director')
                throw new BgaUserException( self::_("You cannot sell a director's share in the basic game") );
            
            $multiplier = 1;
            if($stock_type == 'director')
                $multiplier = 3;
            else if($stock_type == 'preferred')
                $multiplier = 2;
            
            if(array_key_exists($short_name, $companies_selling))
            {
                $current_lost_value = $companies_selling[$short_name]['lost_value'];
                $companies_selling[$short_name] = $current_lost_value + $multiplier;
            }
            else
            {
                $companies_selling[$short_name] = ['company_id' => $stock['card_type_arg'], 'short_name' => $short_name, 'lost_value' => $multiplier];
            }
            
            
            // TODO -> advanced game condition for selling director's share
        }

        // sell shares
        $share_values = self::getShareValues();
        $money_gained = 0;
        foreach($stocks as $stock)
        {
            $card_id = $stock['card_id'];
            $card_type = $stock['card_type'];
            $split = explode('_', $card_type);
            $short_name = $split[0];
            $stock_type = $split[1];

            $multiplier = 1;
            if($stock_type == 'director')
                $multiplier = 3;
            else if($stock_type == 'preferred')
                $multiplier = 2;

            $share_value = $share_values[$short_name]['value'];

            $money_gained += $share_value*$multiplier;

            // update card location to bank
            $sql = "UPDATE card SET card_location='bank', owner_type='bank', card_location_arg=0 WHERE card_id=$card_id";
            self::DbQuery( $sql );

            self::notifyAllPlayers( "shareSold", clienttranslate( '${player_name} sells share to the bank' ), array(
                'player_name' => self::getActivePlayerName(),
                'type' => $card_type,
                'id' => $card_id,
                'player_id' => $player_id
            ) );
        }

        // insert in sold companies table, this player can't buy shares from this company this decade
        $round = self::getGameStateValue( "round" );
        $sql = "INSERT INTO sold_shares (player_id, round, company_short_name) VALUES ";
        $values = [];
        foreach($companies_selling as $company)
        {
            $short_name = $company['short_name'];
            $values[] = "($player_id, $round, $short_name)";

            // also update companies to reflect new share value
            // make sure value is not less than 0
            $company_id = $company['company_id'];
            $lost_value = $company['lost_value'];
            $company = self::getNonEmptyObjectFromDB("SELECT share_value_step, id FROM company WHERE id = $company_id");
            $share_value_step = $company['share_value_step'];

            $new_share_value_step = 0;
            if($share_value_step - $lost_value > 0)
                $new_share_value_step = $share_value_step - $lost_value;

            self::DbQuery( "UPDATE company SET share_value_step = $new_share_value_step WHERE id = $company_id" );

            $new_share_value = self::getShareValue($new_share_value_step);
            self::notifyAllPlayers( "shareValueChange", clienttranslate( 'The share value of ${company_name} decreases to $${share_value}' ), array(
                'company_short_name' => $short_name,
                'company_name' => self::getCompanyName($short_name),
                'share_value' => $new_share_value,
                'share_value_step' => $new_share_value_step,
                'previous_share_value_step' => $share_value_step
            ));
        }

        // update player treasury
        $player = self::getNonEmptyObjectFromDB("SELECT player_id, treasury FROM player WHERE player_id = ${player_id}");
        $new_treasury = $player['treasury'] + $money_gained;
        self::DbQuery("UPDATE player SET treasury=$new_treasury WHERE player_id='$player_id'");

        $counters = [];
        self::addCounter($counters, "money_${player_id}", $new_treasury);
        self::notifyAllPlayers( "countersUpdated", "", array(
            'counters' => $counters,
        ) );

        self::setGameStateValue( "consecutive_passes", 0 );
        $this->gamestate->nextState( 'playerBuyPhase' );
    }

    function startCompany($company_short_name, $initial_share_value_step)
    {        
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'startCompany' );

        $state = $this->gamestate->state();
        if($state['name'] == 'playerSkipSellBuyPhase' || $state['name'] == 'playerBuyPhase')
        {
            $round = self::getGameStateValue( "round" );
            if($round == 0)
                throw new BgaVisibleSystemException( self::_("You can't start a new company on the first round") );
        }

        $company = self::getCompanyByShortName($company_short_name);

        // check if company is in DB = owned by a player
        if($company != null)
            throw new BgaVisibleSystemException("This company is already owned");

        // check if company can be created (i.e., meterial)
        $companyMaterial = $this->companies[$company_short_name];
        if($companyMaterial == null)
            throw new BgaVisibleSystemException("This company does not exist");
        
        // check if share value is possible
        if($initial_share_value_step < 4 || $initial_share_value_step > 7)
            throw new BgaVisibleSystemException("initial share value step must be 4, 5, 6, 7, but not {$initial_share_value_step}");
        
        $player_id = self::getActivePlayerId();

        $player = self::getPlayer($player_id);
        
        $share_value = self::getShareValue($initial_share_value_step);

        if($player['treasury'] < $share_value*3)
            throw new BgaUserException( self::_("You don't have enough money to start this company") );

        // get companies order and appeal to set company turn order
        $initial_appeal = $companyMaterial['initial_appeal'];
        $query_result = self::getPreviousAndNextCompany($initial_appeal, $company_short_name, $player_id);
        $next_company_id = $query_result['next_company_id'];
        $next_company_id = $next_company_id == null ? 'NULL' : $next_company_id;
        
        // create company in database
        $company_treasury = $share_value*3;
        $sql = "INSERT INTO company (appeal, next_company_id, short_name,treasury,owner_id,share_value_step) 
            VALUES ($initial_appeal, $next_company_id,'$company_short_name',$company_treasury,$player_id,$initial_share_value_step)";
        self::DbQuery( $sql );
        $company_id = self::DbGetLastId();

        // update previous company in the turn order
        self::updatePreviousCompany($company_id, $query_result['previous_company_id']);

        // update player's treasury
        $newTreasury = $player['treasury'] - $share_value*3;
        if($state['name'] == 'playerStartFirstCompany')
        {
            // also set this company as initial company
            $sql = "UPDATE player 
                SET treasury='$newTreasury',
                    initial_company_id = $company_id
                WHERE player_id='$player_id'";
        }
        else
        {
            $sql = "UPDATE player 
                SET treasury='$newTreasury'
                WHERE player_id='$player_id'";
        }
        
        self::DbQuery( $sql );

        // create stocks and give director's share to player
        $initial_stocks = [
            ['owner_type' => 'player', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_director', 'card_type_arg' => $company_id, 'card_location' => 'personal_area_'.$player_id, 'card_location_arg' => $player_id],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_preferred', 'card_type_arg' => $company_id, 'card_location' => 'available_shares_company', 'card_location_arg' => $company_id],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'available_shares_company', 'card_location_arg' => 0],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'available_shares_company', 'card_location_arg' => 0],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'available_shares_company', 'card_location_arg' => 0],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'available_shares_company', 'card_location_arg' => 0],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'available_shares_company', 'card_location_arg' => 0],
        ];

        $values = [];
        $sql = "INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location, card_location_arg) VALUES";
        foreach( $initial_stocks as $stock )
        {
            $values[] = "('".$stock['owner_type']."','".$stock['primary_type']."','".$stock['card_type']."','".$stock['card_type_arg']."','".$stock['card_location']."','".$stock['card_location_arg']."')";
        }

        // create automation tokens
        $automation_tokens = [];
        foreach($companyMaterial['factories'] as $factory_number => $factory)
        {
            for($i = 0; $i < $factory['automation']; $i++)
            {
                $automation_tokens[] = ['owner_type' => 'company', 'primary_type' => 'automation', 'card_type' => $company_short_name.'_automation_'.$factory_number.'_'.$i, 'card_type_arg' => 0, 'card_location' => $company_short_name.'_automation_holder_'.$factory_number.'_'.$i, 'card_location_arg' => $company_id];
                $values[] = "('company','automation','".$company_short_name."_automation_".$factory_number."_".$i."',0,'".$company_short_name."_automation_holder_".$factory_number."_".$i."','".$company_id."')";
            }
        }

        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );

        // get the id of the stock that were just created
        $sql = "SELECT * FROM card WHERE primary_type = 'stock' AND card_type_arg = $company_id";
        $stocks = self::getObjectListFromDB($sql);

        // notify players that company started
        $money_id = "money_${player_id}";
        $company_money_id = "money_${company_short_name}";
        self::notifyAllPlayers( "startCompany", clienttranslate( '${player_name} has started company ${company_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'company_name' => $companyMaterial['name'],
            'short_name' => $companyMaterial['short_name'],
            'initial_share_value_step' => $initial_share_value_step,
            'owner_id' => $player_id,
            'automation_tokens' => $automation_tokens,
            'appeal' => $initial_appeal,
            'order' => $query_result['order'],
            'counters' => [
                $money_id => array ('counter_name' => $money_id, 'counter_value' => $newTreasury),
                $company_money_id => array ('counter_name' => $company_money_id, 'counter_value' => $company_treasury)],
            'stocks' => $stocks
        ) );
        
        self::setGameStateValue( "consecutive_passes", 0 );
        $this->gamestate->nextState( 'gameStartFirstCompany' );
    }

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    // need the round to not show start company action during first round
    function argPlayerBuyPhase()
    {
        $round = self::getGameStateValue( "round" );
        return ["round" => $round];
    }

    function argsPlayerActionPhase()
    {
        $round = self::getGameStateValue( "round" );
        return ["round" => $round];
    }

    function argsPlayerAssetBonus()
    {
        $id = self::getGameStateValue( "bonus_company_id" );
        $company = self::getNonEmptyObjectFromDB("SELECT short_name FROM company WHERE id=$id");
        $short_name = $company['short_name'];
        return [
            'company_name' => self::getCompanyName($short_name),
            'company_short_name' => $short_name,
            'next_appeal_bonus' => self::getGameStateValue('next_appeal_bonus')
        ];
    }

    function argsOperationPhase()
    {
        $id = self::getGameStateValue( "current_company_id" );
        $company = self::getNonEmptyObjectFromDB("SELECT short_name, income FROM company WHERE id=$id");
        $short_name = $company['short_name'];
        return [
            'company_name' => self::getCompanyName($short_name),
            'last_factory_produced' => self::getGameStateValue( "last_factory_produced" ),
            'company_short_name' => $short_name,
            'income' => $company['income']
        ];
    }

    function argsManagerBonusResources()
    {
        $id = self::getGameStateValue( "current_company_id" );
        $company = self::getNonEmptyObjectFromDB("SELECT short_name, income FROM company WHERE id=$id");
        $short_name = $company['short_name'];
        return [
            'company_name' => self::getCompanyName($short_name),
            'company_short_name' => $short_name,
            'resources_gained' => self::getGameStateValue( "resources_gained" )
        ];
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    function stGameOperationPhase()
    {
        // refill supply
        self::refillSupply();

        // if next company == null, go to cleanup phase
        $next_company_id = self::getGameStateValue( "next_company_id" );
        if($next_company_id == -1)
        {
            $round = self::getGameStateValue("round");
            if($round == 4)
            {
                $this->gamestate->nextState( 'gameEnd' );
                return;
            }

            $this->gamestate->nextState( 'cleanup' );
            return;
        }

        // else update current company and next company, then go back to beginning of operation phase
        $order = self::getCurrentCompanyOrder();

        foreach($order as $company)
        {
            if($company['id'] != $next_company_id)
                continue;
            
            $owner_id = $company['owner_id'];
            self::giveExtraTime( $owner_id );
            $this->gamestate->changeActivePlayer( $owner_id );
    
            self::setGameStateValue("next_company_id", $company['next_company_id'] == NULL ? -1 : $company['next_company_id']);
            self::setGameStateValue("current_company_id", $company["id"]);
            self::setGameStateValue("last_factory_produced", 0);
            break;
        }
        
        $this->gamestate->nextState( 'nextCompany' );
    }

    function stGameCleanup()
    {
        self::notifyAllPlayers( "cleanupPhase", clienttranslate("Cleanup phase"), array() );

        // refill supply
        self::refillSupply(true);

        // discard and refill asset tiles
        $asset = self::getNonEmptyObjectFromDB("SELECT card_type, card_id FROM card WHERE primary_type = 'asset' AND card_location = '40'");
        self::DbQuery("UPDATE card SET card_location = 'discard' WHERE primary_type = 'asset' AND card_location = '40'");
        self::notifyAllPlayers( "assetDiscarded", "", array(
            'asset_name' => $asset['card_type'],
            'asset_id' => $asset['card_id']
        ) );
        self::refillAssets();

        // discard and refill demand tiles
        self::refillDemand();

        // return partners to inventory
        $players = self::getObjectListFromDB("SELECT player_id, number_partners FROM player");
        self::DbQuery("UPDATE player SET current_number_partners = number_partners");
        self::DbQuery("DELETE FROM card WHERE primary_type = 'partner'");

        $counters = [];
        foreach($players as $player)
        {
            $player_id = $player['player_id'];
            self::addCounter($counters, "partner_current_${player_id}", $player['number_partners']);
        }
        self::notifyAllPlayers( "partnersReturned", "", array(
            'counters' => $counters
        ) );

        // change round
        self::incGameStateValue( "round", 1 );
        self::notifyAllPlayers( "newRound", clienttranslate("New round"), array() );

        // change phase
        self::setGameStateValue( "phase", 0 );

        // set active player as player with priority deal marker
        $player_id = self::getGameStateValue("priority_deal_player_id");
        self::giveExtraTime( $player_id );
        $this->gamestate->changeActivePlayer( $player_id );

        $this->gamestate->nextState( 'nextRound' );
    }

    function stGameActionPhaseSetup()
    {
        // get buildings that should be played or discarded
        $sql = "SELECT card_id AS card_id, card_type AS card_type, card_location AS card_location FROM card 
            WHERE primary_type = 'building' AND 
                (card_location LIKE '%_play' OR card_location LIKE '%_discard')
                AND card_location <> 'building_discard'";
        $buildings = self::getCollectionFromDB($sql);

        $player_number = self::getPlayersNumber();
        if(count($buildings) != 2 * $player_number)
            throw new BgaVisibleSystemException("Expected more buildings to play and discard");

        $number_of_workers_to_add = 0;

        $new_buildings = [];
        
        // update location to track
        foreach($buildings as $building_id => $building)
        {
            $sql = "";
            $split = explode('_', $building['card_location']);
            $building_action = $split[2];
            $player_id = $split[1];
            $building_number = $building['card_type'];
            if($building_action == 'play')
            {
                // compute number of workers to add to board
                $number_of_workers = $this->building[$building_number]['number_of_workers'];
                $number_of_workers_to_add += $number_of_workers;

                $new_buildings[$building_id] = $building;
                $new_buildings[$building_id]['card_location'] = "building_track_${player_id}";

                $sql = "UPDATE card SET card_location = 'building_track_${player_id}' WHERE card_id = $building_id";
            }
            else if($building_action == 'discard')
            {
                $sql = "UPDATE card SET card_location = 'building_discard' WHERE card_id = $building_id";
            }
            else
            {
                throw new BgaVisibleSystemException("Building should be marked as play or discard");
            }
            
            self::DbQuery($sql);
        }

        // get workers already on board (can't have more than 12 workers)
        $number_workers_market = self::getUniqueValueFromDB("SELECT COUNT(card_id) FROM card WHERE primary_type = 'worker' AND card_location = 'job_market'");
        if($number_of_workers_to_add + $number_workers_market > 12)
        {
            $number_of_workers_to_add = 12 - $number_workers_market;
        }

        // add workers to board
        $sql = "INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location,card_location_arg) VALUES ";
        $cards = [];
        for($i = 0; $i < $number_of_workers_to_add; $i++)
        {
            $cards[] = "(NULL,'worker','worker',0,'job_market',0)";
        }
        $sql .= implode( $cards, ',' );
        self::DbQuery( $sql );

        $sql = "SELECT * FROM card WHERE primary_type = 'worker'";
        $workers = self::getCollectionFromDB($sql);

        // notify players
        self::notifyAllPlayers( "buildingsSelected", clienttranslate( 'New buildings have been played to the board' ), array(
            'buildings' => $new_buildings
        ) );

        self::notifyAllPlayers( "workersAdded", clienttranslate( '${workers_added} new workers added to the job market' ), array(
            'workers_added' => $number_of_workers_to_add,
            'all_workers' => $workers
        ) );

        // get first player in turn order and set as active player
        $sql = "SELECT player_id FROM player WHERE player_order = 1";
        $player = self::getNonEmptyObjectFromDB($sql);
        $this->gamestate->changeActivePlayer( $player['player_id'] ); 

        // if round = 2 => add partner to player inventory
        $round = self::getGameStateValue("round");
        if($round == 2)
        {
            self::gainPartner(null, 'round');
        }

        // start action phase
        $this->gamestate->nextState( 'playerActionPhase' );
    }

    function stGameActionPhase()
    {
        $new_active_player = null;

        // get all players
        $sql = "SELECT player_id, player_order, current_number_partners FROM player ORDER BY player_order ASC";
        $players = self::getCollectionFromDB($sql);
        $tmp = array_values($players);
        $last_player = array_pop($tmp);

        // get active player
        $active_player_id = $this->getActivePlayerId();
        $active_player_order = $players[$active_player_id]['player_order'];

        if($last_player['player_id'] == $active_player_id)
        {
            // check if advertising was used and adjust turn order
            $sql = "SELECT card_id, card_type_arg FROM card WHERE primary_type = 'partner' AND card_location = 'advertising'";
            $advertising = self::getObjectFromDB( $sql );
            if($advertising != null)
            {
                $player_id_advertising = $advertising['card_type_arg'];
                $new_active_player = $players[$player_id_advertising];
                $player_order = 2;
                $order = 0;
                foreach($players as $player_id => $player)
                {
                    if($player_id == $player_id_advertising)
                    {
                        $order = 1;
                    }
                    else
                    {
                        $order = $player_order++;
                    }

                    $sql = "UPDATE player SET player_order = $order WHERE player_id = $player_id";
                    self::DbQuery($sql);
                }
            }
        }

        if($new_active_player == null)
        {
            foreach($players as $player)
            {
                if($player['player_order'] > $active_player_order && $player['current_number_partners'] > 0)
                {
                    $new_active_player = $player;
                    break;
                }    
            }
        }

        if($new_active_player == null)
        {
            foreach($players as $player)
            {
                if($player['player_order'] <= $active_player_order && $player['current_number_partners'] > 0)
                {
                    $new_active_player = $player;
                    break;
                }    
            }

            if($new_active_player == null)
            {
                // if no more partners go to playerOperationPhase
                // Active player = player with company that is first in appeal order
                // give extra time to player
                $order = self::getCurrentCompanyOrder();
                $first_company = $order[0];
                $owner_id = $first_company['owner_id'];
                self::giveExtraTime( $owner_id );
                $this->gamestate->changeActivePlayer( $owner_id );

                self::setGameStateValue("next_company_id", $first_company['next_company_id']);
                self::setGameStateValue("current_company_id", $first_company["id"]);
                self::setGameStateValue("last_factory_produced", 0);

                $this->gamestate->nextState( 'playerBuyResourcesPhase' );
                return;
            }
        }

        // if not last player
        // set active player in next player order (with partners > 0) and go to player action phase
        $new_player_id = $new_active_player['player_id'];
        $this->gamestate->changeActivePlayer( $new_player_id );
        self::giveExtraTime( $new_player_id );
        $this->gamestate->nextState( 'playerActionPhase' );
    }

    function stGameStockPhase()
    {
        $consecutive_passes = self::getGameStateValue( "consecutive_passes" );
        $player_number = self::getPlayersNumber();

        if($consecutive_passes == $player_number)
        {
            self::setGameStateValue( "consecutive_passes", 0 );
            self::setGameStateValue( "turns_this_phase", 0 );

            
            $player_id = self::getActivePlayerId();
            self::giveExtraTime( $player_id );

            // if everyone passes, the last player that did an action is necessarily the active player
            $next_player_id = self::getPlayerAfter( $player_id );
            self::setGameStateValue('priority_deal_player_id', $next_player_id);
            $player_info = self::loadPlayersBasicInfos()[$next_player_id];
            self::notifyAllPlayers( "dealMarkerReceived", clienttranslate( '${player_name} receives the priority deal marker' ), array(
                'player_name' => $player_info['player_name']
            ));

            // deal buildings
            $round = self::getGameStateValue('round');
            if($round > 0)
            {
                $era = 2;
                if($round == 3 || $round == 4)
                    $era = 3;

                $limit = 2 * $player_number;
                $buildings = self::getObjectListFromDB("SELECT card_id, card_type FROM card 
                        WHERE primary_type = 'building' AND card_location = 'era_${era}'
                        ORDER BY card_location_arg ASC
                        LIMIT $limit");
                
                $players = self::loadPlayersBasicInfos();
                foreach($players as $player_id => $player)
                {
                    $building1 = array_pop($buildings);
                    $building1_id = $building1['card_id'];
                    $building2 = array_pop($buildings);
                    $building2_id = $building2['card_id'];
                    $dealt_buildings = [$building1, $building2];
                    
                    self::DbQuery("UPDATE card SET 
                        owner_type = 'player',
                        card_location = 'player_${player_id}'
                        WHERE card_id = ${building1_id} OR card_id = ${building2_id}");

                    self::notifyPlayer($player_id, "buildingsDealt", clienttranslate( 'Deal new buildings' ), array(
                        'dealt_buildings' => $dealt_buildings));
                }
            }

            // check if stock in company is completely owned by players
            $stocks_by_company = self::getObjectListFromDB(
                "SELECT COUNT(card_id) AS number_stocks, card_type_arg FROM card
                WHERE primary_type = 'stock' AND owner_type = 'player'
                GROUP BY card_type_arg");
            $companies = self::getCollectionFromDB("SELECT id, short_name, share_value_step FROM company");
            foreach($stocks_by_company as $stock)
            {
                $count = $stock['number_stocks'];
                if($count == 7) // there are 7 certificates for each company increase share value one step
                {
                    $company_id = $stock['card_type_arg'];
                    $company = $companies[$company_id];
                    $company_short_name = $company['short_name'];
                    $previous_share_value_step = $company['share_value_step'];
                    $new_share_value_step = $previous_share_value_step;
                    if($new_share_value_step < 20)
                        $new_share_value_step++;
                    $new_share_value = self::getShareValue($new_share_value_step);
                    self::notifyAllPlayers( "shareValueChange", clienttranslate( '${company_name} is completely owned by players, the share value increases to $${share_value}' ), array(
                        'company_short_name' => $company_short_name,
                        'company_name' => self::getCompanyName($company_short_name),
                        'share_value' => $new_share_value,
                        'share_value_step' => $new_share_value_step,
                        'previous_share_value_step' => $share_value_step
                    ));
                }
            }

            $this->gamestate->nextState('playerBuildingPhase');
        }
        else
        {
            self::incGameStateValue( "turns_this_phase", 1 );
            // Activate next player
            $player_id = $this->activeNextPlayer();
            
            self::giveExtraTime( $player_id );
            $this->gamestate->nextState( 'playerStockPhase' );
        }
    }

    function st_MultiPlayerInit() {
        $this->gamestate->setAllPlayersMultiactive();
    }

    function gameStartFirstCompany()
    {
        $turns_this_phase = self::getGameStateValue( "turns_this_phase" );
        $player_number = self::getPlayersNumber();

        // update player order for action phase (reverse from company start order)
        $player_id = self::getActivePlayerId();
        $player_order = $player_number - $turns_this_phase;
        $sql = "UPDATE player SET player_order = $player_order WHERE player_id = '$player_id'";
        self::DbQuery( $sql );
        
        if($turns_this_phase+1 == $player_number)
        {
            // everyone has started a company, go to next phase with active player starting next phase
            self::setGameStateValue( "turns_this_phase", 0 );
            self::setGameStateValue( "consecutive_passes", 0 );

            $player_id = self::getActivePlayerId();
            self::giveExtraTime( $player_id );

            $this->gamestate->nextState('playerSellPhase');
        }
        else
        {
            self::incGameStateValue( "turns_this_phase", 1 );
            // Activate next player
            $player_id = $this->activePrevPlayer();
            
            self::giveExtraTime( $player_id );
            $this->gamestate->nextState( 'nextPlayer' );
        }
    }

//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];
    	
        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );
            
            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
    }
    
///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */
    
    function upgradeTableDb( $from_version )
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345
        
        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }    
}
