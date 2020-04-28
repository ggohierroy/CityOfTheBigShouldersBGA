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
        
        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
        $this->initializeBoardTokens();
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
        $sql = "SELECT player_id AS id, player_score AS score, treasury AS treasury, number_partners AS number_partners FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );
  
        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $sql = "SELECT id AS id, treasury AS treasury, appeal AS appeal, next_company_id AS next_company_id, share_value_step AS share_value_step, owner_id AS owner_id, short_name AS short_name FROM company";
        $owned_companies = self::getCollectionFromDb( $sql );
        $result['owned_companies'] = $owned_companies;
        $result['company_order'] = self::getCurrentCompanyOrder($owned_companies);

        $result['all_companies'] = $this->companies;

        // gather all items in card table
        $sql = "SELECT card_id AS card_id, owner_type AS owner_type, primary_type AS primary_type, card_type AS card_type, card_type_arg AS card_type_arg, card_location AS card_location, card_location_arg AS card_location_arg FROM card";
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
            $partner_id = "parter_${player_id}";

            $result ['counters'] [$money_id] = array ('counter_name' => "money_${player_id}", 'counter_value' => $player['treasury'] );
            //$result ['counters'] [$partner_id] = array ('counter_name' => $partner_id, 'counter_value' => $player['number_partners'] );
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
            array_unshift($order, ['id' => $previous_company['id'], 'owner_id' => $previous_company['owner_id'], 'order' => $i, 'short_name' => $previous_company['short_name'], 'appeal' => $previous_company['appeal']]);

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

        // insert rest of resources in the resource bag
        $number_of_items = count($resource_bag);
        for($i = 0; $i < $number_of_items; $i++)
        {
            $resource = array_pop($resource_bag);

            $cards[] = "(NULL,'resource','$resource','resource_bag','$i')";
        }

        $sql .= implode( $cards, ',' );
        self::DbQuery( $sql );
    }

    function initializeBoardTokens()
    {
        $sql = "INSERT INTO board_token (token_name, token_value) 
            VALUES
            ('round_marker',0),
            ('phase_marker',0),
            ('wokers_in_market',4),
            ('deal_player_id',NULL)";
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
            "SELECT player_id id, treasury treasury
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

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */

    
//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    
    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    function startCompany($company_short_name, $initial_share_value_step)
    {
        self::dump('company_name', $company_short_name);
        self::dump('initial_share_value_step', $initial_share_value_step);
        self::trace('startCompany');
        
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'startCompany' );

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
        $sql = "UPDATE player 
            SET treasury='$newTreasury'
            WHERE player_id='$player_id'";
        self::DbQuery( $sql );

        // create stocks and give director's share to player
        $initial_stocks = [
            ['owner_type' => 'player', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_director', 'card_type_arg' => $company_id, 'card_location' => 'personal_area_'.$player_id, 'card_location_arg' => $player_id],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_preferred', 'card_type_arg' => $company_id, 'card_location' => 'company_stock_holder_'.$company_short_name, 'card_location_arg' => $company_id],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'limbo', 'card_location_arg' => 0],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'limbo', 'card_location_arg' => 0],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'limbo', 'card_location_arg' => 0],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'limbo', 'card_location_arg' => 0],
            ['owner_type' => 'company', 'primary_type' => 'stock', 'card_type' => $company_short_name.'_common', 'card_type_arg' => $company_id, 'card_location' => 'limbo', 'card_location_arg' => 0],
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
            'stocks' => $initial_stocks
        ) );

        $this->gamestate->nextState( 'gameStartFirstCompany' );
    }

    function gameStartFirstCompany()
    {
        // Activate next player
        $player_id = self::activeNextPlayer();
        
        self::giveExtraTime( $player_id );
        $this->gamestate->nextState( 'nextPlayer' );
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
