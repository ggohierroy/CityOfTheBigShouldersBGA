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
 * cityofthebigshoulders.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in cityofthebigshoulders_cityofthebigshoulders.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_cityofthebigshoulders_cityofthebigshoulders extends game_view
  {
    function getGameName() {
        return "cityofthebigshoulders";
    }    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/

        $this->page->begin_block( "cityofthebigshoulders_cityofthebigshoulders", "share_track" );

        for($i = 0; $i < 21; $i++){
            $this->page->insert_block ( "share_track", array (
                "ZONE_ID" => $i,
                "TOP" => 33+22.1*$i,
                "Z_INDEX" => 20-$i
            ) );
        }

        $this->page->begin_block( "cityofthebigshoulders_cityofthebigshoulders", "appeal_track" );

        for($i = 0; $i < 17; $i++){
            $this->page->insert_block ( "appeal_track", array (
                "ZONE_ID" => $i,
                "TOP" => 33+27.5*$i,
                "Z_INDEX" => 16-$i
            ) );
        }

        // building track top 135px +
        $this->page->begin_block( "cityofthebigshoulders_cityofthebigshoulders", "building_track" );

        $track_colors = ["ff0000", "ffa500", "008000", "0000ff"];
        for($i = 0; $i < 4; $i++)
        {
            $track_color = $track_colors[$i];
            $player_id = 0;
            // find player id for this color
            foreach ( $players as $key => $player )
            {
                if($player['player_color'] == $track_color)
                    $player_id = $key;
            }

            if($player_id == 0)
                continue;

            $this->page->insert_block ( "building_track", array (
                "TOP" => 135 + $i*61.5,
                "PLAYER_ID" => $player_id
            ) );
        }
        
        $this->page->begin_block( "cityofthebigshoulders_cityofthebigshoulders", "player_area" );
        
        // make current player panel first
        global $g_user;
        $current_player_id = $g_user->get_id ();
        if (isset($players [$current_player_id])) { // may be not set if spectator
            $current_player = $players [$current_player_id];
            $this->page->insert_block ( "player_area", array (
                    "PLAYER_NAME" => $current_player ['player_name'],
                    "PLAYER_COLOR" => $current_player ['player_color'],
                    "PLAYER_ID" => $current_player ['player_id']
            ) );
        }

        foreach ( $players as $player_id => $player ) {
            if ($player_id != $current_player_id)
                $this->page->insert_block ( "player_area", array (
                        "PLAYER_NAME" => $player ['player_name'],
                        "PLAYER_COLOR" => $player ['player_color'],
                        "PLAYER_ID" => $player ['player_id'] 
                ) );
        }

        $this->tpl['DECK_STR'] = self::_("Deck");
        $this->tpl['DEMAND_STR'] = self::_("Demand");
        $this->tpl['BAG_STR'] = self::_("Bag");
        $this->tpl['OWNED_COMPANIES_STR'] = self::_("Owned Companies");
        $this->tpl['PERSONAL_SHARES_STR'] = self::_("Personal Shares");
        $this->tpl['SHARES_CHARTERS_STR'] = self::_("Shares on Company Charters");
        $this->tpl['BANK_POOL_STR'] = self::_("Bank Pool");

        /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */
        
        /*
        
        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 
        

        $this->page->begin_block( "cityofthebigshoulders_cityofthebigshoulders", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }
        
        */



        /*********** Do not change anything below this line  ************/
  	}
  }
  

