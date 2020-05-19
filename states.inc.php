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
    		"transitions" => array( "gameTurn" => 3 )
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
        "updateGameProgression" => true,
        "possibleactions" => array( "sellShares", "skipSell", "passStockAction" ),
        "transitions" => array( "gameStockPhase" => 5, "playerBuyPhase" => 8, "playerSkipSellBuyPhase" => 9, "interruptPriceProtection" => 33 )
    ),

    9 => array(
        "name" => "playerSkipSellBuyPhase",
        "description" => clienttranslate('${actplayer} may buy an available certificate or start a company'),
        "descriptionmyturn" => clienttranslate('${you} may buy an available certificate or start a company'),
        "type" => "activeplayer",
        "args" => "argPlayerBuyPhase",
        "possibleactions" => array( "buyCertificate", "startCompany", "passStockAction" ),
        "transitions" => array( "gameStockPhase" => 5, "gameTurn" => 5 )
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
        "transitions" => array( "playerStockPhase" => 4, "playerBuildingPhase" => 7 )
    ),

    33 => array(
        "name" => "gameInterruptPriceProtection",
        "description" => "",
        "type" => "game",
        "action" => "stGameInterruptPriceProtection",
        "transitions" => array( "playerPriceProtection" => 6, "buyPhase" => 8 )
    ),

    6 => array(
        "name" => "playerPriceProtection",
        "description" => clienttranslate('${company_name}\'s share price is about to drop ${lost_value_step}. ${actplayer} may choose to use Price Protection to prevent it'),
        "descriptionmyturn" => clienttranslate('${company_name}\'s share price is about to drop ${lost_value_step}. ${you} may choose to use Price Protection to prevent it'),
        "type" => "activeplayer",
        "args" => "argPriceProtection",
        "possibleactions" => array( "priceProtect", "passPriceProtect" ),
        "transitions" => array( "interruptReturn" => 33 )
    ),

    7 => array(
        "name" => "playerBuildingPhase",
        "description" => clienttranslate('${actplayer} must choose a building to play'),
        "descriptionmyturn" => clienttranslate('${you} must choose a building to play'),
        "type" => "multipleactiveplayer",
        "updateGameProgression" => true,
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
        "updateGameProgression" => true,
        "args" => "argsPlayerActionPhase",
        "possibleactions" => array( "buildingAction", "tradeResources", "useAsset" ),
        "transitions" => array( "freeActions" => 23, "workerBonus" => 21, "automationBonus" => 22, "appealBonus" => 24, "freeAppealBonus" => 25, "loopback" => 11)
    ),

    23 => array(
        "name" => "playerFreeActionPhase",
        "description" => clienttranslate('${actplayer} may trade with Haymarket Square or use Capital Assets'),
        "descriptionmyturn" => clienttranslate('${you} may trade with Haymarket Square or use Capital Assets'),
        "type" => "activeplayer",
        "possibleactions" => array( "tradeResources", "useAsset", "passFreeActions" ),
        "transitions" => array( "pass" => 12, "freeAppealBonus" => 31, "loopback" => 23)
    ),

    12 => array(
        "name" => "gameActionPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameActionPhase",
        "transitions" => array( "playerActionPhase" => 11, "playerBuyResourcesPhase" => 13)
    ),

    // this happens when immediate bonus is gained when purchasing asset tile
    21 => array(
        "name" => "playerAssetWorkerBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may choose a factory in which to hire a worker for free'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may choose a factory in which to hire a worker for free'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus",
        "possibleactions" => array( "hireWorker", "skipAssetBonus" ),
        "transitions" => array( "freeActions" => 23 )
    ),

    // this happens when immediate bonus is gained when purchasing asset tile
    22 => array(
        "name" => "playerAssetAutomationBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may automate a factory'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may automate a factory'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus",
        "possibleactions" => array( "automateFactory", "skipAssetBonus" ),
        "transitions" => array( "freeActions" => 23 )
    ),

    // this happens when immediate bonus is gained when purchasing asset tile
    // or when action space is used which gives appeal
    24 => array(
        "name" => "playerAssetAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 24, "next" => 23 )
    ),

    13 => array(
        "name" => "playerBuyResourcesPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may purchase resources from the supply chain'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may purchase resources from the supply chain'),
        "type" => "activeplayer",
        "updateGameProgression" => true,
        "args" => "argsOperationPhase",
        "possibleactions" => array( "buyResources", "tradeResources", "useAsset", "skipBuyResources" ),
        "transitions" => array( "playerProduceGoodsPhase" => 14, "freeAppealBonus" => 26, "loopback" => 13)
    ),

    14 => array(
        "name" => "playerProduceGoodsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may produce goods in its factories'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may produce goods in its factories'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "produceGoods", "tradeResources", "useAsset", "skipProduceGoods" ),
        "transitions" => array( "distributeGoods" => 16, "nextFactory" => 14, "managerBonusResources" => 15, "managerBonusAppeal" => 30, "freeAppealBonus" => 27, "loopback" => 14)
    ),

    15 => array(
        "name" => "managerBonusResources",
        "description" => clienttranslate('${company_name} (${actplayer}) may choose ${resources_gained} resources to gain from Haymarket Square'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may choose ${resources_gained} resources to gain from Haymarket Square'),
        "type" => "activeplayer",
        "args" => "argsManagerBonusResources",
        "possibleactions" => array( "managerBonusGainResources" ),
        "transitions" => array( "distributeGoods" => 16, "nextFactory" => 14, "managerBonusAppeal" => 30 )
    ),

    // this happens when a manager bonus gives appeal
    30 => array(
        "name" => "managerBonusAppeal",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 30, "nextFactory" => 14, "distributeGoods" => 16 )
    ),

    16 => array(
        "name" => "playerDistributeGoodsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may distribute goods'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may distribute goods'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "distributeGoods", "skipDistributeGoods", "useAsset" ),
        "transitions" => array( "dividends" => 17, "gameOperationPhase" => 19, "freeAppealBonus" => 28, "loopback" => 16)
    ),

    17 => array(
        "name" => "playerDividendsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may pay dividends to shareholders with its earnings ($${income})'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may pay dividends to shareholders with its earnings ($${income})'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "payDividends", "withhold", "useAsset", "withholdProtection" ),
        "transitions" => array( "gameOperationPhase" => 19, "freeAppealBonus" => 29, "loopback" => 17)
    ),

    19 => array(
        "name" => "gameOperationPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameOperationPhase",
        "transitions" => array( "nextCompany" => 13, "cleanup" => 20, "publicGoalScoring" => 32)
    ),

    20 => array(
        "name" => "gameCleanupPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameCleanup",
        "transitions" => array("nextRound" => 4)
    ),

    32 => array(
        "name" => "publicGoalScoring",
        "description" => "",
        "type" => "game",
        "action" => "stGamePublicGoalScoring",
        "transitions" => array( "gameEnd" => 99)
    ),

    // this happens when an asset tile is used during the action phase
    25 => array(
        "name" => "playerActionAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 25, "next" => 11 )
    ),

    // this happens when an asset tile is used during the buy resource step of the operation phase
    26 => array(
        "name" => "playerBuyResourceAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 26, "next" => 13 )
    ),

    // this happens when an asset tile is used during the produce goods step of the operation phase
    27 => array(
        "name" => "playerProduceGoodsAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 27, "next" => 14 )
    ),

    // this happens when an asset tile is used during the distribute goods step of the operation phase
    28 => array(
        "name" => "playerDistributeAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 28, "next" => 16 )
    ),

    // this happens when an asset tile is used during the pay dividens step of the operation phase
    29 => array(
        "name" => "playerDividendsAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 29, "next" => 17 )
    ),

    // this happens when an asset tile is used during the free action phase
    31 => array(
        "name" => "playerFreeActionAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 31, "next" => 23 )
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



