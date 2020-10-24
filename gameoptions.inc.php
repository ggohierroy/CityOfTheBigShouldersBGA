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
 * gameoptions.inc.php
 *
 * CityOfTheBigShoulders game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in cityofthebigshoulders.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    /*
    
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => array(
                'name' => totranslate('my game option'),    
                'values' => array(

                            // A simple value for this option:
                            1 => array( 'name' => totranslate('option 1') )

                            // A simple value for this option.
                            // If this value is chosen, the value of "tmdisplay" is displayed in the game lobby
                            2 => array( 'name' => totranslate('option 2'), 'tmdisplay' => totranslate('option 2') ),

                            // Another value, with other options:
                            //  description => this text will be displayed underneath the option when this value is selected to explain what it does
                            //  beta=true => this option is in beta version right now.
                            //  nobeginner=true  =>  this option is not recommended for beginners
                            3 => array( 'name' => totranslate('option 3'), 'description' => totranslate('this option does X'), 'beta' => true, 'nobeginner' => true )
                        )
            )

    */

    100 => array(
        'name' => totranslate('Public Goals'),    
        'values' => array(

                    // A simple value for this option:
                    1 => array( 'name' => totranslate('Yes'), 'tmdisplay' => totranslate('Public Goals') ),
                    2 => array( 'name' => totranslate('No'), 'nobeginner' => true )
                )
    ),
    101 => array(
        'name' => totranslate('Advanced Rules'),    
        'values' => array(

                    // A simple value for this option:
                    1 => array( 'name' => totranslate('No') ),
                    2 => array( 'name' => totranslate('Yes'), 'tmdisplay' => totranslate('Advanced Rules'), 'nobeginner' => true )
                )
    ),
    102 => array(
        'name' => totranslate('Friendly Stock Sales'),    
        'values' => array(

                    // A simple value for this option:
                    1 => array( 'name' => totranslate('Yes'), 'tmdisplay' => totranslate('Friendly Stock Sales'), 'description' => totranslate('Stock price only drops one step regardless of number of shares sold by non president') ),
                    2 => array( 'name' => totranslate('No'), 'beta' => true, 'nobeginner' => true )
                )
    ),
    103 => array(
        'name' => totranslate('Supply Chain Variant'),
        'values' => array(
                    1 => array( 'name' => totranslate('No') ),
                    2 => array( 'name' => totranslate('Yes'), 'beta' => true, 'tmdisplay' => totranslate('Supply Chain Variant'), 'description' => totranslate('Supply Chain variant of the game, however it is a much more stable resources market') )
                )
    ),
);


