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
 * states.inc.php
 *
 * CityOfTheBigShoulders game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 2 )
    ),
    
    // Note: ID=2 => your first state

    2 => array(
    		"name" => "playerStartFirstCompany",
    		"description" => clienttranslate('${actplayer} must choose a company to start'),
    		"descriptionmyturn" => clienttranslate('${you} must choose a company to start'),
    		"type" => "activeplayer",
    		"possibleactions" => array( "startCompany" ),
    		"transitions" => array( "gameStartFirstCompany" => 3 )
    ),

    3 => array(
        "name" => "gameStartFirstCompany",
        "description" => "",
        "type" => "game",
        "action" => "gameStartFirstCompany",
        "transitions" => array( "nextPlayer" => 2, "playerSellPhase" => 4 )
    ),

    4 => array(
        "name" => "playerSellPhase",
        "description" => clienttranslate('${actplayer} may sell any number of certificates to the bank'),
        "descriptionmyturn" => clienttranslate('${you} may sell any number of certificates to the bank'),
        "type" => "activeplayer",
        "possibleactions" => array( "sellShares", "skipSell", "passStockAction" ),
        "transitions" => array( "gameStockPhase" => 5, "playerBuyPhase" => 8, "playerSkipSellBuyPhase" => 9 )
    ),

    9 => array(
        "name" => "playerSkipSellBuyPhase",
        "description" => clienttranslate('${actplayer} may buy an available certificate or start a company'),
        "descriptionmyturn" => clienttranslate('${you} may buy an available certificate or start a company'),
        "type" => "activeplayer",
        "args" => "argPlayerBuyPhase",
        "possibleactions" => array( "buyCertificate", "startCompany", "passStockAction" ),
        "transitions" => array( "gameStockPhase" => 5 )
    ),

    8 => array(
        "name" => "playerBuyPhase",
        "description" => clienttranslate('${actplayer} may buy an available certificate or start a company'),
        "descriptionmyturn" => clienttranslate('${you} may buy an available certificate or start a company'),
        "type" => "activeplayer",
        "args" => "argPlayerBuyPhase",
        "possibleactions" => array( "buyCertificate", "startCompany", "skipBuy" ),
        "transitions" => array( "gameStockPhase" => 5 )
    ),

    5 => array(
        "name" => "gameStockPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameStockPhase",
        "transitions" => array( "playerStockPhase" => 4, "playerBuildingPhase" => 7, "playerPriceProtection" => 6 )
    ),

    6 => array(
        "name" => "playerPriceProtection",
        "description" => clienttranslate('${actplayer} must choose to use the company\'s Price Protection asset tile or skip'),
        "descriptionmyturn" => clienttranslate('${you} must choose to use the company\'s Price Protection asset tile or skip'),
        "type" => "activeplayer",
        "possibleactions" => array( "st", "stBuyStock", "stStartCompany", "stPass" ),
        "transitions" => array( "nextPlayer" => 5 )
    ),

    7 => array(
        "name" => "playerBuildingPhase",
        "description" => clienttranslate('${actplayer} must choose a building to play'),
        "descriptionmyturn" => clienttranslate('${you} must choose a building to play'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array( "selectBuildings" ),
        "action" => "st_MultiPlayerInit",
        "transitions" => array( "gameActionPhaseSetup" => 10 )
    ),

    10 => array(
        "name" => "gameActionPhaseSetup",
        "description" => "",
        "type" => "game",
        "action" => "stGameActionPhaseSetup",
        "transitions" => array( "playerActionPhase" => 11 )
    ),

    11 => array(
        "name" => "playerActionPhase",
        "description" => clienttranslate('${actplayer} must choose an action'),
        "descriptionmyturn" => clienttranslate('${you} must choose an action'),
        "type" => "activeplayer",
        "args" => "argsPlayerActionPhase",
        "possibleactions" => array( "buildingAction", "appealBonus", "tradeResources", "useAsset" ),
        "transitions" => array( "freeActions" => 23, "workerBonus" => 21, "automationBonus" => 22, "loopback" => 11)
    ),

    23 => array(
        "name" => "playerFreeActionPhase",
        "description" => clienttranslate('${actplayer} may trade with Haymarket Square or use Capital Assets'),
        "descriptionmyturn" => clienttranslate('${you} may trade with Haymarket Square or use Capital Assets'),
        "type" => "activeplayer",
        "possibleactions" => array( "tradeResources", "useAsset", "passFreeActions" ),
        "transitions" => array( "pass" => 12, "loopback" => 23)
    ),

    12 => array(
        "name" => "gameActionPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameActionPhase",
        "transitions" => array( "playerActionPhase" => 11, "playerBuyResourcesPhase" => 13)
    ),

    21 => array(
        "name" => "playerAssetWorkerBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may choose a factory in which to hire a worker for free'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may choose a factory in which to hire a worker for free'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus",
        "possibleactions" => array( "hireWorker", "skipAssetBonus" ),
        "transitions" => array( "freeActions" => 23 )
    ),

    22 => array(
        "name" => "playerAssetAutomationBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may automate a factory'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may automate a factory'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus",
        "possibleactions" => array( "automateFactory", "skipAssetBonus" ),
        "transitions" => array( "freeActions" => 23 )
    ),

    13 => array(
        "name" => "playerBuyResourcesPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may purchase resources from the supply chain'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may purchase resources from the supply chain'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "buyResources", "tradeResources", "useAsset", "skipBuyResources" ),
        "transitions" => array( "playerProduceGoodsPhase" => 14 )
    ),

    14 => array(
        "name" => "playerProduceGoodsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may produce goods in its factories'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may produce goods in its factories'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "produceGoods", "tradeResources", "useAsset", "skipProduceGoods" ),
        "transitions" => array( "distributeGoods" => 16, "nextFactory" => 14, "managerBonus" => 16 )
    ),

    15 => array(
        "name" => "managerBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain a resources from Haymarket Square (Manager bonus)'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain a resources from Haymarket Square (Manager bonus)'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "managerBonusGainResources" ),
        "transitions" => array( "distributeGoods" => 15, "nextFactory" => 14 )
    ),

    16 => array(
        "name" => "playerDistributeGoodsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may distribute goods'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may distribute goods'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "distributeGoods", "skipDistributeGoods" ),
        "transitions" => array( "dividends" => 17, "gameOperationPhase" => 19 )
    ),

    17 => array(
        "name" => "playerDividendsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may pay dividends to shareholders with its earnings ($${income})'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may pay dividends to shareholders with its earnings ($${income})'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "payDividends", "withhold" ),
        "transitions" => array( "gameOperationPhase" => 19 )
    ),

    19 => array(
        "name" => "gameOperationPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameOperationPhase",
        "transitions" => array( "nextCompany" => 13, "cleanup" => 20, "gameEnd" => 99)
    ),

    20 => array(
        "name" => "gameCleanupPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameCleanup",
        "transitions" => array("nextRound" => 4)
    ),
   
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);



