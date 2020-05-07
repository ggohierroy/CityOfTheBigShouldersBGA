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
            "turns_this_phase" => 10,
            "round" => 11,
            "phase" => 12,
            "priority_deal_player_id" => 13,
            "consecutive_passes" => 14,
            "next_company_id" => 15, // we need this because the current company can change appeal during operation phase
            "current_company_id" => 16, // we need this to return company name in the operation phase args
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
        self::setGameStateInitialValue( 'turns_this_phase', 0 );
        self::setGameStateInitialValue( 'round', 0 );
        self::setGameStateInitialValue( 'consecutive_passes', 0 );
        self::setGameStateInitialValue( 'next_company_id', 0 );
        self::setGameStateInitialValue( 'current_company_id', 0 );

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

        // gather all items in card table that are visible to the player
        $sql = "SELECT card_id AS card_id, owner_type AS owner_type, primary_type AS primary_type, card_type AS card_type, card_type_arg AS card_type_arg, card_location AS card_location, card_location_arg AS card_location_arg
            FROM card
            WHERE
                (card_location <> 'demand_deck' AND
                card_location <> 'asset_deck' AND
                card_location <> 'era_2' AND
                card_location <> 'era_3' AND
                card_location <> 'resource_bag' AND
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

    function automateWorker($company_short_name, $factory_number, $relocateFactoryNumber)
    {
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
            $values = explode('_', $automation['card_location']);
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
            } else if ($values[1] == $relocateFactoryNumber){
                $workers_in_relocate_factory++;
            }
        }

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
        $worker_sent_to_market = false;
        if($total_workers + $total_automated + 1 == $total_automations)
        {
            $worker_id = $to_automate_worker['card_id'];
            $sql = "UPDATE card SET card_location = 'job_market' WHERE card_id = '$worker_id'";
            self::DbQuery($sql);
            $worker_sent_to_market = true;
        }
        else
        {
            // otherwise check that $relocateFactoryNumber has an empty spot and send it there
            if($relocateFactoryNumber != $factory_number)
            {
                $automation_in_relocate_factory = $this->companies[$company_short_name]['factories'][$relocateFactoryNumber]['automation'];  
                if($automated_in_relocate_factory + $workers_in_relocate_factory == $automation_in_relocate_factory)
                    throw new BgaUserException( self::_("The automated worker cannot be relocated to this factory") );
                
                $worker_id = $to_automate_worker['card_id'];
                $sql = "UPDATE card SET card_location = '${company_short_name}_${relocateFactoryNumber}' WHERE card_id = '$worker_id'";
                self::DbQuery($sql);
            }
        }

        // notify players
        // -> new worker location
        // -> new automation location
        self::notifyAllPlayers( "factoryAutomated", clienttranslate( '${company_name} automated a factory' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'company_short_name' => $company_short_name,
            'factory_number' => $factory_number,
            'worker_relocation' => $worker_sent_to_market ? 'job_market' : "${company_short_name}_${relocateFactoryNumber}"
        ));

        if($worker_sent_to_market){
            self::notifyAllPlayers( "workerSentToMarket", clienttranslate( 'Worker relocated to the job market' ), []);
        }
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

    function increaseCompanyAppeal($company_short_name, $current_appeal, $steps){

        $sql = "";
        $new_appeal = $current_appeal;
        if($current_appeal + $steps <= 16)
        {
            $new_appeal += $steps;
            $sql = "UPDATE company SET appeal = $new_appeal WHERE short_name = '$company_short_name'";
            self::DbQuery($sql);
        } else if ($current_appeal < 16)
        {
            $new_appeal = 16;
            $sql = "UPDATE company SET appeal = $new_appeal WHERE short_name = '$company_short_name'";
            self::DbQuery($sql);
        }

        self::notifyAllPlayers( "appealIncreased", clienttranslate( '${company_name} increased its appeal to ${appeal}' ), array(
            'company_name' => self::getCompanyName($company_short_name),
            'company_short_name' => $company_short_name,
            'appeal' => $new_appeal,
            'previous_appeal' => $current_appeal,
            'order' => self::getCurrentCompanyOrder()
        ) );
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

    // $factory_id -> 'brunswick_factory_1'
    function hireWorkers($number_of_workers, $company_short_name, $factory_number)
    {
        if(!$factory_number)
            throw new BgaVisibleSystemException("Unknown factory number");

        $condition = $company_short_name.'_'.$factory_number;
        
        $total_spots = $this->companies[$company_short_name]['factories'][$factory_number]['workers'];
        $sql = "SELECT COUNT(card_id) FROM card WHERE primary_type = 'worker' AND card_location LIKE '$condition%'";
        $number_of_workers_in_factory = self::getUniqueValueFromDB($sql);
        
        $available_spots = $total_spots - $number_of_workers_in_factory;
        if($available_spots < 0)
            throw new BgaVisibleSystemException("Negative spots available in factory");
        
        if($available_spots < $number_of_workers)
            throw new BgaVisibleSystemException("Not enough space for hired workers");

        $sql = "SELECT card_id FROM card WHERE primary_type = 'worker' AND card_location ='job_market'";
        $workers_in_market = self::getObjectListFromDB($sql);
        $number_of_workers_in_market = count($workers_in_market);
        $convert = [0 => 50, 1 => 40, 2 => 40, 3 => 40, 4 => 40, 5 => 30, 6 => 30, 7 => 30, 8 => 30, 9 => 20, 10 => 20, 11 => 20, 12 => 20];
        $cost = 0;
        $values = [];
        for($i = 0; $i < $number_of_workers; $i++){
            $cost += $convert[$number_of_workers_in_market];
            if($number_of_workers_in_market > 0){
                $worker = array_shift($workers_in_market);
                $values[] = $worker['card_id'];
                $number_of_workers_in_market--;
            }
        }

        // move these workers to the factory
        $sql = "UPDATE card SET card_location = '$condition', owner_type = 'company' WHERE card_id IN ";
        $sql .= "(".implode( $values, ',' ).")";
        self::DbQuery($sql);

        $company_material = $this->companies[$company_short_name];
        $company_name = $company_material['name'];

        self::notifyAllPlayers( "workersHired", clienttranslate( '${company_name} hired ${number_of_workers} workers from the job market' ), array(
            'factory_id' => $condition,
            'company_name' => $company_name,
            'worker_ids' => $values,
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
            "SELECT player_id AS id, treasury AS treasury, current_number_partners AS current_number_partners
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

    function skipBuyResources()
    {
        self::checkAction( 'buyResources' );
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
        $sql = "SELECT id AS id, owner_id AS owner_id, treasury AS treasury, appeal AS appeal FROM company WHERE short_name = '$company_short_name' AND owner_id = '$player_id'";
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
        $worker_id = "worker_${player_id}_${number_of_partners}";
        
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
        if($building_action == 'job_market_worker'){
            $number_of_workers = intval($action_args);
            $cost = self::getCostToHireWorkers($number_of_workers);
        } else if ($building_action == 'capital_investment'){
            // TODO: implement this
        } else {
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

        // execute action
        switch($building_action){
            case 'job_market_worker':
                self::hireWorkers($number_of_workers, $company_short_name, $factory_number);
                break;
            case 'advertising':
                self::increaseCompanyAppeal($company_short_name, $company['appeal'], 1);
                break;
            case 'building4':
                self::increaseCompanyAppeal($company_short_name, $company['appeal'], 1);
                break;
            case 'building9':
                self::increaseCompanyAppeal($company_short_name, $company['appeal'], 2);
                break;
            case 'building16':
                self::increaseCompanyAppeal($company_short_name, $company['appeal'], 2);
                break;
            case 'building28':
                self::increaseCompanyAppeal($company_short_name, $company['appeal'], 3);
                break;
            case 'building39':
                self::increaseCompanyAppeal($company_short_name, $company['appeal'], 3);
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
        }

        $values = [];
        $card_sql = "INSERT INTO card (owner_type, primary_type, card_type, card_type_arg, card_location, card_location_arg) VALUES ";
        
        // create partner in building location
        $values[] = "('player','partner','${worker_id}',$player_id,'$building_action',0)";

        $card_sql .= implode( $values, ',' );
        self::DbQuery($card_sql);

        // set state to next game state
        $this->gamestate->nextState( 'gameActionPhase' );
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
        if($stock_type = 'director')
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
        {
            $this->gamestate->nextState( 'playerBuyPhase' );
            return;
        }

        $in_clause = "(".implode(",", $selected_shares).")";

        $sql = "SELECT * FROM card WHERE card_id IN $in_clause";
        $stocks = self::getObjectListFromDB($sql);

        $companies_selling = [];

        // check that shares can be sold
        foreach($stocks as $stock)
        {
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
            $company_id = $companies_selling['company_id'];
            $lost_value = $companies_selling['lost_value'];
            $sql_company = "UPDATE company SET share_value_step =
                CASE
                    WHEN share_value_step - $lost_value >= 0 THEN share_value_step - $lost_value
                    ELSE 0
                END
            WHERE id = $company_id";
            self::DbQuery( $sql_company );
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
            $sql = "UPDATE card SET card_location=bank, owner_type=bank, card_location_arg=NULL WHERE card_id=$card_id";
            self::DbQuery( $sql );
        }

        // update player treasury
        $sql = "UPDATE player 
            SET treasury=treasury+$money_gained
            WHERE player_id='$player_id'";
        self::DbQuery( $sql );

        // TODO notify players
        // -> change in player treasury
        // -> stocks moving from player area to bank
        // -> share value drop
        self::notifyAllPlayers( "sellShares", clienttranslate( '${player_name} has sold shares' ), array(
        ) );

        self::setGameStateValue( "consecutive_passes", 0 );
        $this->gamestate->nextState( 'playerBuyPhase' );
    }

    function startCompany($company_short_name, $initial_share_value_step)
    {        
        // Check that this player is active and that this action is possible at this moment
        self::checkAction( 'startCompany' );

        $state = $this->gamestate->state();
        if($state == 'playerSkipSellBuyPhase' || $state == 'playerBuyPhase')
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
        $sql = "UPDATE player 
            SET treasury='$newTreasury'
            WHERE player_id='$player_id'";
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

    function argsOperationPhase()
    {
        $id = self::getGameStateValue( "current_company_id" );
        $short_name = self::getUniqueValueFromDB("SELECT short_name FROM company WHERE id=$id");
        return ['company_name' => self::getCompanyName($short_name)];
    }

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

    function stGameActionPhaseSetup()
    {
        // get buildings that should be played or discarded
        $sql = "SELECT card_id AS card_id, card_type AS card_type, card_location AS card_location FROM card WHERE primary_type = 'building' AND (card_location LIKE '%_play' OR card_location LIKE '%_discard')";
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
