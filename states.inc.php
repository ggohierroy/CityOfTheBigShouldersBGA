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
    		"transitions" => array( "gameTurn" => 3, "zombiepass" => 3 )
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
        "transitions" => array( "gameStockPhase" => 5, "playerBuyPhase" => 8, "playerSkipSellBuyPhase" => 9, "zombiepass" => 5 )
    ),

    9 => array(
        "name" => "playerSkipSellBuyPhase",
        "description" => clienttranslate('${actplayer} may buy an available certificate or start a company'),
        "descriptionmyturn" => clienttranslate('${you} may buy an available certificate or start a company'),
        "type" => "activeplayer",
        "args" => "argPlayerBuyPhase",
        "possibleactions" => array( "buyCertificate", "startCompany", "passStockAction", "undo" ),
        "transitions" => array( "gameStockPhase" => 5, "gameTurn" => 5, "playerconfirmDirectorship" => 37 )
    ),

    8 => array(
        "name" => "playerBuyPhase",
        "description" => clienttranslate('${actplayer} may buy an available certificate or start a company'),
        "descriptionmyturn" => clienttranslate('${you} may buy an available certificate or start a company'),
        "type" => "activeplayer",
        "args" => "argPlayerBuyPhase",
        "possibleactions" => array( "buyCertificate", "startCompany", "skipBuy", "undo" ),
        "transitions" => array( "gameStockPhase" => 5, "gameTurn" => 5, "playerconfirmDirectorship" => 37 )
    ),

    37 => array(
        "name" => "playerconfirmDirectorship",
        "description" => clienttranslate('${actplayer} must confirm directorship change'),
        "descriptionmyturn" => clienttranslate('${you} must confirm directorship change'),
        "type" => "activeplayer",
        "possibleactions" => array( "confirmDirectorship", "undo" ),
        "transitions" => array( "gameStockPhase" => 5 )
    ),

    5 => array(
        "name" => "gameStockPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameStockPhase",
        "transitions" => array( "playerStockPhase" => 4, "playerBuildingPhase" => 7 )
    ),

    7 => array(
        "name" => "playerBuildingPhase",
        "description" => clienttranslate('Waiting on other players to choose a building to play'),
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
        "possibleactions" => array( "buildingAction", "tradeResources", "useAsset", "undo" ),
        "transitions" => array( "freeActions" => 23, "workerBonus" => 21, "automationBonus" => 22, "appealBonus" => 24, "freeAppealBonus" => 25, "loopback" => 11, "zombiepass" => 12)
    ),

    23 => array(
        "name" => "playerFreeActionPhase",
        "description" => clienttranslate('${actplayer} may trade with Haymarket Square or use Capital Assets'),
        "descriptionmyturn" => clienttranslate('${you} may trade with Haymarket Square or use Capital Assets'),
        "type" => "activeplayer",
        "possibleactions" => array( "tradeResources", "useAsset", "passFreeActions", "undo" ),
        "transitions" => array( "pass" => 12, "freeAppealBonus" => 31, "loopback" => 23)
    ),

    12 => array(
        "name" => "gameActionPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameActionPhase",
        "transitions" => array( "playerActionPhase" => 11, "playerBuyResourcesPhase" => 13, "playerEmergencyFundraise" => 34)
    ),

    // this happens when immediate bonus is gained when purchasing asset tile
    21 => array(
        "name" => "playerAssetWorkerBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may choose a factory in which to hire a worker for free'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may choose a factory in which to hire a worker for free'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus",
        "possibleactions" => array( "hireWorker", "skipAssetBonus", "undo" ),
        "transitions" => array( "freeActions" => 23 )
    ),

    // this happens when immediate bonus is gained when purchasing asset tile
    22 => array(
        "name" => "playerAssetAutomationBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may automate a factory'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may automate a factory'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus",
        "possibleactions" => array( "automateFactory", "skipAssetBonus", "undo" ),
        "transitions" => array( "freeActions" => 23, "appealBonus" => 24 /* this only happens with price protection*/)
    ),

    // this happens when immediate bonus is gained when purchasing asset tile
    // or when action space is used which gives appeal
    24 => array(
        "name" => "playerAssetAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus", "undo" ),
        "transitions" => array( "loopback" => 24, "next" => 23 )
    ),

    34 => array(
        "name" => "playerEmergencyFundraise",
        "description" => clienttranslate('${company_name} (${actplayer}) may perform Emergency Fundraising by selling shares to the bank'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may perform Emergency Fundraising by selling shares to the bank'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "emergencyFundraise", "passEmergencyFundraise"  ),
        "transitions" => array( "playerBuyResourcesPhase" => 13)
    ),

    13 => array(
        "name" => "playerBuyResourcesPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may purchase resources from the supply chain'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may purchase resources from the supply chain'),
        "type" => "activeplayer",
        "updateGameProgression" => true,
        "args" => "argsOperationPhase",
        "possibleactions" => array( "buyResources", "tradeResources", "useAsset", "skipBuyResources", "undo"  ),
        "transitions" => array( "playerProduceGoodsPhase" => 14, "freeAppealBonus" => 26, "loopback" => 13, "zombiepass" => 19, "playerDistributeGoodsPhase" => 16)
    ),

    14 => array(
        "name" => "playerProduceGoodsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may produce goods in its factories'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may produce goods in its factories'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "produceGoods", "tradeResources", "useAsset", "skipProduceGoods", "undo" ),
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
        "possibleactions" => array( "distributeGoods", "skipDistributeGoods", "useAsset", "undo" ),
        "transitions" => array( "dividends" => 17, "gameOperationPhase" => 19, "freeAppealBonus" => 28, "loopback" => 16, "useAssets" => 35)
    ),

    17 => array(
        "name" => "playerDividendsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may pay dividends to shareholders with its earnings ($${income})'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may pay dividends to shareholders with its earnings ($${income})'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "payDividends", "withhold", "useAsset", "undo" ),
        "transitions" => array( "gameOperationPhase" => 19, "freeAppealBonus" => 29, "loopback" => 17, "useAssets" => 35)
    ),

    35 => array(
        "name" => "playerUseAssetsPhase",
        "description" => clienttranslate('${company_name} (${actplayer}) may use unexhausted assets'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may use unexhausted assets'),
        "type" => "activeplayer",
        "args" => "argsOperationPhase",
        "possibleactions" => array( "useAsset", "finish", "undo" ),
        "transitions" => array( "gameOperationPhase" => 19, "freeAppealBonus" => 36, "loopback" => 35)
    ),

    19 => array(
        "name" => "gameOperationPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameOperationPhase",
        "transitions" => array( "nextCompany" => 13, "nextEmergency" => 34, "cleanup" => 20, "gameEnd" => 99)
    ),

    20 => array(
        "name" => "gameCleanupPhase",
        "description" => "",
        "type" => "game",
        "action" => "stGameCleanup",
        "transitions" => array("nextRound" => 4)
    ),

    // this happens when an asset tile is used during the action phase
    25 => array(
        "name" => "playerActionAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus", "undo" ),
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

    // this happens when an asset tile is used during the pay dividends step of the operation phase
    29 => array(
        "name" => "playerDividendsAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 29, "next" => 17 )
    ),

    // this happens when an asset tile is used during the use asset step of the operation phase (final opportunity to use asset)
    36 => array(
        "name" => "playerUseAssetsAppealBonus",
        "description" => clienttranslate('${company_name} (${actplayer}) may gain or forfeit appeal bonus'),
        "descriptionmyturn" => clienttranslate('${company_name} (${you}) may gain or forfeit appeal bonus'),
        "type" => "activeplayer",
        "args" => "argsPlayerAssetBonus", // <- bad naming, only has company id and short name
        "possibleactions" => array( "gainAppealBonus", "forfeitAppealBonus" ),
        "transitions" => array( "loopback" => 36, "next" => 35 )
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