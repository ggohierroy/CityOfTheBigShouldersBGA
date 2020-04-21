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
  

