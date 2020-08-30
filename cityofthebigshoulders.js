/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CityOfTheBigShoulders implementation : © Gabriel Gohier-Roy <ggohierroy@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * cityofthebigshoulders.js
 *
 * CityOfTheBigShoulders user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    "ebg/zone"
],
function (dojo, declare) {
    return declare("bgagame.cityofthebigshoulders", ebg.core.gamegui, {
        constructor: function(){
            console.log('cityofthebigshoulders constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;

            this.companyNameToImagePosition = { 'anglo':0, 'brunswick':1,'cracker': 2, 'doggett': 3, 'elgin': 4, 'fairbank': 5, 'henderson': 6, 'libby': 7, 'spalding': 8, 'swift': 9 };
            this.buildingNumberToImagePosition = {
                'building1':0, 'building2':1, 'building3':2, 'building4':5, 'building5':6,
                'building6':7, 'building7':10, 'building8':11, 'building9':12, 'building10':15,
                'building11':16, 'building12':17, 'building13':3, 'building14':4, 'building15':8,
                'building16':9, 'building17':13, 'building18':14, 'building19':18, 'building20':19,
                'building21':20, 'building22':21, 'building23':25, 'building24':26, 'building25':30,
                'building26':31, 'building27':35, 'building28':36, 'building29':22, 'building30':34,
                'building31':37, 'building32':38, 'building33':23, 'building34':24, 'building35':27,
                'building36':28, 'building37':29, 'building38':32, 'building39':33, 'building40':39,
                'building41':40, 'building42':41, 'building43':42, 'building44':43  };
            this.assetNameToImagePosition = {
                'color_catalog': 45, 'brand_recognition': 46, 'catalogue_empire': 47, 'mail_order_catalogue': 48, 'popular_partners': 49,
                'price_protection': 50, 'backroom_deals': 51, 'union_stockyards': 52, 'refrigeration': 53, 'foundry': 54, 
                'workshop': 55, 'michigan_lumber': 56, 'abattoir': 57, 'pennsylvania_coal': 58, 'cincinnati_steel': 59,
                'brilliant_marketing': 60
            };
            this.goalPositions = {
                'goal1': 65, 
                'goal2': 67, 
                'goal3': 69, 
                'goal4': 71,  
                'goal5': 73,  
                'goal6': 66,  
                'goal7': 68,  
                'goal8': 70,  
                'goal9': 72,  
                'goal10': 74
            };
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );

            this.createPlayerOrderStock();
            var orderWeight = {};
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                
                // create player company stocks
                this.createCompaniesStock(gamedatas.all_companies, player_id);

                // create personal share stocks
                this.createShareStock(gamedatas.all_companies, 'personal_area_'+player_id);
                if(player_id == this.player_id )
                    dojo.connect( this['personal_area_'+player_id], 'onChangeSelection', this, 'onPersonalShareSelected' );
                
                // Setting up players boards if needed
                
                var player_board_div = $('player_board_'+player_id);
                dojo.place( this.format_block('jstpl_player_board', {
                    'id': player.id,
                    'color': player.color
                } ), player_board_div );

                if(player.player_order && gamedatas.gamestate.name != 'playerStartFirstCompany'){
                    var hash = this.hashString(player.color);
                    this.player_order.addToStockWithId(hash, player.name);
                    var itemDiv = this.player_order.getItemDivId(player.name);
                    dojo.style(itemDiv, "z-index", player.player_order);
                    orderWeight[hash] = Number(player.player_order);
                }

                // create building tracks
                this['building_track_'+player_id] = this.createBuildingsStock(gamedatas.all_buildings, 'building_track_'+player_id, 'setupNewTrackBuilding');
                this['building_track_'+player_id].item_margin=4;
            }

            // adjust player order
            this.player_order.changeItemsWeight( orderWeight ); // { 1: 10, 2: 20, itemType: Weight }

            if(!this.isSpectator)
                this.player_color = gamedatas.players[this.player_id].color;
            this.clientStateArgs = {};
            this.clientStateArgs.actionArgs = {};
            this.clientStateArgs.goods = [];

            // create zones and stocks
            this.createShareValueZones();
            this.createAppealZones();
            this.createJobMarketZone();
            this.createWorkerZones(gamedatas.general_action_spaces);
            this.createAssetTileStock('capital_assets', gamedatas.all_capital_assets);
            dojo.connect( this.capital_assets, 'onChangeSelection', this, 'onAssetSelected' );
            this.createResourceStock('haymarket');
            dojo.connect($('haymarket_square'), 'onclick', this, 'onHaymarketSquareClicked');
            this.haymarket.onItemCreate = dojo.hitch( this, 'onHaymarketResourceCreated' ); 
            this.createResourceStock('supply_x');
            this.createResourceStock('supply_30');
            this.createResourceStock('supply_20');
            this.createResourceStock('supply_10');
            this.createGoalStock(gamedatas.goals);

            // create available shares stock
            this.createShareStock(gamedatas.all_companies, 'available_shares_company');
            dojo.connect( this.available_shares_company, 'onChangeSelection', this, 'onAvailableShareSelected' );

            this.createShareStock(gamedatas.all_companies, 'available_shares_bank');
            dojo.connect( this.available_shares_bank, 'onChangeSelection', this, 'onAvailableShareSelected' );

            // create available companies stock
            this.createCompaniesStock(gamedatas.all_companies);
            dojo.connect( this.available_companies, 'onChangeSelection', this, 'onCompanySelected' );

            // create buildings personal stock
            if(!this.isSpectator)
                this.buildings = this.createBuildingsStock(gamedatas.all_buildings, 'building_area_'+this.player_id, 'setupNewBuilding');
            
            // place owned in the game
            for(var i in gamedatas.owned_companies){
                var ownedCompany = gamedatas.owned_companies[i];
                var availableCompany = gamedatas.all_companies[ownedCompany.short_name];
                availableCompany.inPlay = true;

                this.placeCompany(ownedCompany);
            }

            this.placeAppealTokens(gamedatas.company_order);

            // place available companies
            for(var property in gamedatas.all_companies){
                var company = gamedatas.all_companies[property];
                if(company.inPlay)
                    continue;

                this.placeAvailableCompany(company.short_name);
            }

            // create demand space zones (for when demand tile deck is empty)
            this.createDemandSpaceZones();

            // add items to board
            this.placeItemsOnBoard(gamedatas);

            // update counters
            this.updateCounters(gamedatas.counters);

            // connect all worker spots
            dojo.query(".worker_spot.general-action").connect( 'onclick', this, 'onWorkerSpotClicked' );
            dojo.connect($('job_market_worker'), 'onclick', this, 'onWorkerSpotClicked');
 
            // connect appeal bonus spots
            dojo.query(".appeal-bonus").connect( 'onclick', this, 'onAppealBonusClicked' );

            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            this.creatAppealBonusTooltips();

            this.placeRoundAndPhaseMarkers(gamedatas);

            // set priority marker
            var priority_deal_player_id = gamedatas.priority_deal_player_id;
            if(priority_deal_player_id != "0")
                dojo.query("#priority_" + priority_deal_player_id).addClass("priority-marker");

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
                case 'playerEmergencyFundraise':
                    this.slideVertically('available_shares_wrapper', 'shares_top');
                    if(this.isCurrentPlayerActive()) {
                        this.available_shares_company.setSelectionMode(2);
                        this.activateCompanyShares(args.args.company_short_name);
                    }
                    break;
                case 'playerStartFirstCompany':
                    this.slideVertically('available_companies_wrapper', 'companies_top');
                    this.slideVertically('main_board_wrapper', 'board_bottom');
                    if(this.isCurrentPlayerActive())
                    {
                        this.available_companies.setSelectionMode(1);
                        dojo.query('#available_companies>.company').addClass('active');
                    } else {
                        this.available_companies.setSelectionMode(0);
                    }
                    break;
                case 'client_playerTurnSelectStartingShareValue':
                    this.available_companies.setSelectionMode(1);
                    break;
                case 'gameStartFirstCompany':
                    this.available_companies.setSelectionMode(0);
                    break;
                case 'playerSellPhase':
                    this.slideVertically('available_shares_wrapper', 'shares_top');
                    this.slideVertically('available_companies_wrapper', 'companies_bottom');
                    this.slideVertically('main_board_wrapper', 'board_bottom');
                    if(this.isCurrentPlayerActive()) {
                        var playerId = this.player_id;
                        this['personal_area_'+playerId].setSelectionMode(2);
                        dojo.query('#personal_area_'+playerId+'>.stockitem').addClass('active');
                    }
                    break;
                case 'playerSkipSellBuyPhase':
                    this.slideVertically('available_shares_wrapper', 'shares_top');
                    this.slideVertically('main_board_wrapper', 'board_bottom');
                    if(args.args.round > 0){
                        var playerId = this.getActivePlayerId();
                        var money = this.gamedatas.counters['money_' + playerId].counter_value;
                        this.clientStateArgs.playerMoney = money;
                        if(money >= 105){
                            this.slideVertically('available_companies_wrapper', 'companies_top');

                            if(this.isCurrentPlayerActive()) {
                                this.available_companies.setSelectionMode(1);
                                dojo.query('#available_companies>.company').addClass('active');
                            }
                        }
                        this.deactivateTooExpensiveStocks(money);
                    }
                    if(this.isCurrentPlayerActive()) {
                        this.available_shares_company.setSelectionMode(1);
                        this.available_shares_bank.setSelectionMode(1);
                        dojo.query('#available_shares_company>.stockitem').addClass('active');
                        dojo.query('#available_shares_bank>.stockitem').addClass('active');
                    }
                    break;
                case 'playerBuyPhase':
                    this.slideVertically('available_shares_wrapper', 'shares_top');
                    this.slideVertically('main_board_wrapper', 'board_bottom');
                    if(args.args.round > 0){
                        var playerId = this.getActivePlayerId();
                        var money = this.gamedatas.counters['money_' + playerId].counter_value;
                        this.clientStateArgs.playerMoney = money;
                        if(money >= 105){
                            this.slideVertically('available_companies_wrapper', 'companies_top');

                            if(this.isCurrentPlayerActive()) {
                                this.available_companies.setSelectionMode(1);
                                dojo.query('#available_companies>.company').addClass('active');
                            }
                        }

                        this.deactivateTooExpensiveStocks(money);
                    }
                    if(this.isCurrentPlayerActive()) {
                        this.available_shares_company.setSelectionMode(1);
                        this.available_shares_bank.setSelectionMode(1);
                        dojo.query('#available_shares_company>.stockitem').addClass('active');
                        dojo.query('#available_shares_bank>.stockitem').addClass('active');
                    }
                    break;
                case 'client_playerTurnBuyCertificate':
                    this.deactivateTooExpensiveStocks(this.clientStateArgs.playerMoney);
                    break;
                case 'playerBuildingPhase':
                    this.slideVertically('main_board_wrapper', 'board_top');
                    this.slideVertically('available_shares_wrapper', 'shares_bottom');
                    this.slideVertically('available_companies_wrapper', 'companies_bottom');
                    if(!this.isSpectator && this.isCurrentPlayerActive())
                        dojo.query('#building_area_'+this.player_id+'>.stockitem').addClass('active');
                    break;
                case 'clientPlayerDiscardBuilding':
                    if(!this.isSpectator)
                        dojo.query('#building_area_'+this.player_id+'>.stockitem').forEach(function(item){
                    if(!dojo.hasClass(item, 'building_to_play'))
                        dojo.addClass(item, 'active');
                    })
                    break;
                case 'playerActionPhase': // main player action
                    // reset action args
                    this.clientStateArgs = {};
                    this.clientStateArgs.actionArgs = {};
                    
                    if(this.isCurrentPlayerActive()){
                        this.activatePlayerAssets();
                        dojo.query('.worker_spot').addClass('active');
                        dojo.query('#haymarket_square').addClass('active');
                    }
                    
                    // disable fundraise actions that only become available in later rounds
                    if(args.args.round < 4){
                        dojo.removeClass('fundraising_80', 'active');
                    }
                    if(args.args.round < 2){
                        dojo.removeClass('fundraising_60', 'active');
                    }
                    break;
                case 'client_actionChooseFactory':
                case 'client_actionChooseFactorySkip':
                case 'client_actionChooseFactorySkipWorker':
                    var companyShortName = this.clientStateArgs.companyShortName;
                    if(companyShortName){
                        // when this value exists, the rest of the action should be in the same company
                        this.activateFactoriesInCompany(companyShortName);
                    } else {
                        // activate all factories in player area
                        dojo.query('#player_'+this.player_id+' .factory').addClass('active');
                    }
                    break;
                case 'client_actionChooseCompany':
                case 'client_tradeChooseCompany':
                case 'client_chooseCompanyToPay':
                    // activate all companies in player area
                    dojo.query('#player_'+this.player_id+' .company').addClass('active');
                    break;
                case 'client_confirmGainLessResources':
                    dojo.query('#company_'+this.clientStateArgs.companyShortName).addClass('active');
                    break;
                case 'client_chooseFactoryRelocate':
                case 'client_placeHiredWorkers':
                case 'client_chooseFactoryRelocateBonus':
                case 'client_chooseFactoryRelocateDoubleAutomation':
                case 'client_actionChooseFactorySkipToRelocate':
                    // activate all factories in the current company
                    this.activateFactoriesInCompany(this.clientStateArgs.companyShortName);
                    break;
                case 'playerBuyResourcesPhase':
                    this.slideVertically('available_shares_wrapper', 'shares_bottom');
                    if(this.isCurrentPlayerActive()){
                        this.activateCompanyAsset(args.args.company_short_name);
                        dojo.query('#haymarket_square').addClass('active');
                        this.supply_10.setSelectionMode(2);
                        this.supply_20.setSelectionMode(2);
                        this.supply_30.setSelectionMode(2);
                        dojo.query('#supply_10>.stockitem').addClass('active');
                        dojo.query('#supply_20>.stockitem').addClass('active');
                        dojo.query('#supply_30>.stockitem').addClass('active');
                    }
                    break;
                case 'playerProduceGoodsPhase':
                    if(this.isCurrentPlayerActive()){
                        this.activateCompanyAsset(args.args.company_short_name);
                        dojo.query('#haymarket_square').addClass('active');
                        var currentFactory = Number(args.args.last_factory_produced) + 1;
                        var companyShortName = args.args.company_short_name;
                        dojo.query('#'+companyShortName+'_factory_'+currentFactory).addClass('active');
                    }
                    break;
                case 'playerDistributeGoodsPhase':
                    this.clientStateArgs.goods = [];
                    if(this.isCurrentPlayerActive()){
                        this.activateCompanyAsset(args.args.company_short_name);
                        dojo.query('#haymarket_square').addClass('active');
                        this.activateDemandForCompany(args.args.company_short_name);
                        this.clientStateArgs.income = Number(args.args.income);
                    }
                    break;
                case 'playerDividendsPhase':
                    if(this.isCurrentPlayerActive()){
                        this.activateCompanyAsset(args.args.company_short_name);
                        dojo.query('#haymarket_square').addClass('active');
                    }
                    break;
                case 'playerUseAssetsPhase': // use assets at the end of the operation phase
                    if(this.isCurrentPlayerActive()){
                        this.activateCompanyAsset(args.args.company_short_name);
                    }
                    break;
                case 'client_playerTurnConfirmDistributeGoods':
                    this.activateDemandForCompany(args.args.company_short_name);
                    break;
                case 'client_chooseAsset':
                    this.clientStateArgs.actionArgs = {};
                    this.capital_assets.setSelectionMode(1);
                    dojo.query('#capital_assets>').addClass('active');
                    break;
                case 'playerAssetAutomationBonus': // bonus when purchasing an asset tile during the action phase
                case 'playerAssetWorkerBonus':
                    if(this.isCurrentPlayerActive()){
                        this.clientStateArgs = {};
                        this.clientStateArgs.actionArgs = {};
                        this.clientStateArgs.undoMoves = [];
                        var companyShortName = args.args.company_short_name;
                        dojo.query('#company_'+companyShortName+'>.factory').addClass('active');
                    }
                    break;
                case 'playerFreeActionPhase':
                    this.clientStateArgs = {};
                    this.clientStateArgs.actionArgs = {};
                    
                    if(this.isCurrentPlayerActive()){
                        this.activatePlayerAssets();
                        dojo.query('#haymarket_square').addClass('active');
                    }
                    break;
                case 'playerAssetAppealBonus':
                case 'playerActionAppealBonus':
                case 'playerBuyResourceAppealBonus':
                case 'playerProduceGoodsAppealBonus':
                case 'playerDistributeAppealBonus':
                case 'playerDividendsAppealBonus':
                case 'playerFreeActionAppealBonus':
                case 'playerUseAssetsAppealBonus':
                case 'managerBonusAppeal':
                    if(this.isCurrentPlayerActive()){
                        this.clientStateArgs = {};
                        this.clientStateArgs.actionArgs = {};
                        this.clientStateArgs.undoMoves = [];
                        var nextAppealBonus = args.args.next_appeal_bonus;
                        dojo.addClass($('appeal_' + nextAppealBonus), 'active');
                    }
                    break;
                case 'client_appealBonusChooseFactory':
                    var nextAppealBonus = args.args.next_appeal_bonus;
                    dojo.addClass($('appeal_' + nextAppealBonus), 'active');
                    var companyShortName = args.args.company_short_name;
                    dojo.query('#company_'+companyShortName+'>.factory').addClass('active');
                    break;
                case 'managerBonusResources':
                    this.clientStateArgs.resourcesToGet = args.args.resources_gained;
                    this.clientStateArgs.selectedResources = [];
                    break;
                case 'client_tradeChooseCompanyResources':
                    this.activateCompany(this.clientStateArgs.companyShortName);
                    break;
                case 'dummmy':
                    break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
                case 'client_playerConfirmEmergencyFundraise':
                    
                    break;
                case 'playerEmergencyFundraise':
                    dojo.query('#available_shares_company>.stockitem').removeClass('active');
                    break;
                case 'client_playerTurnConfirmBuyResources':
                    
                    break;
                case 'playerSkipSellBuyPhase':
                    dojo.query('#available_shares_company>.stockitem').removeClass('active exhausted');
                    dojo.query('#available_shares_bank>.stockitem').removeClass('active exhausted');
                    dojo.query('.company').removeClass('active');
                    break;
                case 'playerBuyPhase':
                    dojo.query('#available_shares_company>.stockitem').removeClass('active exhausted');
                    dojo.query('#available_shares_bank>.stockitem').removeClass('active exhausted');
                    dojo.query('.company').removeClass('active');
                    break;
                case 'playerSellPhase':
                    if(!this.isSpectator)
                        dojo.query('#personal_area_'+this.player_id+'>.stockitem').removeClass('active');
                    break;
                case 'client_playerStockPhaseSellShares':
                    var playerId = this.getActivePlayerId();
                    this['personal_area_'+playerId].setSelectionMode(0);
                    break;
                case 'client_appealBonusChooseFactory':
                    dojo.query('.appeal-bonus').removeClass('active');
                    dojo.query('.factory').removeClass('active');
                    break;
                case 'playerAssetAppealBonus':
                case 'playerActionAppealBonus':
                case 'playerBuyResourceAppealBonus':
                case 'playerProduceGoodsAppealBonus':
                case 'playerDistributeAppealBonus':
                case 'playerDividendsAppealBonus':
                case 'playerUseAssetsAppealBonus':
                case 'playerFreeActionAppealBonus':
                case 'managerBonusAppeal':
                    dojo.query('.appeal-bonus').removeClass('active');
                    break;
                case 'client_actionChooseFactory':
                case 'client_chooseFactoryRelocate':
                case 'client_placeHiredWorkers':
                case 'playerAssetAutomationBonus':
                case 'playerAssetWorkerBonus':
                case 'client_chooseFactoryRelocateBonus':
                case 'client_actionChooseFactorySkip':
                case 'client_actionChooseFactorySkipToRelocate':
                case 'client_chooseFactoryRelocateDoubleAutomation':
                case 'client_actionChooseFactorySkipWorker':
                    dojo.query('.factory').removeClass('active');
                    break;
                case 'playerActionPhase':
                    dojo.query('.worker_spot').removeClass('active');
                    dojo.query('.asset-tile').removeClass('active');
                    dojo.query('#haymarket_square').removeClass('active');
                    break;
                case 'client_actionChooseCompany':
                case 'client_tradeChooseCompany':
                case 'client_tradeChooseCompanyResources':
                case 'playerStartFirstCompany':
                case 'client_chooseCompanyToPay':
                    dojo.query('.company').removeClass('active');
                    break;
                case 'playerBuyResourcesPhase':
                    dojo.query('#supply_10>.stockitem').removeClass('active');
                    dojo.query('#supply_20>.stockitem').removeClass('active');
                    dojo.query('#supply_30>.stockitem').removeClass('active');
                    dojo.query('.asset-tile').removeClass('active');
                    dojo.query('#haymarket_square').removeClass('active');
                    break;
                case 'playerProduceGoodsPhase':
                    dojo.query('.asset-tile').removeClass('active');
                    dojo.query('#haymarket_square').removeClass('active');
                    dojo.query('.factory').removeClass('active');
                    break;
                case 'playerDistributeGoodsPhase':
                    dojo.query('.asset-tile').removeClass('active');
                    dojo.query('#haymarket_square').removeClass('active');
                    dojo.query('.demand').removeClass('active');
                    dojo.removeClass('bank', 'active');
                    break;
                case 'playerDividendsPhase':
                    dojo.query('.asset-tile').removeClass('active');
                    dojo.query('#haymarket_square').removeClass('active');
                    break;
                case 'playerUseAssetsPhase':
                    dojo.query('.asset-tile').removeClass('active');
                    break;
                case 'client_playerTurnConfirmDistributeGoods':
                    dojo.query('.demand').removeClass('active');
                    dojo.removeClass('bank', 'active');
                    break;
                case 'playerFreeActionPhase':
                    dojo.query('.asset-tile').removeClass('active');
                    dojo.query('#haymarket_square').removeClass('active');
                    break;
                case 'client_chooseAsset':
                    this.capital_assets.setSelectionMode(0);
                    dojo.query('#capital_assets>').removeClass('active');
                    break;
                case 'client_confirmGainLessResources':
                    dojo.query('.company-content').removeClass('active');
                    break;
                case 'client_playerTurnSelectStartingShareValue':
                    this.available_companies.unselectAll();
                    dojo.query('.company').removeClass('active');
                    break;
                case 'client_playerTurnBuyCertificate':
                    dojo.query('#available_shares_company>.stockitem').removeClass('exhausted');
                    dojo.query('#available_shares_bank>.stockitem').removeClass('exhausted');
                    break;
                case 'playerBuildingPhase':
                case 'clientPlayerDiscardBuilding':
                    if(!this.isSpectator)
                        dojo.query('#building_area_'+this.player_id+'>.stockitem').removeClass('active');
                    break;
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
                    case 'client_confirmFirstPlayer':
                        this.addActionButton( 'accept_first_player', _('Accept'), 'onAcceptFirstPlayer');
                        this.addActionButton( 'decline_first_player', _('Decline'), 'onDeclineFirstPlayer');
                        break;
                    case 'playerconfirmDirectorship':
                        this.addActionButton( 'confirm_gain_directoship', _('Confirm'), 'onConfirmDirectorshipChange');
                        this.addActionButton( 'undo', _('Undo Whole Stock Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'client_playerConfirmEmergencyFundraise':
                        this.addActionButton( 'confirm', _('Confirm'), 'onConfirmEmergencyFundraise');
                        this.addActionButton( 'concel', _('Cancel'), 'onCancelEmergencyFundraise');
                        break;
                    case 'playerEmergencyFundraise':
                        this.addActionButton( 'pass', _('pass'), 'onPassEmergencyFundraise');
                        break;
                    case 'playerActionPhase':
                        this.addActionButton( 'undo', _('Undo Whole Action Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'client_appealBonusChooseFactory':
                        this.addActionButton( 'concel', _('Cancel'), 'onCancelAppealBonus');
                        break;
                    case 'client_confirmGainLessSalespeople':
                        this.addActionButton( 'confirm_gain_salesperson', _('Confirm'), 'onConfirmAction');
                        this.addActionButton( 'concel_gain_salesperson', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_confirmGainLessSalespeopleAppealBonus':
                        this.addActionButton( 'confirm_gain_salesperson', _('Confirm'), 'gainAppealBonus');
                        this.addActionButton( 'concel_gain_salesperson', _('Cancel'), 'onCancelAppealBonus');
                        break;
                    case 'playerSkipSellBuyPhase':
                        this.addActionButton( 'stock_pass', _('Pass Stock Action'), 'onStockPass');
                        this.addActionButton( 'undo', _('Undo Whole Stock Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'playerBuyPhase':
                        this.addActionButton( 'skip_buy', _('Skip'), 'onSkipBuy');
                        this.addActionButton( 'undo', _('Undo Whole Stock Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'client_playerTurnSelectStartingShareValue':
                        this.addActionButton( 'initial_share_35', '$35', 'onStartCompany');
                        this.addActionButton( 'initial_share_40', '$40', 'onStartCompany');
                        this.addActionButton( 'initial_share_50', '$50', 'onStartCompany');
                        this.addActionButton( 'initial_share_60', '$60', 'onStartCompany');
                        this.addActionButton( 'concel_buy', _('Cancel'), 'onCancel');
                        break;
                    case 'client_playerTurnBuyCertificate':
                        this.addActionButton( 'confirm_buy', _('Confirm'), 'onConfirmBuy');
                        this.addActionButton( 'concel_buy', _('Cancel'), 'onCancelBuyCertificate');
                        break;
                    case 'playerSellPhase':
                        this.addActionButton( 'skip_sell', _('Skip to Buy'), 'onSkipSell');
                        this.addActionButton( 'stock_pass', _('Pass Stock Action'), 'onStockPass');
                        break;
                    case 'client_playerTurnConfirmSellShares':
                        this.addActionButton( 'confirm_sell', _('Confirm'), 'onConfirmShareSell');
                        this.addActionButton( 'skip_sell', _('Skip to Buy'), 'onSkipSell');
                        this.addActionButton( 'stock_pass', _('Pass Stock Action'), 'onStockPass');
                        break;
                    case 'clientPlayerDiscardBuilding':
                        this.addActionButton( 'cancel_play_building', _('Cancel'), 'onCancelSelectBuildings');
                        break;
                    case 'clientBuildingPhaseConfirm':
                        this.addActionButton( 'confirm_buildings', _('Confirm'), 'onConfirmBuildings');
                        this.addActionButton( 'cancel_play_building', _('Cancel'), 'onCancelSelectBuildings');
                        break;
                    case 'client_actionChooseFactory':
                        this.addActionButton( 'concel_action', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_actionChooseFactorySkip':
                        this.addActionButton( 'skip_action', _('Skip Rest of Action'), 'onConfirmActionSkip', null, false, 'red');
                        this.addActionButton( 'concel_action', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_chooseCompanyToPay':
                        this.addActionButton( 'concel_action', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_actionChooseFactorySkipToRelocate':
                        this.addActionButton( 'skip_action', _('Skip Second Automation and Relocate Worker'), 'onSkipToRelocate', null, false, 'red');
                        this.addActionButton( 'concel_action', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_actionChooseFactorySkipWorker':
                        this.addActionButton( 'skip_action', _('Skip Receive Worker'), 'onSkipReceiveWorker', null, false, 'red');
                        this.addActionButton( 'concel_action', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_actionChooseCompany':
                    case 'client_tradeChooseCompany':
                        this.addActionButton( 'concel_buy', _('Cancel'), 'onCancelAction');
                        break;

                    case 'client_chooseFactoryRelocate':
                    case 'client_chooseFactoryRelocateDoubleAutomation':
                    case "client_chooseFactoryRelocateBonus":
                        this.addActionButton( 'cancel_relocate', _('Cancel'), 'onCancelAction');
                        break;
                    case "client_placeHiredWorkers":
                        this.addActionButton( 'confirm_gain_resource', _('Confirm'), 'onConfirmAction');
                        this.addActionButton( 'cancel_gain_less', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_playerTurnConfirmBuyResources':
                        this.addActionButton( 'confirm_buy_resources', _('Confirm'), 'onConfirmBuyResources');
                        this.addActionButton( 'cancel_buy_resources', _('Cancel'), 'onCancelBuyResources');
                        break;
                    case 'playerBuyResourcesPhase':
                        this.addActionButton( 'skip_buy_resources', _('Skip'), 'onSkipBuyResources');
                        this.addActionButton( 'undo', _('Undo Whole Operation Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'playerProduceGoodsPhase':
                        this.addActionButton( 'skip_produce_goods', _('Skip'), 'onSkipProduceGoods');
                        this.addActionButton( 'undo', _('Undo Whole Operation Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'playerDistributeGoodsPhase':
                        this.addActionButton( 'skip_distribute_goods', _('Skip'), 'onSkipDistributeGoods');
                        this.addActionButton( 'undo', _('Undo Whole Operation Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'client_playerTurnConfirmDistributeGoods':
                        this.addActionButton( 'confirm_distribute_goods', _('Confirm'), 'onConfirmDistributeGoods');
                        this.addActionButton( 'cancel_distribute_goods', _('Cancel'), 'onCancelDistributeGoods');
                        break;
                    case 'playerDividendsPhase':
                        this.addActionButton( 'confirm_dividends', _('Confirm'), 'onConfirmPayDividends');
                        this.addActionButton( 'withhold_dividends', _('Withhold'), 'onWithhold');
                        this.addActionButton( 'undo', _('Undo Whole Operation Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'playerUseAssetsPhase':
                        this.addActionButton( 'finish', _('Finish Operation Phase'), 'onFinish');
                        this.addActionButton( 'undo', _('Undo Whole Operation Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'client_confirmGainLessResources':
                        this.addActionButton( 'confirm_gain_resource', _('Confirm'), 'onConfirmAction');
                        this.addActionButton( 'cancel_gain_less', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_confirmGainLessResourcesAsset':
                        this.addActionButton( 'confirm_gain_resource', _('Confirm'), 'onConfirmAssetUse');
                        this.addActionButton( 'cancel_gain_less', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_chooseAsset':
                        this.addActionButton( 'cancel_choose_asset', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_chooseKeepOrReplace':
                        this.addActionButton( 'replace_asset', _('Replace'), 'onReplaceAsset');
                        this.addActionButton( 'keep_asset', _('Keep'), 'onKeepAsset');
                        this.addActionButton( 'cancel_choose_asset', _('Cancel'), 'onCancelAction');
                        break;
                    case 'playerAssetWorkerBonus':
                    case 'playerAssetAutomationBonus':
                        this.addActionButton( 'skip_bonus', _('Skip'), 'onSkipAssetBonus');
                        this.addActionButton( 'undo', _('Undo Whole Action Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'client_tradeChooseCompanyResources':
                        var options = this.clientStateArgs.options;
                        for(var i = 0; i < options.length; i++){
                            var option = options[i];
                            var type = option.type;
                            var div = '<div class="' + type + '-item resource"></div><div class="' + type + '-item resource"></div>'
                            this.addActionButton( 'choose_' + type, div, 'onChooseCompanyResources');
                        }
                        this.addActionButton( 'cancel_trade', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_tradeChooseHaymarketResource':
                        var options = this.clientStateArgs.haymarketOptions;
                        for(var i = 0; i < options.length; i++){
                            var option = options[i];
                            var type = option.type;
                            var div = '<div class="' + type + '-item resource"></div>'
                            this.addActionButton( 'choose_' + type, div, 'onChooseHaymarketResource');
                        }
                        this.addActionButton( 'cancel_trade', _('Cancel'), 'onCancelAction');
                        break;
                    case 'playerFreeActionPhase':
                        this.addActionButton( 'pass_free_action', _('Pass'), 'onPassFreeAction');
                        this.addActionButton( 'undo', _('Undo Whole Action Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'playerAssetAppealBonus':
                    case 'playerActionAppealBonus':
                        this.addActionButton( 'forfeit_bonus', _('Forfeit for $25'), 'onForfeitBonus');
                        this.addActionButton( 'undo', _('Undo Whole Action Phase'), 'onUndo', null, false, 'red');
                        break;
                    case 'playerBuyResourceAppealBonus':
                    case 'playerProduceGoodsAppealBonus':
                    case 'playerDistributeAppealBonus':
                    case 'playerDividendsAppealBonus':
                    case 'playerUseAssetsAppealBonus':
                    case 'playerFreeActionAppealBonus':
                    case 'managerBonusAppeal':
                        this.addActionButton( 'forfeit_bonus', _('Forfeit for $25'), 'onForfeitBonus');
                        break;
                    case 'managerBonusResources':
                        var options = this.getHaymarketResourceOptions();
                        this.clientStateArgs.haymarketOptions = options;
                    case 'client_managerBonusSelectResource':
                        var options = this.clientStateArgs.haymarketOptions;
                        for(var i = 0; i < options.length; i++){
                            var option = options[i];
                            var type = option.type;
                            var div = '<div class="' + type + '-item resource"></div>'
                            this.addActionButton( 'choose_' + type, div, 'onChooseResourceBonus');
                        }
                        break;
                    case 'client_confirmGainDifferentResources':
                        var options = this.getHaymarketDifferentResourceOptions();
                        if(options.length !== 0) {
                            for(var i = 0; i < options.length; i++){
                                var option = options[i];
                                if(option.type1) {
                                    var div = '<div id="choose_' + option.id1 + '" class="' + option.type1 + '-item resource"></div><div id="choose_' + option.id2 + '" class="' + option.type2 + '-item resource"></div>'
                                    this.addActionButton( 'choose_' + option.type1 + '_' + option.type2, div, 'onChooseResources');
                                } else {
                                    var div = '<div id="choose_' + option.id + '" class="' + option.type + '-item resource"></div>'
                                    this.addActionButton( 'choose_' + option.type, div, 'onChooseResources');
                                }
                            }
                        }
                        this.addActionButton( 'cancel_trade', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_confirmGainSameResources':
                        var options = this.getHaymarketSameResourceOptions();
                        if(options.length !== 0) {
                            for(var i = 0; i < options.length; i++){
                                var option = options[i];
                                if(option.ids.length > 1) {
                                    var div = '<div id="choose_' + option.ids[0] + '" class="' + option.type + '-item resource"></div><div id="choose_' + option.ids[1] + '" class="' + option.type + '-item resource"></div>'
                                    this.addActionButton( 'choose_' + option.type, div, 'onChooseResources');
                                } else {
                                    var div = '<div id="choose_' + option.id + '" class="' + option.type + '-item resource"></div>'
                                    this.addActionButton( 'choose_' + option.type, div, 'onChooseResources');
                                }
                            }
                        }
                        this.addActionButton( 'cancel_trade', _('Cancel'), 'onCancelAction');
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        getWorkerLocation: function(tokenId){
            var worker = $(tokenId);
            if(worker == null)
                return null;

            var parentId = worker.parentNode.id;
            
            if(parentId == "job_market")
                return "job_market";

            if(parentId.indexOf("automation") !== -1)
                return parentId; // swift_automation_holder_1_0
            
            var split = parentId.split("_");
            return split[0] + "_" + split[1]; // switf_3
        },

        getTooltipHtml: function(positionLookup, objectType, text, imagesPerRow, width, height){
            var imagePosition = positionLookup[objectType];
            var horizontalOffset = (imagePosition % imagesPerRow) * width;
            var verticalOffset = Math.floor(imagePosition / imagesPerRow ) * height;
            var style = "-" + horizontalOffset + "px -" + verticalOffset + "px;";
            return "<div class='clearfix'><div class='tile-tooltip' style='background-position: " + style + "'></div><span>" + text + "</span></div>";
        },

        deactivateTooExpensiveStocks: function(money){
            var companyShares = this.available_shares_company.getAllItems();
            for(var i = 0; i < companyShares.length; i++){
                var share = companyShares[i];
                var shareElementId = this.available_shares_company.getItemDivId(share.id);
                var shareId = share.id.split('_')[2]; //brunswick_common_145
                var value = this.gamedatas.counters['stock_' + shareId].counter_value;
                if(value > money){
                    dojo.addClass(shareElementId, 'exhausted');
                }
            }
            var bankShares = this.available_shares_bank.getAllItems();
            for(var i = 0; i < bankShares.length; i++){
                var share = bankShares[i];
                var shareElementId = this.available_shares_bank.getItemDivId(share.id);
                var shareId = share.id.split('_')[2]; //brunswick_common_145
                var value = this.gamedatas.counters['stock_' + shareId].counter_value;
                if(value > money){
                    dojo.addClass(shareElementId, 'exhausted');
                }
            }
        },

        activateCompanyShares: function(shortName){
            var stockElements = dojo.query('#available_shares_company>.stockitem');
            for(var i = 0; i < stockElements.length; i++){
                var stockElement = stockElements[i];
                if(stockElement.id.indexOf(shortName) !== -1){
                    dojo.addClass(stockElement, 'active');
                }
            }
        },

        placeRoundAndPhaseMarkers: function(gamedatas){
            var round = Number(gamedatas.round) + 1;
            var phase = Number(gamedatas.phase) + 1;
            
            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': 'round_marker',
                'class': 'black-marker'
            } ), 'round_' + round);

            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': 'phase_marker',
                'class': 'black-marker'
            } ), 'phase_' + phase);
        },

        createDemandSpaceZones: function(){
            
            this.createZone('demand27_goods', 13, 22, true);
            this.createZone('demand28_goods', 13, 22, false);
            this.createZone('demand29_goods', 13, 22, true);
            this.createZone('demand30_goods', 13, 22, false);
            this.createZone('demand31_goods', 13, 22, true);
            this.createZone('demand32_goods', 13, 22, false);
            this.createZone('demand25_goods', 13, 22, true);
            this.createZone('demand26_goods', 13, 22, false);
            this.createZone('demand33_goods', 13, 22, false);

            dojo.query(".demand").connect( 'onclick', this, 'onDemandSpaceClicked' );
            dojo.query("#bank").connect( 'onclick', this, 'onDemandSpaceClicked' );

            this.addTooltip( "bank", "", _( "Since you can always sell goods at half-price, this space can be used to do so when all the demand tiles have been filled and the half-price space has not yet been revealed on the board." ));
        },

        createZone: function(name, width, height, hasPattern){
            var zone = new ebg.zone();
            zone.create( this, name, width, height );
            this[name] = zone;

            if(!hasPattern)
                return;

            zone.setPattern('custom');
            zone.itemIdToCoords = function( i, control_width ) {
                if( i==0 )
                    return { x:13,y:8, w:13, h:22 };
                else if(i==1)
                    return { x:35,y:8, w:13, h:22 };
                else if(i==2)
                    return { x:13,y:32, w:13, h:22 };
                else if(i==3)
                    return { x:35,y:32, w:13, h:22 };
                else if(i==4)
                    return { x:13,y:54, w:13, h:22 };
                else if(i==5)
                    return { x:35,y:54, w:13, h:22 };
            };
        },

        creatAppealBonusTooltips: function(){
            this.addTooltip( "appeal_1", "", _( "Gain a worker from the supply" ));
            this.addTooltip( "appeal_2", "", _( "Gain a salesperson from the supply" ));
            this.addTooltip( "appeal_3", "", _( "Automate a factory" ));
            this.addTooltip( "appeal_4", "", _( "Gain a partner" ));
            this.addTooltip( "appeal_5", "", _( "Gain an Appeal Goods Bonus token, which produces 1 good at the end of the company's production step" ));
            this.addTooltip( "appeal_6", "", _( "Increase company's share value one step along the stock track" ));
            this.addTooltip( "appeal_7", "", _( "Gain an Appeal Goods Bonus token, which produces 1 good at the end of the company's production step" ));
            this.addTooltip( "appeal_8", "", _( "Increase company's share value one step along the stock track" ));

            this.addTooltip( "job_market_worker", "", _( "When you place a Partner here, your company may hire any number of Workers by paying the bank for each Worker it hires." ));
            // advertising for ad blockers
            this.addTooltip( "banana", "", _( "When you place a Partner here and pay $20, increase your company’s Appeal by 1 space on the Appeal Track. Additionnally, you may become the starting player for the Action Phase. This change in turn order takes effect immediately." ));
            this.addTooltip( "fundraising_40", "", _( "When you place a Partner here, your company gains $40 from the bank." ));
            this.addTooltip( "fundraising_60", "", _( "When you place a Partner here, your company gains $60 from the bank." ));
            this.addTooltip( "fundraising_80", "", _( "When you place a Partner here, your company gains $80 from the bank." ));
            this.addTooltip( "hire_manager", "", _( "You may spend $60 and place a Partner here to hire a single Manager." ));
            this.addTooltip( "hire_salesperson", "", _( "You may spend $70 and place a Partner here to hire a single Salesperson." ));
            this.addTooltip( "capital_investment", "", _( "When you place a Partner here, your company may purchase a Capital Asset from the available Capital Asset tiles on the game board. To do so, your company will pay the bank the amount indicated above the Capital Asset tile and then you will place that tile on the Company Charter." ));
            this.addTooltip( "extra_dividends", "", _( "You must have at least $100 in your company treasury for you to be able to take this action. When you place a Partner here your company may pay an immediate dividend of $10 per share to its shareholders. This can increase your company's share value." ));
            this.addTooltip( "haymarket_square", "", _( "You may trade for any resources in Haymarket Square by exchanging two identical resources for 1 of any other resource currently available there." ));
        },

        moveAutomatedWorker: function(companyShortName, fromFactoryNumber, toFactoryNumber){
            // the worker is on an automation spot
            var factorySelector = '#'+companyShortName+'_factory_'+fromFactoryNumber; //#brunswick_factory_2
            var worker = dojo.query(factorySelector + '>.automation_holder>.worker')[0];
            var automationHolder = worker.parentNode;

            // move to first empty spot in new factory
            var toFactorySelector = '#'+companyShortName+'_factory_'+toFactoryNumber;
            var workerHolders = dojo.query(toFactorySelector + '>.worker-holder:empty');

            if(workerHolders.length == 0)
                return false;

            var workerHolder = workerHolders[0];
            dojo.place(worker, workerHolder);
            this.placeOnObject(worker, automationHolder);
            this.slideToObject(worker, workerHolder).play();

            // we mark this worker as first-automation
            // otherwise we have no clue that this is the automated worker that was already moved
            // when we get notification back from server
            dojo.addClass(worker, 'first-automation');

            return true;
        },

        createTempWorker: function(companyShortName, factoryNumber){
            var workerSpotId = this.getNextAvailableWorkerSpot(companyShortName + '_' + factoryNumber);
            worker = dojo.place( this.format_block( 'jstpl_temp_worker', {} ), workerSpotId);
            this.placeOnObject( worker, 'main_board');
            this.slideToObject( worker, workerSpotId ).play();
        },

        createTempManager: function(shortName, factoryNumber){
            // create manager
            var holder = shortName + '_' + factoryNumber + '_manager_holder';
            var manager = dojo.place( this.format_block( 'jstpl_temp_manager', {} ), holder);
            this.placeOnObject( manager, 'main_board');
            this.slideToObject( manager, holder ).play();
        },

        activateDemandForCompany: function(shortName){
            var companyType = this.gamedatas.all_companies[shortName].type;
            dojo.query('.'+companyType).addClass('active');
            dojo.addClass('bank', 'active');
        },

        activateFactoriesInCompany: function(shortName){
            dojo.query('#company_'+shortName+ ' .factory').addClass('active');
        },

        activateCompany: function(companyShortName){
            var companyElement = $('company_'+companyShortName);
            dojo.addClass(companyElement.parentNode, 'active');
        },

        activateCompanyAsset: function(companyShortName){
            var assetTiles = dojo.query('#company_'+companyShortName +' .asset-tile');
            for(var i = 0; i<assetTiles.length; i++){
                var assetTile = assetTiles[i];
                if(!dojo.hasClass(assetTile, 'exhausted'))
                    dojo.addClass(assetTile, 'active');
            }
        },

        activatePlayerAssets: function(){
            var assetTiles = dojo.query('#player_'+this.player_id+' .asset-tile');
            for(var i = 0; i<assetTiles.length; i++){
                var assetTile = assetTiles[i];
                if(!dojo.hasClass(assetTile, 'exhausted'))
                    dojo.addClass(assetTile, 'active');
            }
        },

        getDifferentHaymarketOptions: function(selectedResources){
            var coal = dojo.query('#haymarket>.coal');
            var livestock = dojo.query('#haymarket>.livestock');
            var steel = dojo.query('#haymarket>.steel');
            var wood = dojo.query('#haymarket>.wood');
            var haymarketOptions = [];
            for(var i = 0; i < coal.length; i++){
                item = coal[i];
                var found = false;
                dojo.forEach(selectedResources, function(selected){
                    if('haymarket_item_' + selected.id == item.id){
                        found = true;
                        return;
                    }
                })
                if(found)
                    continue;
                var elementId = coal[i].id; // haymarket_item_69
                var resourceId = elementId.split('_')[2];
                haymarketOptions.push({type: 'coal', id: resourceId});
                break;
            }
            for(var i = 0; i < livestock.length; i++){
                item = livestock[i];
                var found = false;
                dojo.forEach(selectedResources, function(selected){
                    if('haymarket_item_' + selected.id == item.id){
                        found = true;
                        return;
                    }
                })
                if(found)
                    continue;
                var elementId = livestock[i].id; // haymarket_item_69
                var resourceId = elementId.split('_')[2];
                haymarketOptions.push({type: 'livestock', id: resourceId});
                break;
            }
            for(var i = 0; i < steel.length; i++){
                item = steel[i];
                var found = false;
                dojo.forEach(selectedResources, function(selected){
                    if('haymarket_item_' + selected.id == item.id){
                        found = true;
                        return;
                    }
                })
                if(found)
                    continue;
                var elementId = steel[i].id; // haymarket_item_69
                var resourceId = elementId.split('_')[2];
                haymarketOptions.push({type: 'steel', id: resourceId});
                break;
            }
            for(var i = 0; i < wood.length; i++){
                item = wood[i];
                var found = false;
                dojo.forEach(selectedResources, function(selected){
                    if('haymarket_item_' + selected.id == item.id){
                        found = true;
                        return;
                    }
                })
                if(found)
                    continue;
                var elementId = wood[i].id; // haymarket_item_69
                var resourceId = elementId.split('_')[2];
                haymarketOptions.push({type: 'wood', id: resourceId});
                break;
            }

            return haymarketOptions;
        },

        getHaymarketSameResourceOptions: function(){
            var coal = dojo.query('#haymarket>.coal');
            var livestock = dojo.query('#haymarket>.livestock');
            var steel = dojo.query('#haymarket>.steel');
            var wood = dojo.query('#haymarket>.wood');

            var options = [];
            if(coal.length > 0){
                var elementId = coal[0].id; // brunswick_resources_item_68
                var resourceId = elementId.split('_')[2];
                var option = {type: 'coal', ids: [resourceId]};
                options.push(option);
                
                if(coal.length > 1){
                    elementId = coal[1].id;
                    resourceId = elementId.split('_')[2];
                    option.ids.push(resourceId);
                }
            }
            if(livestock.length > 0){
                var elementId = livestock[0].id; // brunswick_resources_item_68
                var resourceId = elementId.split('_')[2];
                var option = {type: 'livestock', ids: [resourceId]};
                options.push(option);
                
                if(livestock.length > 1){
                    elementId = livestock[1].id;
                    resourceId = elementId.split('_')[2];
                    option.ids.push(resourceId);
                }
            }
            if(steel.length > 0){
                var elementId = steel[0].id; // brunswick_resources_item_68
                var resourceId = elementId.split('_')[2];
                var option = {type: 'steel', ids: [resourceId]};
                options.push(option);

                if(steel.length > 1){
                    elementId = steel[1].id;
                    resourceId = elementId.split('_')[2];
                    option.ids.push(resourceId);
                }
            }
            if(wood.length > 0){
                var elementId = wood[0].id; // brunswick_resources_item_68
                var resourceId = elementId.split('_')[2];
                var option = {type: 'wood', ids: [resourceId]};
                options.push(option);

                if(wood.length > 1){
                    elementId = wood[1].id;
                    resourceId = elementId.split('_')[2];
                    option.ids.push(resourceId);
                }
            }

            return options;
        },

        getHaymarketDifferentResourceOptions: function(){
            var options = this.getHaymarketResourceOptions();

            if(options.length <= 1)
                return options;

            var differentOptions = [];

            for(var i = 0; i < options.length - 1; i++){
                var option1 = options[i];
                for(var j = i + 1; j < options.length; j++){
                    var option2 = options[j];
                    differentOptions.push({type1: option1.type, type2: option2.type, id1: option1.id, id2: option2.id});
                }
            }
            
            return differentOptions;
        },

        getHaymarketResourceOptions: function(){
            var coal = dojo.query('#haymarket>.coal');
            var livestock = dojo.query('#haymarket>.livestock');
            var steel = dojo.query('#haymarket>.steel');
            var wood = dojo.query('#haymarket>.wood');
            var haymarketOptions = [];
            if(coal.length > 0){
                var elementId = coal[0].id; // haymarket_item_69
                var resourceId = elementId.split('_')[2];
                haymarketOptions.push({type: 'coal', id: resourceId});
            }
            if(livestock.length > 0){
                var elementId = livestock[0].id; // haymarket_item_69
                var resourceId = elementId.split('_')[2];
                haymarketOptions.push({type: 'livestock', id: resourceId});
            }
            if(steel.length > 0){
                var elementId = steel[0].id; // haymarket_item_69
                var resourceId = elementId.split('_')[2];
                haymarketOptions.push({type: 'steel', id: resourceId});
            }
            if(wood.length > 0){
                var elementId = wood[0].id; // haymarket_item_69
                var resourceId = elementId.split('_')[2];
                haymarketOptions.push({type: 'wood', id: resourceId});
            }

            return haymarketOptions;
        },

        moveWorkerFromMarketToFactory: function(companyShortName, factoryNumber){
            var marketWorkers = this.job_market.getAllItems();
            var workerSpotId = this.getNextAvailableWorkerSpot(companyShortName + '_' + factoryNumber);
            var worker = null;
            var workerId = null;
            if(marketWorkers == 0){
                // create worker
                worker = dojo.place( this.format_block( 'jstpl_temp_worker', {} ), workerSpotId);
                this.placeOnObject( worker, 'job_market');
                this.slideToObject( worker, workerSpotId ).play();
            } else {
                worker = $(marketWorkers[0]);
                workerId = worker.id;
                dojo.place(worker, workerSpotId);
                this.placeOnObject( worker, 'job_market');
                this.slideToObject( worker, workerSpotId ).play();
                this.job_market.removeFromZone(workerId);
            }

            return workerId;
        },

        createResourceOptions: function(shortName){
            var coal = dojo.query('#' + shortName + '_resources>.coal');
            var livestock = dojo.query('#' + shortName + '_resources>.livestock');
            var steel = dojo.query('#' + shortName + '_resources>.steel');
            var wood = dojo.query('#' + shortName + '_resources>.wood');

            var options = [];
            if(coal.length >= 2){
                var elementId = coal[0].id; // brunswick_resources_item_68
                var resourceId = elementId.split('_')[3];
                var option = {type: 'coal', ids: [resourceId]};
                options.push(option);
                elementId = coal[1].id;
                resourceId = elementId.split('_')[3];
                option.ids.push(resourceId);
            }
            if(livestock.length >= 2){
                var elementId = livestock[0].id; // brunswick_resources_item_68
                var resourceId = elementId.split('_')[3];
                var option = {type: 'livestock', ids: [resourceId]};
                options.push(option);
                elementId = livestock[1].id;
                resourceId = elementId.split('_')[3];
                option.ids.push(resourceId);
            }
            if(steel.length >= 2){
                var elementId = steel[0].id; // brunswick_resources_item_68
                var resourceId = elementId.split('_')[3];
                var option = {type: 'steel', ids: [resourceId]};
                options.push(option);
                elementId = steel[1].id;
                resourceId = elementId.split('_')[3];
                option.ids.push(resourceId);
            }
            if(wood.length >= 2){
                var elementId = wood[0].id; // brunswick_resources_item_68
                var resourceId = elementId.split('_')[3];
                var option = {type: 'wood', ids: [resourceId]};
                options.push(option);
                elementId = wood[1].id;
                resourceId = elementId.split('_')[3];
                option.ids.push(resourceId);
            }
            
            if(options.length > 0) {
                this.clientStateArgs.companyShortName = shortName;
                this.clientStateArgs.options = options;
                this.setClientState("client_tradeChooseCompanyResources", {
                    descriptionmyturn : _('Choose resources to put in Haymarket Square')
                });
            } else {
                this.showMessage( _("Company doesn't have enough resources to trade"), 'info' );
                return;
            }
        },

        gainResources:function(resourceList){
            var coal = dojo.query('#haymarket>.coal');
            var livestock = dojo.query('#haymarket>.livestock');
            var steel = dojo.query('#haymarket>.steel');
            var wood = dojo.query('#haymarket>.wood');
            var options = [];
            var gotEverything = true;
            for(var index in resourceList){
                var resource = resourceList[index];
                var list = null;
                switch(resource){
                    case 'wood':
                        list = wood;
                        break;
                    case 'steel':
                        list = steel;
                        break;
                    case 'coal':
                        list = coal;
                        break;
                    case 'livestock':
                        list = livestock;
                        break;
                }

                if(list.length > 0){
                    var item = list.pop(); 
                    var id = item.id.split('_')[2]; //haymarket_item_60
                    options.push({id: id, type: resource});
                } else {
                    gotEverything = false;
                }
            }

            for(var i = 0; i < options.length; i++){
                this.clientStateArgs.actionArgs['resource'+i] = options[i].id;
            }

            if(gotEverything){
                return { gotEverything: true };
            } else {
                // go to client state and ask user to confirm
                var items = "";
                if(options.length > 0){
                    for(var i = 0; i < options.length; i++){
                        items += '<div class="' + options[i].type + '-item resource"></div>'
                    }
                } else {
                    items = _("nothing");
                }
                return { gotEverything: false, options: options, optionsString: items };
            }
        },

        // returns whether a worker should be relocated
        automateWorker: function(companyShortName, factoryNumber){

            // workers could temporarily be in automation spots
            // get automations in worker spots and all workers of company
            // check against number of worker spots in factory
            var companySelector = '#company_' + companyShortName;
            var totalWorkers = dojo.query(companySelector + ' .worker').length;
            var totalAutomated = dojo.query(companySelector + ' .worker-holder>.automation_token').length;
            var totalWorkerSpots = dojo.query(companySelector + ' .worker-holder').length;

            // if there is a choice between multiple factories, get the automation
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var automation = dojo.query(factorySelector + '>.automation_holder>.automation_token')[0];

            // get the automation holder
            var automationHolder = automation.parentNode;
            var holderPosition = automationHolder.id.split('_')[4]; // spalding_automation_holder_2_0
            
            // get the worker and worker holder
            var holderId = [companyShortName, factoryNumber, 'worker_holder', holderPosition];
            var workerHolder = dojo.byId(holderId.join('_'));
            var worker = workerHolder.firstChild;

            // move automation token to worker spot
            dojo.place(automation, workerHolder);
            this.placeOnObject(automation, automationHolder);
            this.slideToObject(automation, workerHolder).play();

            // move worker to automation spot (temporarily)
            dojo.place(worker, automationHolder);
            this.placeOnObject(worker, workerHolder);
            this.slideToObject(worker, automationHolder).play();

            this.clientStateArgs.undoMoves.push({'element': automation, 'to': automationHolder});
            this.clientStateArgs.undoMoves.push({'element': worker, 'to': workerHolder});

            var workerId = worker.id.split('_')[1];
            if(totalWorkers + totalAutomated == totalWorkerSpots){
                return { 'workerId': workerId, 'relocate': false };;
            }

            // worker needs to be relocated
            return { 'workerId': workerId, 'relocate': true };
        },
       
        canAutomateFactory: function(companyShortName, factoryNumber){
            // get first automation holder with a child (automation token)
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var automationTokens = dojo.query(factorySelector + '>.automation_holder>.automation_token');

            // because of double automation, sometimes there is a worker in those holders
            // if not found, factory is completely automated
            if(automationTokens.length == 0)
                return false;
            
            var firstToken = automationTokens[0];
            var automationHolder = firstToken.parentNode; // spalding_automation_holder_2_0
            var holderPosition = automationHolder.id.split('_')[4];

            // get the worker holder in the same position as this automation holder
            // check that it exists
            var holderId = [companyShortName, factoryNumber, 'worker_holder', holderPosition];
            var workerHolder = dojo.byId(holderId.join('_')); // spalding_2_worker_holder_0

            return workerHolder != null && workerHolder.childNodes.length > 0;
        },

        createGoalStock: function(goals){
            var newStock = new ebg.stock();

            newStock.create( this, $('goals'), 50, 50);

            // Specify that there are 5 buildings per row
            newStock.image_items_per_row = 5;
            newStock.setSelectionMode(0);
            //newStock.item_margin = 1;
            newStock.centerItems = true;

            var i = 0;
            for(var goalId in goals){
                var hash = this.hashString(goalId);
                var imagePosition = this.goalPositions[goalId];
                newStock.addItemType( hash, i, g_gamethemeurl+'img/buildings_large_final.png', imagePosition );
            }

            newStock.jstpl_stock_item= "<div id=\"${id}\" class=\"stockitem tile\" style=\"top:${top}px;left:${left}px;width:${width}px;height:${height}px;z-index:${position};background-image:url('${image}');\"></div>";

            this.goals = newStock;
        },

        createResourceStock: function(stockName){
            var newStock = new ebg.stock();

            newStock.create( this, $(stockName), 14, 14);

            // Specify that there are 5 buildings per row
            newStock.image_items_per_row = 4;
            newStock.setSelectionMode(0);
            newStock.item_margin = 1;
            newStock.centerItems = true;

            var i = 0;
            var resources = {'livestock': 3, 'coal': 1, 'wood': 2, 'steel': 0};
            for(var resourceName in resources){
                var imagePosition = resources[resourceName];
                var hash = this.hashString(resourceName);
                newStock.addItemType( hash, i, g_gamethemeurl+'img/resources.png', imagePosition );
            }

            this[stockName] = newStock;

            dojo.connect( this[stockName], 'onChangeSelection', this, 'onResourceSelected' );
        },

        createAssetTileStock: function(stockName, capitaAssets){
            var newStock = new ebg.stock();

            newStock.create( this, $(stockName), 50, 50);

            // Specify that there are 5 buildings per row
            newStock.image_items_per_row = 5;
            newStock.setSelectionMode(0);
            newStock.item_margin=5.5;
            newStock.jstpl_stock_item= "<div id=\"${id}\" class=\"stockitem tile\" style=\"top:${top}px;left:${left}px;width:${width}px;height:${height}px;z-index:${position};background-image:url('${image}');\"></div>";

            var i = 0;
            for(var assetName in capitaAssets){
                var hash = this.hashString(assetName);

                var imagePosition = this.assetNameToImagePosition[assetName];
                newStock.addItemType( hash, i, g_gamethemeurl+'img/buildings_large_final.png', imagePosition );

                i++;
            }

            // add an empty asset for when the deck is empty
            newStock.addItemType( 0, -1, g_gamethemeurl+'img/buildings_large_final.png', 61 );

            this[stockName] = newStock;
        },

        createPlayerOrderStock: function(){
            var newStock = new ebg.stock();

            newStock.create( this, $('player_order'), 22, 22);

            newStock.image_items_per_row = 12;
            newStock.setSelectionMode(0);
            newStock.item_margin=-9;

            // red green blue yellow
            // "ff0000", "008000", "0000ff", "ffa500"
            var colorPosition = [ 
                {'color': 'ff0000', 'position': 57},
                {'color': '008000', 'position': 58},
                {'color': '0000ff', 'position': 59},
                {'color': 'ffa500', 'position': 56} ];

            var i = 0;
            for(var i = 0; i < colorPosition.length; i++){
                var item = colorPosition[i];
                var hash = this.hashString(item.color);
                var imagePosition = item.position;
                newStock.addItemType( hash, i, g_gamethemeurl+'img/tokens.png', imagePosition );
            }

            this.player_order = newStock;
        },

        placeManager: function(manager, slideFromSupply){
            var tokenId = 'manager_' + manager.card_id;
            var holder = manager.card_location + '_manager_holder';
            dojo.place( this.format_block( 'jstpl_token', {
                'token_id': tokenId, 
                'token_class': 'moveable-token meeple meeple-tan'
            } ), holder);
            
            if(slideFromSupply){
                this.placeOnObject(tokenId, 'main_board');
                this.slideToObject(tokenId, holder).play();
            }
        },

        placeResource: function(item, from, fromItemDiv){
            var resourceName = item.card_type;
            var hash = this.hashString(resourceName);
            var div = "";
            switch(item.card_location){
                case 'haymarket':
                    this.haymarket.addToStockWithId(hash, item.card_id, fromItemDiv);
                    div = this.haymarket.getItemDivId(item.card_id);
                    dojo.addClass(div, resourceName);
                    if(from != null){
                        this[from].removeFromStockById(item.card_id);
                    }
                    break;
                case 'x':
                    this.supply_x.addToStockWithId(hash, item.card_id);
                    div = this.supply_x.getItemDivId(item.card_id);
                    break;
                case '30':
                    this.supply_30.addToStockWithId(hash, item.card_id);
                    div = this.supply_30.getItemDivId(item.card_id);
                    break;
                case '20':
                    this.supply_20.addToStockWithId(hash, item.card_id);
                    div = this.supply_20.getItemDivId(item.card_id);
                    break;
                case '10':
                    this.supply_10.addToStockWithId(hash, item.card_id);
                    div = this.supply_10.getItemDivId(item.card_id);
                    break;
                default:
                    // this is going in a company (card_location = company short name)
                    this[item.card_location + '_resources'].addToStockWithId(hash, item.card_id, fromItemDiv);
                    div = this[item.card_location + '_resources'].getItemDivId(item.card_id);
                    dojo.addClass(div, resourceName);
                    if(from == 'haymarket'){
                        this.haymarket.removeFromStockById(item.card_id);
                    } else if(from != null){
                        this['supply_' + from].removeFromStockById(item.card_id);
                    }
                    break;
            }

            switch(resourceName)
            {
                case 'livestock':
                    this.addTooltip( div, _('Livestock'), "");
                    break;
                case 'coal':
                    this.addTooltip( div, _('Coal'), "");
                    break;
                case 'wood':
                    this.addTooltip( div, _('Wood'), "");
                    break;
                case 'steel':
                    this.addTooltip( div, _('Steel'), "");
                    break;
            }
            
        },

        placeAsset: function(item, from, fromItemDiv){
            var assetName = item.card_type;
            var hash = this.hashString(assetName);

            var itemDivId = null;
            if(item.card_location == '80' || 
                item.card_location == '70' || 
                item.card_location == '60' || 
                item.card_location == '50' || 
                item.card_location == '40') {
                // place on board
                this.capital_assets.addToStockWithId(hash, assetName + '_' + item.card_id);
                var itemDivId = this.capital_assets.getItemDivId(assetName + '_' + item.card_id);
            } else {
                // place on company
                this[item.card_location + '_asset'].addToStockWithId(hash, assetName + '_' + item.card_id, fromItemDiv);
                itemDivId = this[item.card_location + '_asset'].getItemDivId(assetName + '_' + item.card_id);
                var nodes = dojo.query('#' + itemDivId);
                nodes.removeClass('stockitem_unselectable');
                dojo.attr(itemDivId, 'asset-name', assetName);
                if(item.card_location_arg == 1)
                    nodes.addClass('exhausted');
                if(from)
                    this.capital_assets.removeFromStockById(assetName + '_' + item.card_id);
            }

            var assetMaterial = this.gamedatas.all_capital_assets[assetName];

            var text = _( assetMaterial.tooltip );
            var html = this.getTooltipHtml(this.assetNameToImagePosition, assetName, text, 5, 118, 118); 
            this.addTooltip( itemDivId, html, "");

            return hash;
        },

        placeSalesperson: function(salesperson, slideFromSupply){
            var tokenId = 'salesperson_' + salesperson.card_id;
            var companyShortName = salesperson.card_location;
            
            originalSpot = companyShortName + '_salesperson_holder';
            if(slideFromSupply){
                originalSpot = 'main_board';
            }

            dojo.place( this.format_block( 'jstpl_token', {
                'token_id': tokenId, 
                'token_class': 'moveable-token meeple meeple-black salesperson'
            } ), originalSpot);
            
            this[companyShortName + '_salesperson_holder'].placeInZone(tokenId);
        },

        getSalespersonEmptySpots: function(companyShortName){
            var currentNumberSalersperon = this[companyShortName+'_salesperson_holder'].getAllItems.length;
            var company = this.gamedatas.all_companies[companyShortName];
            return company.salesperson_number - currentNumberSalersperon - 1;
        },

        doesFactoryEmployManager: function(companyShortName, factoryNumber){
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var managerHolderChild = dojo.query(factorySelector + '>.manager-holder>');
            return managerHolderChild.length > 0;
        },

        chooseFactory: function(){
            this.setClientState("client_actionChooseFactory", {
                descriptionmyturn : dojo.string.substitute(_('Choose a factory'),{

                })
            });
        },

        chooseFactorySkip: function(message){
            if(!message)
                message = _('Choose a factory');
            this.setClientState("client_actionChooseFactorySkip", {
                descriptionmyturn : dojo.string.substitute(message,{
                })
            });
        },

        chooseCompany: function(){
            this.setClientState("client_actionChooseCompany", {
                descriptionmyturn : dojo.string.substitute(_('Choose a company'),{

                })
            });
        },

        createWorkerZones: function(actionSpaces){
            for(var actionSpaceName in actionSpaces){
                var zone = new ebg.zone();
                zone.create( this, actionSpaceName+'_holder', 15, 14 );
                this[actionSpaceName + '_holder'] = zone;
            }
        },

        setupNewTrackBuilding: function(item_div, item_type_id, item_id){
            dojo.removeClass(item_div, "stockitem_unselectable");

            // item_id -> building_track_2319929_item_building10_35
            var split = item_id.split('_');
            var building = split[4];

            // create the worker spot
            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': building,
                'class': 'worker_spot building-action'
            } ) , item_div );

            // create the partner zone
            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': building + '_holder',
                'class': ''
            } ) , building );

            var zone = new ebg.zone();
            zone.create( this, building+'_holder', 15, 14 );
            this[building + '_holder'] = zone;

            dojo.connect( $(building), 'onclick', this, 'onWorkerSpotClicked' );
        },

        setupNewBuilding: function(item_div, item_type_id, item_id)
        {
            dojo.connect( $(item_id), 'onclick', this, 'onClickBuildingPlayerArea' );
        },

        createBuildingsStock: function(buildings, stockName, itemCreateCallback){
            var newStock = new ebg.stock();

            newStock.create( this, $(stockName), 50, 50);

            // Specify that there are 5 buildings per row
            newStock.image_items_per_row = 5;
            newStock.setSelectionMode(0);
            
            var i = 0;
            for(var index in buildings){
                var hash = this.hashString(index);

                var imagePosition = this.buildingNumberToImagePosition[index];
                newStock.addItemType( hash, i, g_gamethemeurl+'img/buildings_large_final.png', imagePosition );

                i++;
            }

            newStock.jstpl_stock_item= "<div id=\"${id}\" class=\"stockitem tile\" style=\"top:${top}px;left:${left}px;width:${width}px;height:${height}px;z-index:${position};background-image:url('${image}');\"></div>";
            
            if(itemCreateCallback)
                newStock.onItemCreate = dojo.hitch( this, itemCreateCallback );

            return newStock;
        },

        placeAppealTokens: function(company_order){
            var count = company_order.length;
            for(var i in company_order){
                var company = company_order[i];
                this.placeAppealToken(company, count - company.order);
            }
        },

        placeAppealToken: function(company, weight, previousAppeal){

            var tokenId = 'appeal_token_' + company.short_name;

            if(!dojo.byId(tokenId)){
                // if the token doesn't exist it means A) company just got started B) page refresh
                
                // place the token on the main board
                dojo.place( this.format_block( 'jstpl_appeal_token', {
                    'id': tokenId,
                    'short_name': company.short_name
                } ) , 'main_board' );

                // move it from the player board
                this.placeOnObject( tokenId, 'overall_player_board_'+company.owner_id );

                // to the zone with the right appeal
                this['appeal_zone_'+company.appeal].placeInZone(tokenId, weight);

                // on page refresh, we know how the tokens should be ordered when they are stacked
                if(weight){
                    dojo.setStyle(tokenId, 'z-index', weight);
                }
            } else {
                // if the token already exists then it means the appeal was increased
                
                // remove the token from the previous zone without destroying it
                this['appeal_zone_'+previousAppeal].removeFromZone(tokenId, false);
                
                // place it in the correct zone, and then the order of the tokens will be adjusted
                this['appeal_zone_'+company.appeal].placeInZone(tokenId, weight);
            }

            var allCompanies = this.gamedatas.all_companies;
            this.addTooltip( tokenId, allCompanies[company.short_name].name, "");
        },

        updateAppealTokens: function(appeal, company_order){
            var count = company_order.length;

            this['appeal_zone_'+appeal].removeAll();

            var allCompanies = this.gamedatas.all_companies;
            
            for(var i in company_order){
                var company = company_order[i];
                if(company.appeal != appeal)
                    continue;
                var weight = count - company.order;
                this['appeal_zone_'+company.appeal].placeInZone('appeal_token_'+company.short_name, weight);
                dojo.setStyle('appeal_token_'+company.short_name, 'z-index', weight);
                this.addTooltip( 'appeal_token_'+company.short_name, allCompanies[company.short_name].name, "");
            }
        },

        placeShareValue: function(share_value_step, short_name, playerId){
            dojo.place( this.format_block( 'jstpl_share_token', {
                'short_name': short_name
            } ) , 'main_board' );

            this.placeOnObject( 'share_token_'+short_name, 'overall_player_board_'+playerId );
            this['share_zone_'+share_value_step].placeInZone('share_token_'+short_name);

            var allCompanies = this.gamedatas.all_companies;
            this.addTooltip( 'share_token_'+short_name, allCompanies[short_name].name, "");
        },

        createShareValueZones: function(){
            for(var i = 0; i < 21; i++){
                var zone = new ebg.zone();
                zone.create( this, 'share_zone_'+i, 22, 22 );
                zone.setPattern('diagonal');
                this['share_zone_'+i] = zone;
            }
        },

        createAppealZones: function(){
            for(var i = 0; i < 17; i++){
                var zone = new ebg.zone();
                zone.create( this, 'appeal_zone_'+i, 28, 28 );
                zone.setPattern('diagonal');
                this['appeal_zone_'+i] = zone;
            }
        },

        createJobMarketZone: function(){
            var zone = new ebg.zone();
            zone.create( this, 'job_market', 15, 29 );
            zone.setPattern('custom');
            this.job_market = zone;
            this.job_market.itemIdToCoords = function( i, control_width ) {
                if( i%12==0 )
                {   return {  x:7,y:0, w:15, h:29 }; }
                else if( i%12==1 )
                {   return {  x:36,y:0, w:15, h:29 }; }
                else if( i%12==2 )
                {   return {  x:7,y:27, w:15, h:29 }; }
                else if( i%12==3 )
                {   return {  x:36,y:27, w:15, h:29 }; }
                else if( i%12==4 )
                {   return {  x:7,y:59, w:15, h:29 }; }
                else if( i%12==5 )
                {   return {  x:36,y:59, w:15, h:29 }; }
                else if( i%12==6 )
                {   return {  x:7,y:86, w:15, h:29 }; }
                else if( i%12==7 )
                {   return {  x:36,y:86, w:15, h:29 }; }
                else if( i%12==8 )
                {   return {  x:7,y:118, w:15, h:29 }; }
                else if( i%12==9 )
                {   return {  x:36,y:118, w:15, h:29 }; }
                else if( i%12==10 )
                {   return {  x:7,y:145, w:15, h:29 }; }
                else if( i%12==11 )
                {   return {  x:36,y:145, w:15, h:29 }; }
            };
        },

        placeItemsOnBoard: function(gamedatas){
            var companyWorkers = [];
            var newWeights = {};
            for(var property in gamedatas.items){
                var item = gamedatas.items[property];

                if(item.card_location == 'limbo')
                    continue;
                
                var primaryType = item.primary_type;
                switch(primaryType){
                    case 'stock':
                        this.placeStock(item)
                        break;
                    case 'automation':
                        this.placeAutomationToken(item)
                        break;
                    case 'building':
                        this.placeBuilding(item)
                        break;
                    case 'partner':
                        this.placePartner(item);
                        break;
                    case 'worker':
                        // we place company workers after everything because we want to place automation tokens first
                        if(item.owner_type != 'company')
                            this.placeWorker(item)
                        else
                            companyWorkers.push(item);
                        break;
                    case 'manager':
                        this.placeManager(item, false);
                        break;
                    case 'salesperson':
                        this.placeSalesperson(item, false);
                        break;
                    case 'asset':
                        var hash = this.placeAsset(item)
                        newWeights[hash] = 80-Number(item.card_location); // 80 -> 40
                        break;
                    case 'resource':
                        this.placeResource(item);
                        break;
                    case 'good':
                        this.placeGood(item);
                        break;
                    case 'demand':
                        this.placeDemand(item);
                        break;
                    case 'goal':
                        this.placeGoal(item);
                        break;
                }
            }

            this.capital_assets.changeItemsWeight( newWeights ) // { 1: 10, 2: 20, itemType: Weight }

            // add empty assets when asset deck is empty
            var items = this.capital_assets.getAllItems();
            var emptySpaces = 5 - items.length;
            for(var i = 0; i < emptySpaces; i++){
                this.capital_assets.addToStock(0);
            }

            for(var i = 0; i < companyWorkers.length; i++){
                this.placeWorkerInFactory(companyWorkers[i]);
            }
        },

        placeWorker: function(worker){

            // this method only places workers in job market
            if(worker.card_location != 'job_market')
                return;

            // never more than 12 workers in the market
            if(this.job_market.getItemNumber() == 12)
                return;

            var itemId = worker.card_type + '_' + worker.card_id;

            if(!dojo.byId(itemId)){
                dojo.place( this.format_block( 'jstpl_token', {
                    'token_id': itemId, 
                    'token_class': 'worker'
                } ), worker.card_location );
            }

            this.job_market.placeInZone(itemId);
        },

        placeBuilding: function(building, from){
            var hashBuildingType = this.hashString(building.card_type);
            var itemId = building.card_type+'_'+building.card_id;
            //var hashBuildingType = this.hashString('building21');
            //var itemId = 'building21'+'_'+building.card_id;

            var location = building.card_location; // building_track_2319929 or player_2319930 or player_2319930_play

            var div = null;
            if (location.indexOf('building_track') !== -1){
                var weights = {};
                weights[hashBuildingType] = Number(building.card_location_arg);
                this[location].changeItemsWeight(weights);
                this[location].addToStockWithId(hashBuildingType, itemId, from);
                div = this[location].getItemDivId(itemId);
            } else {
                this.buildings.addToStockWithId(hashBuildingType, itemId, from);
                div = this.buildings.getItemDivId(itemId);
                dojo.removeClass(div, 'stockitem_unselectable');

                if(location.indexOf('_play') !== -1){
                    dojo.addClass(div, "building_to_play");
                } else if(location.indexOf('_discard') !== -1){
                    dojo.addClass(div, "building_to_discard");
                }
            }

            var buildingMaterial = this.gamedatas.all_buildings[building.card_type];
            var text = _( buildingMaterial.tooltip );
            var html = this.getTooltipHtml(this.buildingNumberToImagePosition, building.card_type, text, 5, 118, 118); 
            this.addTooltip( div, html, "");
        },

        placeStock: function(stock, from){
            var stockType = stock.card_type;
            
            if(stock.owner_type == 'player'){
                var hashStockType = this.hashString(stockType);
                this[stock.card_location].addToStockWithId(hashStockType, stockType+'_'+stock.card_id, from);
            } else if (stock.owner_type == 'company') {
                var hashStockType = this.hashString(stockType);
                this.available_shares_company.addToStockWithId(hashStockType, stockType+'_'+stock.card_id, from);
            } else if (stock.owner_type == 'bank'){
                var hashStockType = this.hashString(stockType);
                this.available_shares_bank.addToStockWithId(hashStockType, stockType+'_'+stock.card_id, from);
            }
        },

        moveStock: function(stock, from)
        {
            var stockType = stock.card_type;
            if(stock.owner_type == 'player') {
                var hashStockType = this.hashString(stockType);
                this[stock.card_location].addToStockWithId(hashStockType, stockType+'_'+stock.card_id, from);
                this[from].removeFromStockById(stockType+'_'+stock.card_id);
            } else if (stock.owner_type == 'company') {
                // TODO: this can only happen during emergency fundraise in the advanced game
            }
        },

        onAssetCreated: function(item_div, asset_type_id, item_id){
            dojo.addClass($(item_div), "asset-tile");
            dojo.connect( $(item_div), 'onclick', this, 'onAssetClicked' );
        },

        setupNewStock: function(stock_div, stock_type_id, stock_id){
            var stockId = stock_id.split('_')[6];
            var elementId = 'stock_' + stockId;
            var element = dojo.place( this.format_block( 'jstpl_stock_content', {
                'stock_id': elementId,
            } ), stock_div.id );

            if(this.gamedatas.counters[elementId]){
                element.lastChild.innerText = this.gamedatas.counters[elementId].counter_value;
            }
        },

        slideVertically : function(token, finalPlace, tlen) {
            if($(token).parentNode.id === finalPlace)
                return;
            var box = this.attachToNewParentNoDestroy(token, finalPlace);
            var left = dojo.style(token, "left");
            var anim = this.slideToObjectPos(token, finalPlace, left, box.t, tlen, 0);

            dojo.connect(anim, "onEnd", dojo.hitch(this, function(token) {
                this.stripPosition(token);
                this.available_companies.resetItemsPosition();
                this.available_shares_bank.resetItemsPosition();
                this.available_shares_company.resetItemsPosition();
            }));

            anim.play();
        },

        // This method will attach mobile to a new_parent without destroying, unlike original attachToNewParent which destroys mobile and
        // all its connectors (onClick, etc)
        attachToNewParentNoDestroy : function(mobile, new_parent) {
            if (mobile === null) {
                console.error("attachToNewParent: mobile obj is null");
                return;
            }
            if (new_parent === null) {
                console.error("attachToNewParent: new_parent is null");
                return;
            }
            if (typeof mobile == "string") {
                mobile = $(mobile);
            }
            if (typeof new_parent == "string") {
                new_parent = $(new_parent);
            }

            var src = dojo.position(mobile);
            dojo.style(mobile, "position", "absolute");
            dojo.place(mobile, new_parent, "last");
            var tgt = dojo.position(mobile);
            var box = dojo.marginBox(mobile);
            var cbox = dojo.contentBox(mobile);
            var left = box.l + src.x - tgt.x;
            var top = box.t + src.y - tgt.y;
            dojo.style(mobile, "top", top + "px");
            dojo.style(mobile, "left", left + "px");
            box.l += box.w - cbox.w;
            box.t += box.h - cbox.h;
            return box;
        },

        stripPosition : function(token) {
            // console.log(token + " STRIPPING");
            // remove added positioning style
            dojo.style(token, "display", null);
            dojo.style(token, "top", null);
            dojo.style(token, "left", null);
            dojo.style(token, "position", null);
        },

        setupCompany: function(company_div, company_type_id, item_id){
            // Add some custom HTML content INSIDE the Stock item:
            // company_div_id looks like this : company_area_2319930_item_libby or available_companies_item_libby
            var array = item_id.split('_');
            var companyShortName = array[array.length - 1];
            var companyId = 'company_' + companyShortName;
            var companyElement = dojo.place( this.format_block( 'jstpl_company_content', {
                'id': companyId,
                'short_name': companyShortName,
                'item_id': item_id
            } ), company_div );

            this.addTooltip( item_id + '_appeal', _( "Current Appeal (Order of Operation)" ), "");

            // this is a hack because when the companies are in transit between stocks
            // there is momentarily two companies with contents that have the same ids
            // because of that, we need to update the company treasury manually
            var companyTreasuryElement = dojo.query('#' + item_id + ' #money_'+companyShortName)[0];
            var companyAppealElement = dojo.query('#' + item_id + ' #appeal_'+companyShortName)[0];
            var companyOrderElement = dojo.query('#' + item_id + ' #order_'+companyShortName)[0];
            companyTreasuryElement.innerText = this.gamedatas.counters['money_' + companyShortName].counter_value;
            companyAppealElement.innerText = this.gamedatas.counters['appeal_' + companyShortName].counter_value;
            companyOrderElement.innerText = this.gamedatas.counters['order_' + companyShortName].counter_value;

            var company = this.gamedatas.all_companies[companyShortName];

            dojo.addClass(company_div, 'company');
            dojo.connect(company_div, 'onclick', this, 'onCompanyClicked' );
            
            var factoryWidth = companyShortName == 'henderson' ? 93 : 97;
            var distanceToLastAutomation = companyShortName == 'henderson' ? 76 : 80;
            for(var factoryNumber in company.factories){
                var factory = company.factories[factoryNumber];
                
                var factoryId = companyShortName + '_factory_' + factoryNumber;
                var factoryElement = dojo.place( this.format_block( 'jstpl_factory', {
                    'id': factoryId,
                    'left': 65+factoryWidth*(factoryNumber-1), // left of first factory + factory width * factory #
                    'width': factoryWidth
                } ), companyElement );

                dojo.connect(factoryElement, 'onclick', this, 'onFactoryClicked');

                // add automation token spots
                var numberOfAutomations = factory.automation;
                for(var i = 0; i < numberOfAutomations; i++){
                    dojo.place( this.format_block( 'jstpl_automation_holder', {
                        'short_name': companyShortName,
                        'factory': factoryNumber,
                        'number': i,
                        'left': distanceToLastAutomation-20*(numberOfAutomations-i-1) // left of first factory + factory width * factory # + left of last automation - distance between automation
                    } ), factoryElement );
                }

                // add worker spots
                var numberOfWorkers = factory.workers;
                var initialWorkerLeft = 43;
                if(numberOfWorkers == 2){
                    initialWorkerLeft = 28;
                } else if (numberOfWorkers == 3){
                    initialWorkerLeft = 15;
                }
                for(var i = 0; i < numberOfWorkers; i++){
                    dojo.place( this.format_block( 'jstpl_worker_holder', {
                        'id': companyShortName+'_'+factoryNumber+'_worker_holder_'+i,
                        'left': initialWorkerLeft+27*i // left of first factory + factory width * factory # + left of last automation - distance between automation
                    } ), factoryElement );
                }

                // add manager spots
                dojo.place( this.format_block( 'jstpl_manager_holder', {
                    'id': companyShortName+'_'+factoryNumber+'_manager_holder'
                } ), factoryElement );
            }

            // add salesperson spots
            dojo.place( this.format_block( 'jstpl_salesperson_holder', {
                'id': companyShortName+'_salesperson_holder',
                'top': 85
            } ), companyElement );

            var zone = new ebg.zone();
            zone.create( this, companyShortName + '_salesperson_holder', 21, 21 );
            zone.setPattern( 'custom' );
            zone.itemIdToCoords = function( i, control_width ) {
                return {x:0, y: 33*i, w:21, h:21}
            };
            this[companyShortName + '_salesperson_holder'] = zone;

            // add price tooltips for salespeople
            for(var i = 0; i < Number(company.salesperson_number); i++){
                var price = company.salesperson[i];

                var divId = companyShortName + '_salesperson_' + i;

                dojo.place( this.format_block( 'jstpl_price_tooltip', {
                    'id': divId,
                    'top': 52+33*i
                } ), companyElement );

                this.addTooltip( divId, dojo.string.substitute(_('$${price}'),{
                    'price': price
                }), "");
            }

            // add resource stock
            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': companyShortName + '_resources',
                'class': 'company-resources'
            } ), companyElement );
            this.createResourceStock(companyShortName + '_resources');

            // add goods zone
            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': companyShortName+'_goods',
                'class': 'goods-holder'
            } ), companyElement );
            
            var zone = new ebg.zone();
            zone.create( this, companyShortName + '_goods', 13, 22 );
            this[companyShortName + '_goods'] = zone;

            // add stock for asset if any
            if(company.has_asset){
                dojo.place( this.format_block( 'jstpl_generic_div', {
                    'id': companyShortName + '_asset',
                    'class': 'company-asset'
                } ), companyElement );

                this.createAssetTileStock(companyShortName + '_asset', this.gamedatas.all_capital_assets);
                this[companyShortName + '_asset'].onItemCreate = dojo.hitch( this, 'onAssetCreated' ); 
            }

            // add extra goods token zone
            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': companyShortName+'_extra_goods',
                'class': 'extra-goods'
            } ), companyElement );

            var zone = new ebg.zone();
            zone.create( this, companyShortName + '_extra_goods', 26, 26 );
            this[companyShortName + '_extra_goods'] = zone;
        },

        placeGoal: function(goal){
            var hash = this.hashString(goal.card_type);
            this.goals.addToStockWithId(hash, hash);

            var itemDivId = this.goals.getItemDivId(hash);
            var goalMaterial = this.gamedatas.goals[goal.card_type];

            var text = _( goalMaterial.tooltip );
            var html = this.getTooltipHtml(this.goalPositions, goal.card_type, text, 5, 118, 118); 
            this.addTooltip( itemDivId, html, "");
        },

        placeDemand: function(demand){
            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': demand.card_type, 
                'class': demand.card_type + " demand-card"
            } ), demand.card_location );

            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': demand.card_type + '_goods', 
                'class': ""
            } ), demand.card_type );

            var zone = new ebg.zone();
            zone.create( this, demand.card_type + '_goods', 13, 22 );
            zone.setPattern('custom');

            // get the custom pattern for the goods
            var patternFunction = null;
            var demandMaterial = this.gamedatas.demand[demand.card_type];
            switch(demandMaterial.demand){
                case 1:
                    patternFunction = function( i, control_width ) {
                        return {  x:24,y:32, w:13, h:22 };
                    };
                    break;
                case 2:
                    patternFunction = function( i, control_width ) {
                        if( i==0 )
                            return { x:24,y:17, w:13, h:22 };
                        else if(i==1)
                            return { x:24,y:47, w:13, h:22 };
                    };
                    break;
                case 3:
                    patternFunction = function( i, control_width ) {
                        if( i==0 )
                            return { x:24,y:8, w:13, h:22 };
                        else if(i==1)
                            return { x:24,y:31, w:13, h:22 };
                        else if(i==2)
                            return { x:24,y:54, w:13, h:22 };
                    };
                    break;
                case 4:
                    patternFunction = function( i, control_width ) {
                        if( i==0 )
                            return { x:13,y:16, w:13, h:22 };
                        else if(i==1)
                            return { x:35,y:16, w:13, h:22 };
                        else if(i==2)
                            return { x:13,y:45, w:13, h:22 };
                        else if(i==3)
                            return { x:35,y:45, w:13, h:22 };
                    };
                    break;
                case 5:
                    patternFunction = function( i, control_width ) {
                        if( i==0 )
                            return { x:13,y:10, w:13, h:22 };
                        else if(i==1)
                            return { x:35,y:10, w:13, h:22 };
                        else if(i==2)
                            return { x:24,y:32, w:13, h:22 };
                        else if(i==3)
                            return { x:13,y:53, w:13, h:22 };
                        else if(i==4)
                            return { x:35,y:53, w:13, h:22 };
                    };
                    break;
                case 6:
                    patternFunction = function( i, control_width ) {
                        if( i==0 )
                            return { x:13,y:8, w:13, h:22 };
                        else if(i==1)
                            return { x:35,y:8, w:13, h:22 };
                        else if(i==2)
                            return { x:13,y:32, w:13, h:22 };
                        else if(i==3)
                            return { x:35,y:32, w:13, h:22 };
                        else if(i==4)
                            return { x:13,y:54, w:13, h:22 };
                        else if(i==5)
                            return { x:35,y:54, w:13, h:22 };
                    };
                    break;
                case 7:
                    patternFunction = function( i, control_width ) {
                        if( i==0 )
                            return { x:24,y:2, w:13, h:22 };
                        else if(i==1)
                            return { x:13,y:20, w:13, h:22 };
                        else if(i==2)
                            return { x:35,y:20, w:13, h:22 };
                        else if(i==3)
                            return { x:13,y:39, w:13, h:22 };
                        else if(i==4)
                            return { x:35,y:39, w:13, h:22 };
                        else if(i==5)
                            return { x:13,y:58, w:13, h:22 };
                        else if(i==6)
                            return { x:35,y:58, w:13, h:22 };
                    };
                    break;
                case 8:
                    patternFunction = function( i, control_width ) {
                        if( i==0 )
                            return { x:13,y:5, w:13, h:22 };
                        else if(i==1)
                            return { x:35,y:5, w:13, h:22 };
                        else if(i==2)
                            return { x:13,y:23, w:13, h:22 };
                        else if(i==3)
                            return { x:35,y:23, w:13, h:22 };
                        else if(i==4)
                            return { x:13,y:40, w:13, h:22 };
                        else if(i==5)
                            return { x:35,y:40, w:13, h:22 };
                        else if(i==6)
                            return { x:13,y:58, w:13, h:22 };
                        else if(i==7)
                            return { x:35,y:58, w:13, h:22 };
                    };
                    break;
            }

            zone.itemIdToCoords = patternFunction;
            this[demand.card_type + '_goods'] = zone;

            dojo.connect( $(demand.card_type), 'onclick', this, 'onDemandClick' );
        },

        placeGood: function(good, from, fromZone){

            var location = good.card_location.split('_')[0]; // brunswick_goods or demand28
            if(from == 'supply'){
                // good produced -> create good
                // when goods are produced, all goods are returned from the server, so we avoid recreating them
                if($('good_' + good.card_id) == null){
                    dojo.place( this.format_block( 'jstpl_generic_div', {
                        'id': 'good_' + good.card_id, 
                        'class': 'good_token'
                    } ), 'main_board' );
                }

                this[location + '_goods'].placeInZone('good_' + good.card_id);

            } else if (from == 'company'){
                // good exists -> move it
                this[good.card_location].placeInZone('good_' + good.card_id);
                this[fromZone].removeFromZone('good_' + good.card_id);
            } else {
                // page refresh -> create good (in company or on demand tile)
                dojo.place( this.format_block( 'jstpl_generic_div', {
                    'id': 'good_' + good.card_id, 
                    'class': 'good_token'
                } ), location + '_goods' );

                this[location + '_goods'].placeInZone('good_' + good.card_id);
            }
        },

        placeCompany: function(company, from){
            var hash = this.hashString(company.short_name);
            this['companyArea'+company.owner_id].addToStockWithId(hash, company.short_name, from);
            this.placeShareValue(company.share_value_step, company.short_name, company.owner_id);

            var extraGoods = Number(company.extra_goods);
            for(var i = 0; i < extraGoods; i++){
                this.placeExtraGood(company.short_name, i, false);
            }
        },

        placeExtraGood: function(shortName, id, isNotif){
            var elementId = shortName + '_extra_good_' + id;
            dojo.place( this.format_block( 'jstpl_generic_div', {
                'id': elementId,
                'class': 'extra-goods-token'
            } ), shortName + '_extra_goods');
            if(isNotif)
                this.placeOnObject(elementId, 'main_board');
            this[shortName + '_extra_goods'].placeInZone(elementId);
        },

        placePartner: function(partner){
            var partnerId = partner.card_type; // worker_{playerId}_{workerNumber}

            // temporary fix since advertising space was renamed
            if(partner.card_location == "advertising")
                partner.card_location = "banana";
            
            if(!dojo.byId(partnerId)){
                var playerId = partnerId.split('_')[1];
                var playerColor = this.gamedatas.players[playerId].color;
                dojo.place( this.format_block( 'jstpl_token', {
                    'token_id': partnerId, 
                    'token_class': 'moveable-token partner partner-'+playerColor
                } ), 'overall_player_board_'+playerId );
            }

            this[partner.card_location + '_holder'].placeInZone(partnerId);
        },

        placeAutomationToken: function(automation, fromId){
            var split = automation.card_location.split('_');
            var companyShortName = split[0];
            var tokenId = 'automation_' + automation.card_id;
            var factoryNumber = split[3];
            if(split[1] == 'worker') {
                if(fromId){
                    // this is when a worker is automated and current player is not active (during notification)
                    var workerSpotId = this.getNextAvailableWorkerSpot(companyShortName + '_' + factoryNumber);
                    dojo.place(tokenId, workerSpotId);
                    this.placeOnObject(tokenId, fromId);
                    this.slideToObject(tokenId, workerSpotId).play();
                } else {
                    // this is an automated worker
                    var workerSpotId = this.getNextAvailableWorkerSpot(companyShortName + '_' + factoryNumber);
                    dojo.place( this.format_block( 'jstpl_token', {
                        'token_id': 'automation_' + automation.card_id,
                        'token_class': 'automation_token'
                    } ), workerSpotId );
                }
            }
            else{
                // fill from right to left and disregard location
                var automationSpotId = this.getNextAvailableAutomationSpot(companyShortName, factoryNumber);
                dojo.place( this.format_block( 'jstpl_token', {
                    'token_id': 'automation_' + automation.card_id,
                    'token_class': 'automation_token'
                } ), automationSpotId );
            }
        },

        placeWorkerInFactory: function(worker, from, fromId){
            var tokenId = 'worker_' + worker.card_id;
            
            // If worker is already in place, no need to move it
            // Ex.: when current player hires workers, they are moved before the call to the server
            var workerLocation = this.getWorkerLocation(tokenId); // workerLocation = swift_1 (shortName_factoryNumber)
            if(workerLocation == worker.card_location)
                return;

            // get the spot where the worker should be placed
            var workerSpotId = this.getNextAvailableWorkerSpot(worker.card_location);

            // if it doesn't exist create it
            // cases: page load, hired when job market is empty, taken from supply
            var element = $(tokenId);
            if(element == null) {
                dojo.place( this.format_block( 'jstpl_token', {
                    'token_id': tokenId,
                    'token_class': 'worker'
                } ), workerSpotId );
            }

            if(from == 'market'){
                dojo.place(tokenId, workerSpotId);
                this.placeOnObject( tokenId, 'job_market');
                this.slideToObject( tokenId, workerSpotId ).play();
                this.job_market.removeFromZone(tokenId);
            } else if (from == 'supply'){
                // some buildings allow hiring workers from the supply
                this.placeOnObject(tokenId, 'main_board');
                this.slideToObject(tokenId, workerSpotId).play();
            } else if (from == 'factory'){
                // happens when a worker is relocated (it already exists)
                dojo.place(tokenId, workerSpotId);
                this.placeOnObject(tokenId, fromId);
                this.slideToObject(tokenId, workerSpotId).play();
            }
        },

        // factoryId -> spalding_2
        getNextAvailableWorkerSpot: function(factoryId){
            var split = factoryId.split('_');
            var companyShortName = split[0];
            var factoryNumber = split[1];

            var factorySelector = '#'+companyShortName + '_factory_' + factoryNumber;
            var availableWorkerSpots = dojo.query(factorySelector + '>.worker-holder:empty');
            return availableWorkerSpots[0].id;
        },

        getNextAvailableAutomationSpot: function(companyShortName, factoryNumber){
            var factorySelector = '#'+companyShortName + '_factory_' + factoryNumber;
            var availableFactorySpots = dojo.query(factorySelector + '>.automation_holder:empty');
            return availableFactorySpots[availableFactorySpots.length - 1].id;
        },

        placeAvailableCompany: function(shortName){
            var hash = this.hashString(shortName);
            this.available_companies.addToStockWithId(hash, shortName);
        },

        createCompaniesStock: function(allCompanies, playerId){
            var newStock = new ebg.stock();
            var id;
            var propertyName;
            if(playerId != null){
                propertyName = 'companyArea'+playerId;
                id = 'company_area_'+playerId;
            } else {
                propertyName = 'available_companies';
                id = 'available_companies';
                newStock.centerItems = true;
            }

            newStock.onItemCreate = dojo.hitch( this, 'setupCompany' ); 

            newStock.create( this, $(id), 350, 229);
            this[propertyName] = newStock;

            // Specify that there are 6 companies per row
            newStock.image_items_per_row = 6;
            newStock.setSelectionMode(0);
            
            var i = 0;
            for(var property in allCompanies){
                var company = allCompanies[property];
                var hash = this.hashString(company.short_name);

                var imagePosition = this.companyNameToImagePosition[company.short_name];
                newStock.addItemType( hash, i, g_gamethemeurl+'img/all_companies_small_final.png', imagePosition );

                i++;
            }
        },

        createShareStock: function(allCompanies, location){
            var newStock = new ebg.stock();
            newStock.create( this, $(location), 109, 73);
            this[location] = newStock;
            newStock.image_items_per_row = 3;
            newStock.apparenceBorderWidth = "3px";
            newStock.setSelectionMode(0);

            var i = 0;
            for(var property in allCompanies){
                var company = allCompanies[property];
                var hashDirector = this.hashString(company.short_name+"_director");
                var hashPreferred = this.hashString(company.short_name+"_preferred");
                var hashCommon = this.hashString(company.short_name+"_common");

                var imagePosition = this.companyNameToImagePosition[company.short_name];
                newStock.addItemType( hashDirector, i, g_gamethemeurl+'img/stocks_small.png', 3*imagePosition );
                newStock.addItemType( hashPreferred, i, g_gamethemeurl+'img/stocks_small.png', 3*imagePosition+1 );
                newStock.addItemType( hashCommon, i, g_gamethemeurl+'img/stocks_small.png', 3*imagePosition+2 );

                i++;
            }

            newStock.onItemCreate = dojo.hitch( this, 'setupNewStock' ); 
        },

        hashString: function(value){
            var hash = 0, i, chr;
            for (i = 0; i < value.length; i++) {
                chr   = value.charCodeAt(i);
                hash  = ((hash << 5) - hash) + chr;
                hash |= 0; // Convert to 32bit integer
            }
            return (hash >>> 0);
        },

        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

        produceGoods: function(event){
            if(!this.checkAction('produceGoods'))
                return;

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/produceGoods.html", { lock: true }, this, function( result ) {} );
        },

        onCompanyClicked: function(event){
            //dojo.stopEvent(event);

            var companyTargetId = event.currentTarget.id; // company_area_2319930_item_swift
            if(!dojo.hasClass(companyTargetId, "active"))
                return;
            
            var split = companyTargetId.split('_');
            var companyShortName = split[4];

            var state = this.gamedatas.gamestate.name;
            switch(state){
                case 'client_chooseCompanyToPay':
                    this.clientStateArgs.companyShortName = companyShortName;
                    this.onConfirmAction();
                    break;
                case 'client_tradeChooseCompany':
                    if(!this.checkAction('tradeResources'))
                        return;

                    this.createResourceOptions(companyShortName);
                    
                    break;
                case 'client_actionChooseCompany':
                    if(!this.checkAction('buildingAction'))
                        return;

                    this.executeActionForCompany(companyShortName);
                    break;
            }
        },

        onFactoryClicked: function(event){

            var state = this.gamedatas.gamestate.name;
            var factoryTargetId = event.currentTarget.id;
            var split = factoryTargetId.split('_'); // brunswick_factory_2
            var companyShortName = split[0];
            var factoryNumber = split[2];

            // This happens when a factory is clicked during the produce goods phase (operation phase)
            if(state == 'playerProduceGoodsPhase'){
                this.produceGoods();
                return;
            }

            // this is a special state that only happens when workers are automated
            // once we know where to put the automated worker, we can confirm the action
            if(state == 'client_chooseFactoryRelocate'){
                this.clientStateArgs.actionArgs.relocateFactoryNumber = factoryNumber;
                this.onConfirmAction();
                return;
            }

            if(state == 'client_chooseFactoryRelocateDoubleAutomation'){
                if(this.clientStateArgs.secondAutomationSkipped){
                    // if the second automation was skipped, we can confirm action
                    this.clientStateArgs.actionArgs.relocateFactoryNumber = factoryNumber;
                    this.onConfirmAction();
                } else if (this.clientStateArgs.actionArgs.firstFactoryRelocate) {
                    // if the first worker has been relocated, and we are here, both workers have been relocated => confirm action
                    this.clientStateArgs.actionArgs.secondFactoryRelocate = factoryNumber;
                    this.onConfirmAction();
                } else {
                    // move 1st automated worker temporarily
                    var canMove = this.moveAutomatedWorker(companyShortName, this.clientStateArgs.actionArgs.firstFactoryNumber, factoryNumber);
                    if(!canMove) {
                        this.showMessage( _("This factory doesn't have any more space for workers"), 'info' );
                        return;
                    }
                        
                    this.clientStateArgs.actionArgs.firstFactoryRelocate = factoryNumber;

                    if(!this.clientStateArgs.shouldRelocateSecondWorker){
                        // if the second worker cannot be relocated
                        this.clientStateArgs.actionArgs.secondFactoryRelocate = 0;
                        this.onConfirmAction();
                    } else {
                        // else go to 2nd relocation
                        this.setClientState("client_chooseFactoryRelocateDoubleAutomation", {
                            descriptionmyturn : _('Choose a factory in which to relocate the second automated worker')
                        });
                    }
                }
                return;
            }

            // this is a special state that happens when a worker is automated because of an asset tile immediate bonus
            if(state == 'client_chooseFactoryRelocateBonus'){
                this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/automateFactory.html", { lock: true,
                    companyShortName: companyShortName,
                    factoryNumber: this.clientStateArgs.factoryNumber,
                    relocateNumber: factoryNumber,
                    workerId: this.clientStateArgs.workerId
                }, this, function( result ) {} );
                return;
            }

            // this is a special state that happens when a player gains a free worker from an asset tile bonus
            if(state == 'playerAssetWorkerBonus'){
                this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/hireWorker.html", { lock: true,
                    companyShortName: companyShortName,
                    factoryNumber: factoryNumber
                }, this, function( result ) {} );
                return;
            }

            if(!dojo.hasClass(factoryTargetId, "active"))
                return;

            //dojo.stopEvent(event);

            //if(!this.checkAction('buildingAction'))
            //    return;

            switch(state){
                case 'client_placeHiredWorkers':

                    cost = this.getCostOfWorkers(1);
                    var emptyWorkerSpots = this.getEmptyWorkerSpotsInFactory(companyShortName, factoryNumber);
                    if(emptyWorkerSpots == 0){
                        this.showMessage( _("This factory doesn't have any more space for workers"), 'info' );
                        break;
                    }

                    var workerId = this.moveWorkerFromMarketToFactory(companyShortName, factoryNumber);

                    this.clientStateArgs.numberOfWorkersToBuy++;
                    this.clientStateArgs.selectedFactories.push({ workerId: workerId, factoryNumber: factoryNumber });
                    this.clientStateArgs.totalCost += cost;
                    this.setClientState("client_placeHiredWorkers", {
                        descriptionmyturn : dojo.string.substitute(_('Hire ${numberWorkers} worker for $${cost}. Select another factory to hire 1 more.'),{
                            cost: this.clientStateArgs.totalCost,
                            numberWorkers: this.clientStateArgs.numberOfWorkersToBuy
                        })
                    });
                    break;
                case 'client_appealBonusChooseFactory':
                    if(this.clientStateArgs.bonus == 'automation')
                    {
                        if(this.clientStateArgs.factoryNumber) {
                            this.clientStateArgs.relocateFactoryNumber = factoryNumber;
                            this.gainAppealBonus();
                        } else {
                            if(!this.canAutomateFactory(companyShortName, factoryNumber)){
                                this.showMessage( _("This factory can't be automated"), 'info' );
                                return;
                            }

                            this.clientStateArgs.factoryNumber = factoryNumber;
                            var result = this.automateWorker(companyShortName, factoryNumber);
                            this.clientStateArgs.workerId = result.workerId;
                            if(result.relocate){
                                this.setClientState("client_appealBonusChooseFactory", {
                                    descriptionmyturn : _('Choose a factory in which to relocate the automated worker')
                                });
                            } else {
                                this.gainAppealBonus();
                            }
                        }
                    } else {
                        this.clientStateArgs.factoryNumber = factoryNumber;
                        this.gainAppealBonus();
                    }
                    break;
                case 'client_actionChooseFactory':
                case 'client_actionChooseFactorySkip':
                case 'client_actionChooseFactorySkipToRelocate':
                case 'client_actionChooseFactorySkipWorker':
                    this.executeActionForCompany(companyShortName, factoryNumber);
                    break;
                case 'playerAssetAutomationBonus':
                    if(!this.canAutomateFactory(companyShortName, factoryNumber)){
                        this.showMessage( _("This factory can't be automated"), 'info' );
                        return;
                    }

                    var result = this.automateWorker(companyShortName, factoryNumber);
                    if(result.relocate){
                        this.clientStateArgs.companyShortName = companyShortName;
                        this.clientStateArgs.factoryNumber = factoryNumber;
                        this.clientStateArgs.workerId = result.workerId;
                        this.setClientState("client_chooseFactoryRelocateBonus", {
                            descriptionmyturn : _('Choose a factory in which to relocate the automated worker')
                        });
                    } else {
                        this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/automateFactory.html", { lock: true,
                            companyShortName: companyShortName,
                            factoryNumber: factoryNumber,
                            relocateNumber: null,
                            workerId: result.workerId
                        }, this, function( result ) {} );
                    }
                    break;
            }
        },

        executeActionForCompany: function(companyShortName, factoryNumber){
            
            var actionOk = true;
            var message = "";

            var buildingMaterial = this.gamedatas.all_buildings[this.clientStateArgs.buildingAction] ||
                this.gamedatas.general_action_spaces[this.clientStateArgs.buildingAction];
            if(!buildingMaterial){
                this.showMessage( _("Could not find associated building"), 'error' );
                return;
            }

            var cost = buildingMaterial.cost;
            var buildingAction = this.clientStateArgs.buildingAction;
            // make checks specific to action and get cost of action
            switch(buildingAction){
                case "job_market_worker":
                    // check if there are empty worker spots
                    cost = this.getCostOfWorkers(1);
                    var emptyWorkerSpots = this.getEmptyWorkerSpotsInFactory(companyShortName, factoryNumber);
                    if(emptyWorkerSpots == 0){
                        actionOk = false;
                        message = _("This factory doesn't have any more space for workers");
                    }
                    break;
                case "building26":
                    var emptyWorkerSpots = this.getEmptyWorkerSpotsInFactory(companyShortName, factoryNumber);
                    if(emptyWorkerSpots == 0){
                        actionOk = false;
                        message = _("This factory doesn't have any more space for workers");
                    }
                    break;
                case "building43":
                    var workerFactoryNumber = this.clientStateArgs.actionArgs.workerFactoryNumber;
                    if(typeof workerFactoryNumber !== 'undefined'){
                        if(this.doesFactoryEmployManager(companyShortName, factoryNumber)){
                            actionOk = false;
                            message = _("This factory already employs a manager");
                        }
                    } else {
                        var emptyWorkerSpots = this.getEmptyWorkerSpotsInFactory(companyShortName, factoryNumber);
                        if(emptyWorkerSpots == 0){
                            actionOk = false;
                            message = _("This factory doesn't have any more space for workers");
                        }
                    }
                    break;
                case "building44":
                    var workerFactoryNumber = this.clientStateArgs.actionArgs.workerFactoryNumber
                    if(typeof workerFactoryNumber === 'undefined'){
                        var emptyWorkerSpots = this.getEmptyWorkerSpotsInFactory(companyShortName, factoryNumber);
                        if(emptyWorkerSpots == 0){
                            actionOk = false;
                            message = _("This factory doesn't have any more space for workers");
                        }
                    }
                    break;
                case "building1":
                    cost -= 10; // discount
                    break;
                case "building19":
                    cost -= 20; // discount
                    break;
                case "building40":
                    cost -= 30; // discount
                    break;
                case "hire_manager":
                case "building11":
                case "building14":
                case "building23": // double manager
                case "building35":
                    if(this.doesFactoryEmployManager(companyShortName, factoryNumber)){
                        actionOk = false;
                        message = _("This factory already employs a manager");
                    }
                    break;
                case "building6":
                case "building24":
                case "building21":
                case "building42":
                    if(!this.canAutomateFactory(companyShortName, factoryNumber)){
                        actionOk = false;
                        message = _("This factory can't be automated");
                    }
                    break;
            }

            var paymentMethod = buildingMaterial.payment;
            if(paymentMethod == 'companytobank' || 
                paymentMethod == 'companytoplayer' ||
                paymentMethod == 'companytoshareholders')
            {
                if(!this.checkCompanyMoney(companyShortName, cost)){
                    this.showMessage( _("Not enough money to pay for this action"), 'info' );
                    return;
                }
            }

            if(!actionOk){
                this.showMessage( message, 'info' );
                return;
            }

            // save client state args
            this.clientStateArgs.factoryNumber = factoryNumber;
            this.clientStateArgs.companyShortName = companyShortName;

            // execute action specific stuff
            switch(buildingAction){
                case 'job_market_worker':
                    var workerId = this.moveWorkerFromMarketToFactory(companyShortName, factoryNumber);
                    this.clientStateArgs.numberOfWorkersToBuy = 1;
                    var selectedFactories = [];
                    selectedFactories.push({ 'workerId': workerId, 'factoryNumber': factoryNumber });
                    this.clientStateArgs.selectedFactories = selectedFactories;
                    this.clientStateArgs.totalCost = cost;
                    this.setClientState("client_placeHiredWorkers", {
                        descriptionmyturn : dojo.string.substitute(_('Hire 1 worker for $${cost}. Select another factory to hire 1 more.'),{
                            cost: cost,
                        })
                    });
                    break;
                case "capital_investment":
                case "building1":
                case "building19":
                case "building40":
                    this.setClientState("client_chooseAsset", {
                        descriptionmyturn : _('Choose an asset to buy')
                    });
                    break;
                case "building15":
                case "building3":
                case "building5":
                case "building7":
                case "building13":
                case "building17":
                case "building34":
                case "building36":
                case "building37":
                case "building38":
                    var material = this.gamedatas.all_buildings[buildingAction];
                    var result = this.gainResources(material.resources);
                    if(result.gotEverything) {
                        this.onConfirmAction();
                    } else {
                        this.setClientState("client_confirmGainLessResources", {
                            descriptionmyturn : dojo.string.substitute(_('Some resources are not available. Confirm gain ${items}'), {
                                items: result.optionsString })
                        });
                    }
                    break;
                case 'building6':
                case "building24":
                    var result = this.automateWorker(companyShortName, factoryNumber);
                    this.clientStateArgs.actionArgs.workerId = result.workerId;
                    if(result.relocate){
                        this.setClientState("client_chooseFactoryRelocate", {
                            descriptionmyturn : _('Choose a factory in which to relocate the automated worker')
                        });
                    } else {
                        this.clientStateArgs.actionArgs.relocateFactoryNumber = 0;
                        this.onConfirmAction();
                    }
                    break;
                case "building21": // double automation
                case "building42":
                    if(this.clientStateArgs.actionArgs.firstFactoryNumber){

                        // this means that the first factory to automate has been selected
                        this.clientStateArgs.actionArgs.secondFactoryNumber = factoryNumber;

                        var result = this.automateWorker(companyShortName, factoryNumber);
                        this.clientStateArgs.actionArgs.workerId2 = result.workerId;

                        if(!this.clientStateArgs.shouldRelocateFirstWorker){
                            // if there is no spot to put the first worker, there is no spot to put the first either
                            this.clientStateArgs.actionArgs.firstFactoryRelocate = 0;
                            this.clientStateArgs.actionArgs.secondFactoryRelocate = 0;
                            this.onConfirmAction();
                        } else {
                            this.clientStateArgs.shouldRelocateSecondWorker = result.relocate;

                            this.setClientState("client_chooseFactoryRelocateDoubleAutomation", {
                                descriptionmyturn : _('Choose a factory in which to relocate the first automated worker')
                            });
                        }
                    } else {

                        // this is the first factory being selected for automation
                        this.clientStateArgs.actionArgs.firstFactoryNumber = factoryNumber;
                        var result = this.automateWorker(companyShortName, factoryNumber);
                        this.clientStateArgs.actionArgs.workerId1 = result.workerId;
                        this.clientStateArgs.shouldRelocateFirstWorker = result.relocate;

                        this.setClientState("client_actionChooseFactorySkipToRelocate", {
                            descriptionmyturn : dojo.string.substitute(_('Choose a second factory to automate'),{
                            })
                        });
                    }
                    break;
                case 'building8': // different resources
                case 'building18':
                    this.setClientState("client_confirmGainDifferentResources", {
                        descriptionmyturn : _('Confirm which two different resources to gain')
                    });
                    break;
                case 'building12': // same resources
                case 'building20':
                    this.setClientState("client_confirmGainSameResources", {
                        descriptionmyturn : _('Confirm which two same resources to gain')
                    });
                    break;
                case "hire_salesperson": // salesperson
                case "building2":
                case "building22":
                    if(this.getSalespersonEmptySpots(companyShortName) == 0){
                        this.setClientState("client_confirmGainLessSalespeople", {
                            descriptionmyturn : _('Confirm gain 0 salesperson')
                        });
                    } else {
                        this.onConfirmAction();
                    }
                    break;
                case "building25": // double salesperson
                case "building33":
                    var emptySpots = this.getSalespersonEmptySpots(companyShortName)
                    if(emptySpots == 0){
                        this.setClientState("client_confirmGainLessSalespeople", {
                            descriptionmyturn : _('Confirm gain 0 salesperson')
                        });
                    } else if (emptySpots == 1){
                        this.setClientState("client_confirmGainLessSalespeople", {
                            descriptionmyturn : _('Confirm gain 1 salesperson')
                        });
                    } else {
                        this.onConfirmAction();
                    }
                    break;
                case "building23": // double manager
                case "building35":
                    if(this.clientStateArgs.actionArgs.firstFactory){
                        this.clientStateArgs.actionArgs.secondFactory = factoryNumber;
                        this.onConfirmAction();
                    } else {
                        this.clientStateArgs.actionArgs.firstFactory = factoryNumber;
                        this.createTempManager(companyShortName, factoryNumber);
                        this.chooseFactorySkip();
                    }
                    break;
                case "building26": // double worker
                    if(this.clientStateArgs.actionArgs.firstWorkerLocation){
                        this.clientStateArgs.actionArgs.secondWorkerLocation = factoryNumber;
                        this.onConfirmAction();
                    } else {
                        this.clientStateArgs.actionArgs.firstWorkerLocation = factoryNumber;
                        this.createTempWorker(companyShortName, factoryNumber);
                        this.setClientState("client_actionChooseFactorySkip", {
                            descriptionmyturn : dojo.string.substitute(_('Choose a factory for the second worker'),{
                            })
                        });
                    }
                    break;
                case "building43": // worker + manager
                    var workerFactoryNumber = this.clientStateArgs.actionArgs.workerFactoryNumber;
                    if(typeof workerFactoryNumber !== 'undefined'){
                        this.clientStateArgs.actionArgs.managerFactoryNumber = factoryNumber;
                        this.onConfirmAction();
                    } else {
                        this.clientStateArgs.actionArgs.workerFactoryNumber = factoryNumber;
                        this.createTempWorker(companyShortName, factoryNumber);
                        this.chooseFactorySkip(_("Choose a factory to put hired manager"));
                    }
                    break;
                case "building44": // worker + salesperson
                    var workerFactoryNumber = this.clientStateArgs.actionArgs.workerFactoryNumber
                    if(typeof workerFactoryNumber === 'undefined'){
                        this.clientStateArgs.actionArgs.workerFactoryNumber = factoryNumber;
                        this.createTempWorker(companyShortName, factoryNumber);
                        if(this.getSalespersonEmptySpots(companyShortName) == 0){
                            this.setClientState("client_confirmGainLessSalespeople", {
                                descriptionmyturn : _('Confirm gain 0 salesperson')
                            });
                        } else {
                            this.onConfirmAction();
                        }
                    } else {
                        // happens when hire worker is skipped
                        this.onConfirmAction();
                    }
                    break;
                case "banana": // advertising for add blockers
                    this.setClientState("client_confirmFirstPlayer", {
                        descriptionmyturn : _('You may become the starting player')
                    });
                    break;
                default:
                    this.onConfirmAction();
                    break;
            }
        },

        checkCompanyMoney: function(companyShortName, cost){
            var counterName = 'money_'+companyShortName;
            var companyTreasury = this.gamedatas.counters[counterName]['counter_value'];
            if(companyTreasury >= cost)
                return true;
            return false;
        },

        getCostOfWorkers: function(numberOfWorkers){
            var workersInMarket = this.job_market.getAllItems().length;
            var totalCost = 0;
            var costConvert = {0: 50, 1: 40, 2: 40, 3: 40, 4: 40, 5: 30, 6: 30, 7: 30, 8: 30, 9: 20, 10: 20, 11: 20, 12: 20};
            for(var i = 0; i<numberOfWorkers; i++){
                var cost = costConvert[workersInMarket];
                totalCost += cost;
                if(workersInMarket > 0)
                    workersInMarket--;
            }
            return totalCost;
        },

        getEmptyWorkerSpotsInFactory: function(companyShortName, factoryNumber){
            var company = this.gamedatas.all_companies[companyShortName];
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var workerHolderChilds = dojo.query(factorySelector + '>.worker-holder>');
            var numberOfWorkers = company.factories[factoryNumber].workers;
            return numberOfWorkers - workerHolderChilds.length;
        },

        getEmptyWorkerSpotsInCompany: function(companyShortName){
            var company = this.gamedatas.all_companies[companyShortName];
            var companySelector = '#company_'+companyShortName; //#company_brunswick
            var workerHolderChilds = dojo.query(companySelector + ' .worker-holder>');
            var numberOfWorkers = 0;
            var factories = company.factories;
            for(var index in factories){
                var factory = factories[index];
                numberOfWorkers += factory.workers;
            }
            return numberOfWorkers - workerHolderChilds.length;
        },

        onDemandSpaceClicked: function(event){
            var demandCards = dojo.query('#' + event.currentTarget.id + '>.demand-card');
            if(demandCards.length > 0)
                return;
            
            dojo.stopEvent(event);

            if(!this.checkAction('distributeGoods'))
                return;
            
            if(!dojo.hasClass(event.currentTarget, 'active'))
                return;

            // check if there are goods to distribute
            var shortName = this.gamedatas.gamestate.args.company_short_name;
            var goods = this[shortName + '_goods'].getAllItems();

            if(goods.length == 0){
                this.showMessage(_('No goods to distribute'), 'info');
                return;
            }

            var targetId = event.currentTarget.id;
            var identifier = dojo.attr(event.currentTarget, "identifier")
            if(!identifier){
                this.showMessage(_('Can\'t distribute goods on this space'), 'info');
                return;
            }

            var demandId = Number(identifier);
            var demandZoneName = "demand" + demandId + "_goods";
            var is20BonusSpot = targetId.indexOf('_20') !== -1;

            // this space holds 6 goods
            // other space holds infinite goods
            if(is20BonusSpot){
                var demandGoods = this[demandZoneName].getAllItems();
                if(demandGoods.length == 6)
                {
                    this.showMessage(_('Cannot distribute anymore goods on this demand space'), 'info');
                    return;
                }
            }

            // place good on demand tile
            var good = goods[0];
            var card_id = good.split('_')[1]; // good_159
            this.placeGood({
                card_location: demandZoneName,
                card_id: card_id
            }, 'company', shortName + '_goods')

            var income = this.clientStateArgs.income;
            // if there is one spot left => add bonus
            if(is20BonusSpot && demandGoods.length == 5){
                income += 20;
            }

            // get salespeople and price of goods
            var numberOfSalespeople = dojo.query('#company_' + shortName + ' .salesperson').length;
            var pricePerGood = Number(this.gamedatas.all_companies[shortName].salesperson[numberOfSalespeople]);
            if(is20BonusSpot){
                income += pricePerGood;
            } else {
                income += pricePerGood/2;
            }

            // save the demand tile id for when the player confirms
            // save also good ids if a player cancels
            this.clientStateArgs.income = income;
            this.clientStateArgs.goods.push({'demandId': demandId, 'goodId': card_id, 'zoneName': demandZoneName});
            var count = this.clientStateArgs.goods.length;

            // calculate operating income and switch to client state that displays it
            this.setClientState("client_playerTurnConfirmDistributeGoods", {
                descriptionmyturn : dojo.string.substitute(_('Distribute ${count} goods to receive $${income}'),{
                    count: count,
                    income: income
                })
            });
        },

        onDemandClick: function(event){
            dojo.stopEvent(event);

            if(!this.checkAction('distributeGoods'))
                return;
            
            if(!dojo.hasClass(event.currentTarget.parentNode, 'active'))
                return;

            // check if there are goods to distribute
            var shortName = this.gamedatas.gamestate.args.company_short_name;
            var goods = this[shortName + '_goods'].getAllItems();

            if(goods.length == 0){
                this.showMessage(_('No goods to distribute'), 'info');
                return;
            }

            var targetId = event.currentTarget.id;

            // make sure there is still space on demand tile
            var demandMaterial = this.gamedatas.demand[targetId];
            var demandGoods = this[targetId + '_goods'].getAllItems();
            if(demandMaterial.demand <= demandGoods.length)
            {
                this.showMessage(_('Cannot distribute anymore goods on this demand tile'), 'info');
                return;
            }

            // place good on demand tile
            var good = goods[0];
            var card_id = good.split('_')[1]; // good_159
            this.placeGood({
                card_location: targetId + '_goods',
                card_id: card_id
            }, 'company', shortName + '_goods')

            // if there is one spot left => add bonus
            var income = this.clientStateArgs.income;
            if(demandMaterial.demand - 1 == demandGoods.length)
            {
                var parentId = event.currentTarget.parentNode.id;
                var split = parentId.split('_'); // dry_goods_50 or food_and_dairy_50...
                var bonus = Number(split[split.length - 1]);
                income += bonus;
            }

            // get salespeople and price of goods
            var numberOfSalespeople = dojo.query('#company_' + shortName + ' .salesperson').length;
            var pricePerGood = Number(this.gamedatas.all_companies[shortName].salesperson[numberOfSalespeople]);
            income += pricePerGood;

            // save the demand tile id for when the player confirms
            // save also good ids if a player cancels
            var regex = /\d+$/;
            var demandIdentifier = Number(regex.exec(targetId)[0]);
            this.clientStateArgs.income = income;
            this.clientStateArgs.goods.push({'demandId': demandIdentifier, 'goodId': card_id, 'zoneName': targetId + '_goods'});
            var count = this.clientStateArgs.goods.length;

            // calculate operating income and switch to client state that displays it
            this.setClientState("client_playerTurnConfirmDistributeGoods", {
                descriptionmyturn : dojo.string.substitute(_('Distribute ${count} goods to receive $${income}'),{
                    count: count,
                    income: income
                })
            });
        },

        onAssetClicked: function(event){
            if(!this.checkAction('useAsset'))
                return;

            dojo.stopEvent(event);

            var targetId = event.currentTarget.id;
            if(!dojo.hasClass(targetId, "active"))
                return;

            if(dojo.hasClass(targetId, "exhausted"))
                return;
            
            var assetName = dojo.attr(event.currentTarget, "asset-name");
            var companyShortName = targetId.split('_')[0];
            
            switch(assetName)
            {
                case 'color_catalog':
                case 'brand_recognition':
                case 'catalogue_empire':
                case 'mail_order_catalogue':
                case 'popular_partners':
                case 'backroom_deals':
                case 'brilliant_marketing':
                    this.confirmAssetUse(assetName);
                    break;
                case 'union_stockyards':
                case 'michigan_lumber':
                case 'pennsylvania_coal':
                case 'cincinnati_steel':
                    // check enough money
                    if(!this.checkCompanyMoney(companyShortName, 10)){
                        this.showMessage( _("Not enough money to use asset"), 'info' );
                        return;
                    }
                case 'refrigeration':
                case 'foundry':
                case 'workshop':
                case 'abattoir':
                    var material = this.gamedatas.all_capital_assets[assetName];
                    var result = this.gainResources(material.resources);
                    if(result.gotEverything) {
                        this.confirmAssetUse(assetName);
                    } else {
                        this.clientStateArgs.assetName = assetName;
                        this.setClientState("client_confirmGainLessResourcesAsset", {
                            descriptionmyturn : dojo.string.substitute(_('Some resources are not available. Confirm gain ${items}'), {
                                items: result.optionsString })
                        });
                    }
                    break;
                case 'price_protection':
                    // show message that this is triggered automatically
                    this.showMessage( _("This asset is triggered automatically"), 'info' );
                    break;
            }
        },

        confirmAssetUse: function(assetName){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/useAsset.html", { lock: true,
                assetName: assetName,
            }, this, function( result ) {} );
        },

        onAppealBonusClicked: function(event){
            dojo.stopEvent(event);

            if(!this.checkAction('gainAppealBonus'))
                return;

            var target = event.currentTarget;
            if(!dojo.hasClass(target.id, "active"))
                return;

            var bonus = dojo.attr(target, 'bonus');

            switch(bonus)
            {
                case 'worker':
                case 'automation':
                    this.clientStateArgs.bonus = bonus;
                    this.setClientState("client_appealBonusChooseFactory", {
                        descriptionmyturn : _('Choose a factory for the appeal bonus')
                    });
                    break;
                case 'salesperson':
                    var companyShortName = this.gamedatas.gamestate.args.company_short_name;
                    if(this.getSalespersonEmptySpots(companyShortName) == 0){
                        this.setClientState("client_confirmGainLessSalespeopleAppealBonus", {
                            descriptionmyturn : _('Confirm gain 0 salesperson')
                        });
                    } else {
                        this.gainAppealBonus();
                    }
                    break;
                case 'partner':
                case 'good':
                case 'bump':
                    this.gainAppealBonus();
                    break;
            }
        },

        onWorkerSpotClicked: function(event){
            dojo.stopEvent(event);

            if(!this.checkAction('buildingAction'))
                return;
        
            var state = this.gamedatas.gamestate.name;
            if(state != 'playerActionPhase')
                return;

            var targetId = event.currentTarget.id;
            if(!dojo.hasClass(targetId, "active"))
                return;

            var playerId = this.getActivePlayerId();
            var counterName = 'partner_current_'+playerId;
            var workerNumber = this.gamedatas.counters[counterName]['counter_value'];
            var totalWorkers = this.gamedatas.counters["partner_"+playerId]['counter_value'];
            
            if(workerNumber == 0)
                return;
            
            var partnerId = 'worker_'+playerId+'_'+(totalWorkers - workerNumber + 1);

            // check if spot can be used multiple times
            var playerLimit = true;
            if(!this.gamedatas.all_buildings[targetId]){
                var buildingMaterial = this.gamedatas.general_action_spaces[targetId];
                playerLimit = buildingMaterial.player_limit;
            }

            if(playerLimit){
                var workers = this[targetId + '_holder'].getItemNumber();
                if(workers > 0){
                    this.showMessage( _("This action space can't accomodate more than one partner"), 'info' );
                    return;
                }
            }
            
            // create worker
            this.placePartner({
                card_type: partnerId,
                card_location: targetId
            });

            // update meeple counter for current player (not reflected on the server yet)
            dojo.byId(counterName).textContent = workerNumber - 1;

            // remove highlight from worker spots
            dojo.query('.worker_spot').removeClass('active');

            this.clientStateArgs.buildingAction = targetId;
            this.clientStateArgs.partnerId = partnerId;
            
            switch(targetId){
                case "job_market_worker":
                    this.chooseFactory();
                    break;
                case "building6": // automation
                case "building24": // automation
                case "building21": // double automation
                case "building42": // double automation
                    this.clientStateArgs.undoMoves = [];
                    this.chooseFactorySkip();
                    break;
                case "hire_manager":
                case "building11": // manager
                case "building14": // manager
                case "building23": // doubler manager
                case "building26": // doubler worker
                case "building35": // doubler manager
                    this.chooseFactorySkip();
                    break;
                case "building44": // worker + salesperson
                case "building43": // worker + manager
                    this.setClientState("client_actionChooseFactorySkipWorker", {
                        descriptionmyturn : _("Choose a factory to put hired worker")
                    });
                    break;
                default:
                    this.chooseCompany();
                    break;
            }
        },

        onUndo: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/undo.html", { lock: true }, this, function( result ) {} );
        },

        onFinish: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/finish.html", { lock: true }, this, function( result ) {} );
        },

        onConfirmPayDividends: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/payDividends.html", { lock: true }, this, function( result ) {} );
        },

        onWithhold: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/withhold.html", { lock: true }, this, function( result ) {} );
        },

        onCancelSelectBuildings: function(event){
            dojo.stopEvent(event);
            dojo.query('.building_to_play').removeClass('building_to_play');
            dojo.query('.building_to_discard').removeClass('building_to_discard');
            this.restoreServerGameState();
        },

        // called when using a building which allows you to gain 2 different or 2 same resources
        onChooseResources: function(event){
            var childNodes = event.currentTarget.childNodes;
            for(var i = 0; i < childNodes.length; i++){
                var nodeId = childNodes[i].id;
                var resourceId = nodeId.split('_')[1];
                this.clientStateArgs.actionArgs[nodeId] = resourceId;
            }

            this.onConfirmAction();
        },

        onChooseResourceBonus: function(event){
            var targetId = event.currentTarget.id;
            var type = targetId.split('_')[1];

            var options = this.clientStateArgs.haymarketOptions;

            var selectedHaymarketResource = null;
            for(var i = 0; i < options.length; i++){
                if(options[i].type == type){
                    selectedHaymarketResource = options[i];
                    break;
                }
            }

            var selectedResources = this.clientStateArgs.selectedResources;
            selectedResources.push(selectedHaymarketResource);
            if(selectedResources.length == this.clientStateArgs.resourcesToGet){
                var args = [];
                for(var i = 0; i < selectedResources.length; i++){
                    args.push(selectedResources[i].id);
                }
                this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/managerBonusGainResources.html", { lock: true,
                    resourceIds: args.join(',')
                }, this, function( result ) {} );
            } else {
                this.clientStateArgs.haymarketOptions = this.getDifferentHaymarketOptions(selectedResources);
                this.setClientState("client_managerBonusSelectResource", {
                    descriptionmyturn : _('Choose another resource')
                });
            }
        },

        onChooseHaymarketResource: function(event){
            var targetId = event.currentTarget.id;
            var type = targetId.split('_')[1];

            var options = this.clientStateArgs.haymarketOptions;

            var selectedHaymarketResource = null;
            for(var i = 0; i < options.length; i++){
                if(options[i].type == type){
                    selectedHaymarketResource = options[i];
                    break;
                }
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/tradeResources.html", { lock: true,
                haymarketResourceId: selectedHaymarketResource.id,
                companyResourceId1: this.clientStateArgs.selectedCompanyResources.ids[0],
                companyResourceId2: this.clientStateArgs.selectedCompanyResources.ids[1]
            }, this, function( result ) {
                this.restoreServerGameState();
            } );
        },

        onChooseCompanyResources: function(event){

            var targetId = event.currentTarget.id;
            var type = targetId.split('_')[1];

            var options = this.clientStateArgs.options;
            for(var i = 0; i < options.length; i++){
                if(options[i].type == type){
                    this.clientStateArgs.selectedCompanyResources = options[i];
                    break;
                }
            }
            
            var haymarketOptions = this.getHaymarketResourceOptions();

            this.clientStateArgs.haymarketOptions = haymarketOptions;
            this.setClientState("client_tradeChooseHaymarketResource", {
                descriptionmyturn : _('Choose resource to take from Haymarket Square')
            });
        },

        onReplaceAsset: function(){
            this.clientStateArgs.actionArgs.replace = 1;
            this.onConfirmAction();
        },

        onKeepAsset: function(){
            this.clientStateArgs.actionArgs.replace = 0;
            this.onConfirmAction();
        },

        onSkipAssetBonus: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/skipAssetBonus.html", { lock: true }, this, function( result ) {} );
        },

        onPassFreeAction: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/passFreeActions.html", { lock: true }, this, function( result ) {} );
        },

        onForfeitBonus: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/forfeitAppealBonus.html", { lock: true }, this, function( result ) {} );
        },

        onConfirmAssetUse: function(event){
            this.confirmAssetUse(this.clientStateArgs.assetName);
        },

        onConfirmDirectorshipChange: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/confirmDirectorship.html", { lock: true }, this, function( result ) {} );
        },

        onAcceptFirstPlayer: function(){
            this.clientStateArgs.actionArgs.acceptFirstPlayer = 1;
            this.onConfirmAction();
        },

        onDeclineFirstPlayer: function(){
            this.clientStateArgs.actionArgs.acceptFirstPlayer = 0;
            this.onConfirmAction();
        },

        onConfirmEmergencyFundraise: function(){
            var selectedShares = this.available_shares_company.getSelectedItems();
            var stockIds = [];
            for(var i = 0; i < selectedShares.length; i++){
                var selectedShare = selectedShares[i]; // spalding_common_3
                var stockId = selectedShare.id.split('_')[2];
                stockIds.push(stockId);
            }
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/emergencyFundraise.html", { lock: true, 
                'stockIds': stockIds.join(',')
            }, this, function( result ) {} );
        },

        onCancelEmergencyFundraise: function(){
            this.available_shares_company.unselectAll();
        },

        onPassEmergencyFundraise: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/passEmergencyFundraise.html", { lock: true }, this, function( result ) {} );
        },

        onCancelAppealBonus: function(event){
            if(this.clientStateArgs.undoMoves){
                dojo.forEach(this.clientStateArgs.undoMoves, function(item){
                    dojo.place(item.element, item.to);
                });
            }

            this.restoreServerGameState();
        },

        onCancelAction: function(event){
            // destroy meeple
            var workerId = this.clientStateArgs.partnerId;
            var buildingAction = this.clientStateArgs.buildingAction;

            if(workerId && buildingAction)
                this[buildingAction + '_holder'].removeFromZone(workerId, true);

            switch(buildingAction){
                case 'job_market_worker':
                    // put back workers in market
                    var selectedFactories = this.clientStateArgs.selectedFactories;
                    if(selectedFactories){
                        for(var i = 0; i < selectedFactories.length; i++){
                            var workerId = selectedFactories[i].workerId;
                            if(workerId != null){
                                this.job_market.placeInZone(workerId);
                            }
                        }
                    }

                    // destroy all temp workers
                    dojo.query('.worker.temp').forEach(dojo.destroy);
                    break;
                case "building23": // double manager
                case "building35":
                    // destroy temp managers
                    dojo.query('.manager.temp').forEach(dojo.destroy);
                    break;
                case "building6":
                case "building24":
                case "building21":
                case "building42": // any automation
                    dojo.forEach(this.clientStateArgs.undoMoves, function(item){
                        dojo.removeClass(item.element, 'first-automation');
                        dojo.place(item.element, item.to);
                    });
                    break;
                case "building26":
                case "building43":
                case "building44":
                    dojo.query('.worker.temp').forEach(dojo.destroy);
                    break;
            }

            // reset counters with server-side counters
            this.updateCounters(this.gamedatas.counters);

            // make factories and companies inactive
            dojo.query('.factory').removeClass('active');
            dojo.query('.company').removeClass('active');

            this.capital_assets.setSelectionMode(0);

            this.restoreServerGameState();
        },

        onCancel : function(event) {
            dojo.stopEvent(event);
            if (this.on_client_state) {
                this.restoreServerGameState();
            } else {
                this.ajaxAction('selectCancel', {});
            }
        },

        onCancelBuyCertificate: function(event){

            this.available_shares_bank.unselectAll();
            this.available_shares_company.unselectAll();

            this.restoreServerGameState();
            dojo.stopEvent(event);
        },

        onResourceSelected: function(control_name, item_id){
            if(!this.checkAction('buyResources'))
            {
                return;
            }

            var total = 0;
            var count = 0;
            var items = this.supply_10.getSelectedItems();
            count += items.length;
            total += items.length * 10;

            items = this.supply_20.getSelectedItems();
            count += items.length;
            total += items.length * 20;

            items = this.supply_30.getSelectedItems();
            count += items.length;
            total += items.length * 30;

            this.setClientState("client_playerTurnConfirmBuyResources", {
                descriptionmyturn : dojo.string.substitute(_('Buy ${count} resources for $${cost}'),{
                    count: count,
                    cost: total
                })
            });
        },

        onHaymarketResourceCreated: function(item_div, asset_type_id, item_id){
            dojo.connect($(item_div), 'onclick', this, 'onHaymarketSquareClicked');
        },

        onHaymarketSquareClicked: function(event){
            if(!this.checkAction('tradeResources'))
                return;
            
            dojo.stopEvent(event);

            // check resources in haymarket
            var items = this.haymarket.getAllItems();
            if(items.length == 0){
                this.showMessage( _('Haymarket Square is empty'), 'info' );
                return;
            }

            var stateName = this.gamedatas.gamestate.name;
            if(stateName == 'playerBuyResourcesPhase' || 
                stateName == 'playerProduceGoodsPhase' ||
                stateName == 'playerDistributeGoodsPhase' ||
                stateName == 'playerDividendsPhase'){
                var companyShortName = this.gamedatas.gamestate.args.company_short_name;
                this.createResourceOptions(companyShortName);
            } else {
                this.setClientState("client_tradeChooseCompany", {
                    descriptionmyturn : _('Choose a company'),
                });
            }
        },

        onAssetSelected: function(control_name, item_id){
            if(!this.checkAction('buildingAction'))
                return;

            var assets = this.capital_assets.getSelectedItems();

            if(assets.length == 0){
                return;
            }

            var item = this.capital_assets.getItemById(item_id);
            if(item.type == 0)
            {
                this.capital_assets.unselectItem(item_id);
                return;
            }

            var split = item_id.split('_'); // union_stockyards_18
            this.clientStateArgs.actionArgs.assetId = split[split.length - 1];
            var shortName = this.clientStateArgs.companyShortName;
            if(this[shortName + '_asset'] != null){
                var items = this[shortName + '_asset'].getAllItems();
                if(items.length > 0){
                    // need to choose to replace or keep current asset
                    this.setClientState("client_chooseKeepOrReplace", {
                        descriptionmyturn : _("Choose to keep or replace the company's current asset")
                    });
                } else {
                    this.clientStateArgs.actionArgs.replace = 1;
                    this.onConfirmAction();
                }
            } else {
                this.clientStateArgs.actionArgs.replace = 1;
                this.onConfirmAction();
            }
        },

        onPersonalShareSelected: function(control_name, item_id){
            if(!this.checkAction('sellShares'))
                return;
            
            var playerId = this.player_id;
            var numberOfItems = this['personal_area_'+playerId].getSelectedItems().length;
            
            this.setClientState("client_playerTurnConfirmSellShares", {
                descriptionmyturn : dojo.string.substitute(_('Sell ${numberOfCertificates} certificates'),{
                    numberOfCertificates: numberOfItems
                })
            });
        },

        onCancelBuyResources: function(){
            this.supply_10.unselectAll();
            this.supply_20.unselectAll();
            this.supply_30.unselectAll();
            this.restoreServerGameState();
        },

        onConfirmBuyResources: function(){
            if(!this.checkAction('buyResources'))
            {
                return;
            }

            var resourceIds = [];
            var items = this.supply_10.getSelectedItems();
            for(var index in items){
                resourceIds.push(items[index].id);
            }

            items = this.supply_20.getSelectedItems();
            for(var index in items){
                resourceIds.push(items[index].id);
            }

            items = this.supply_30.getSelectedItems();
            for(var index in items){
                resourceIds.push(items[index].id);
            }

            this.supply_10.setSelectionMode(0);
            this.supply_20.setSelectionMode(0);
            this.supply_30.setSelectionMode(0);

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/buyResources.html", { lock: true,
                resourceIds: resourceIds.join(",")
            }, this, function( result ) {} );
        },

        onSkipBuyResources: function(){
            if(!this.checkAction('skipBuyResources'))
            {
                return;
            }
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/skipBuyResources.html", { lock: true,
            }, this, function( result ) {} );
        },

        // control_name = available_shares_company (or bank)
        // item_id = brunswick_common_134
        onAvailableShareSelected: function(control_name, item_id){
            var stateName = this.gamedatas.gamestate.name;
            if(stateName == 'client_playerConfirmEmergencyFundraise' || stateName == 'playerEmergencyFundraise'){
                var companyItems = this.available_shares_company.getSelectedItems();
                if(companyItems.length == 0){
                    this.restoreServerGameState();
                    return;
                }
                var split = item_id.split('_'); // brunswick_common_3
                if(this.available_shares_company.isSelected(item_id)){
                    var stockCompany = split[0];
                    var operatedCompany = this.gamedatas.gamestate.args.company_short_name;
                    if(stockCompany != operatedCompany){
                        this.showMessage( _('Can only sell shares from the company being operated'), 'info' );
                        this.available_shares_company.unselectItem(item_id);
                        return;
                    }
                }

                var totalValue = 0;
                var selectedShares = this.available_shares_company.getSelectedItems();
                var stockId =  split[2];
                for(var i = 0; i < selectedShares.length; i++){
                    totalValue += this.gamedatas.counters['stock_' + stockId].counter_value;
                }

                this.setClientState("client_playerConfirmEmergencyFundraise", {
                    descriptionmyturn : dojo.string.substitute(_('Sell ${count} certificates for $${funds}'),{
                        'count': selectedShares.length,
                        'funds': totalValue
                    })
                });
                
            } else if (stateName == 'client_playerTurnBuyCertificate' || stateName == 'playerSkipSellBuyPhase' || stateName == 'playerBuyPhase') {
                var bankItems = this.available_shares_bank.getSelectedItems();
                var companyItems = this.available_shares_company.getSelectedItems();

                if(bankItems.length + companyItems.length == 0){
                    this.restoreServerGameState();
                    return;
                }

                var items = [];
                if(control_name == 'available_shares_company' && companyItems.length != 0){
                    items = companyItems;
                    this.available_shares_bank.unselectAll();
                } else if (control_name == 'available_shares_bank' && bankItems.length != 0){
                    items = bankItems;
                    this.available_shares_company.unselectAll();
                }

                if(items.length == 1){
                    this.available_companies.unselectAll();
                    var item_id = items[0].id; // brunswick_common_3
                    var companyShortName = item_id.split('_')[0];
                    var companyName = this.gamedatas.all_companies[companyShortName].name;
                    this.setClientState("client_playerTurnBuyCertificate", {
                        descriptionmyturn : dojo.string.substitute(_('Buy certificate for ${companyName}'),{
                            companyName: companyName
                        })
                    });
                }
            }
        },

        onClickBuildingPlayerArea: function(event)
        {
            // Preventing default browser reaction
            dojo.stopEvent(event);

            if(!this.checkAction('selectBuildings'))
                return;

            var state = this.gamedatas.gamestate.name;
            if(state == "playerBuildingPhase"){
                dojo.addClass(event.target, "building_to_play");
                this.setClientState("clientPlayerDiscardBuilding", {
                    descriptionmyturn : _('You must choose a building to discard')
                });
            }
            else if(state == "clientPlayerDiscardBuilding"){
                if(!dojo.hasClass(event.target, "building_to_play")){
                    dojo.addClass(event.target, "building_to_discard");
                    this.setClientState("clientBuildingPhaseConfirm", {
                        descriptionmyturn : _('Confirm selection')
                    });
                }
            }
        },

        onConfirmBuildings: function(event){
            dojo.stopEvent(event);

            if(!this.checkAction('selectBuildings'))
                return;

            // example: building_area_2319929_item_building11_36
            var buildingToPlayId = dojo.query('.building_to_play')[0].id;
            var buildingToDiscardId = dojo.query('.building_to_discard')[0].id;
            
            var splitBuildingToPlay = buildingToPlayId.split('_');
            var splitBuildingToDiscard = buildingToDiscardId.split('_');

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/selectBuildings.html", { lock: true,
                playedBuildingId: splitBuildingToPlay[5],
                discardedBuildingId: splitBuildingToDiscard[5]
            }, this, function( result ) {} );
        },

        onCompanySelected: function(control_name, item_id){
            if(!this.checkAction('startCompany'))
            {
                return;
            }

            var items = this.available_companies.getSelectedItems();
            if(items.length == 0){
                this.restoreServerGameState();
                return;
            }

            if(items.length == 1){

                var gamestate = this.gamedatas.gamestate;
                if(gamestate.name == 'playerSkipSellBuyPhase' || gamestate.name == 'playerBuyPhase' || gamestate.name == 'client_playerTurnBuyCertificate')
                {
                    if(gamestate.args.round == 0){
                        this.showMessage( _('You cannot start a new company during the first decade'), 'info' );
                        this.available_companies.unselectAll();
                        return;
                    }
                }

                this.available_shares_company.unselectAll();
                this.available_shares_bank.unselectAll();

                var companyShortName = items[0].id;
                var companyName = this.gamedatas.all_companies[companyShortName].name;
                this.setClientState("client_playerTurnSelectStartingShareValue", {
                    descriptionmyturn : dojo.string.substitute(_('You must select an initial share price for ${companyName}'),{
                        companyName: companyName
                    })
                });
            }
        },

        onStartCompany: function (event){
            console.log('onStartCompany');

            // Preventing default browser reaction
            dojo.stopEvent(event);

            if(!this.checkAction('startCompany'))
            {
                return;
            }

            var initialShareValueStep = 4;
            var buttonName = event.currentTarget.id;
            switch(buttonName){
                case 'initial_share_40':
                    initialShareValueStep = 5;
                    break;
                case 'initial_share_50':
                    initialShareValueStep = 6;
                    break;
                case 'initial_share_60':
                    initialShareValueStep = 7;
                    break;
            }

            var items = this.available_companies.getSelectedItems();
            if(items.length == 1){
                var companyShortName = items[0].id;

                this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/startCompany.html", { lock: true,
                    company_short_name: companyShortName,
                    initialShareValueStep: initialShareValueStep //can be 4,5,6,7 for $35,$40,$50,$60
                }, this, function( result ) {} );
            }
        },

        onSellBuyShare: function(){
            this.setClientState("client_playerStockPhaseSellShares", {
                descriptionmyturn : _('You must select the shares you want to sell')
            });
        },

        onConfirmShareSell: function(){
            if(!this.checkAction('sellShares'))
            {
                return;
            }

            var playerId = this.getActivePlayerId();
            var selectedShares = this['personal_area_'+playerId].getSelectedItems();
            if(selectedShares.length == 0)
            {
                this.showMessage( _('You must select at least one certificate to sell'), 'info' );
                return;
            }

            var ids = [];
            for(var index in selectedShares){
                var selectedShare = selectedShares[index];
                var split = selectedShare.id.split('_');
                ids.push(split[2]);
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/sellShares.html", { lock: true,
                selected_shares: ids.join(';') 
            }, this, function( result ) {} );
        },

        onSkipBuy: function(){
            if(!this.checkAction('skipBuy'))
            {
                return;
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/skipBuy.html", { lock: true,
            }, this, function( result ) {} );
        },

        onSkipSell: function(){
            if(!this.checkAction('sellShares'))
            {
                return;
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/skipSell.html", { lock: true }, this, function( result ) {} );
        },

        gainAppealBonus: function(){
            if(!this.checkAction('gainAppealBonus'))
            {
                return;
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/gainAppealBonus.html", { lock: true,
                factoryNumber: this.clientStateArgs.factoryNumber,
                relocateFactoryNumber: this.clientStateArgs.relocateFactoryNumber,
                workerId: this.clientStateArgs.workerId // for automations
            }, this, function( result ) {} );
        },

        onConfirmDistributeGoods: function(){
            var goods = this.clientStateArgs.goods;
            var demandIds = [];
            var goodIds = [];
            for(var i = 0; i < goods.length; i++){
                var good = goods[i];
                demandIds.push(good.demandId);
                goodIds.push(good.goodId);
            }
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/distributeGoods.html", { lock: true,
                demandIds: demandIds.join(','),
                goodIds: goodIds.join(',')
            }, this, function( result ) {} );
        },

        onCancelDistributeGoods: function(){
            //this.clientStateArgs.goods.push({'demandId': demandIdentifier, 'goodId': card_id});
            var goods = this.clientStateArgs.goods;
            var companyShortName = this.gamedatas.gamestate.args.company_short_name;
            for(var i = 0; i < goods.length; i++){
                var good = goods[i];
                this[companyShortName + '_goods'].placeInZone('good_' + good.goodId);
                this[good.zoneName].removeFromZone('good_' + good.goodId);
            }

            this.restoreServerGameState();
        },

        onSkipDistributeGoods: function(){
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/skipDistributeGoods.html", { lock: true }, this, function( result ) {} );
        },

        onSkipProduceGoods: function(){
            if(!this.checkAction('skipProduceGoods'))
            {
                return;
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/skipProduceGoods.html", { lock: true }, this, function( result ) {} );
        },

        onConfirmBuy: function(){
            if(!this.checkAction('buyCertificate'))
            {
                return;
            }

            var selectedShares = this.available_shares_company.getSelectedItems();
            if(selectedShares.length == 0)
            {
                selectedShares = this.available_shares_bank.getSelectedItems();

                if(selectedShares.length == 0){
                    this.showMessage( _('You must select at least one certificate to buy'), 'info' );
                    return;
                }
            }

            var item = selectedShares[0];

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/buyCertificate.html", { lock: true,
                certificate: item.id
            }, this, function( result ) {} );
        },

        onStockPass: function(){
            if(!this.checkAction('passStockAction'))
            {
                return;
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/passStockAction.html", { lock: true }, this, function( result ) {} );
        },

        onSkipToRelocate: function(){
            this.clientStateArgs.secondAutomationSkipped = true;
            this.setClientState("client_chooseFactoryRelocateDoubleAutomation", {
                descriptionmyturn : _('Choose a factory in which to relocate the automated worker')
            });
        },

        onSkipReceiveWorker: function(){
            this.clientStateArgs.actionArgs.workerFactoryNumber = 0;
            if(this.clientStateArgs.buildingAction == "building43"){
                this.chooseFactorySkip(_("Choose a factory to put hired manager"));
            }
            else if(this.clientStateArgs.buildingAction == "building44"){
                this.chooseCompany();
            }
        },

        onConfirmActionSkip: function(){
            if(!this.clientStateArgs.companyShortName){
                // since you can skip the action just to block someone
                // we still need to know which company this is for
                this.setClientState("client_chooseCompanyToPay", {
                    descriptionmyturn : _('Choose a company to pay for this action')
                });
            } else {
                this.onConfirmAction();
            }
        },

        onConfirmAction: function(){
            if(!this.checkAction('buildingAction'))
                return;
            
            var args = [];
            var args2 = [];
            switch(this.clientStateArgs.buildingAction){
                case 'job_market_worker':
                    var selectedFactories = this.clientStateArgs.selectedFactories;
                    for(var i = 0; i < selectedFactories.length; i++){
                        args.push(selectedFactories[i].factoryNumber); 

                        var workerId = selectedFactories[i].workerId;
                        if(workerId != null){
                            var workerNumber = selectedFactories[i].workerId.split('_')[1];
                            args2.push(workerNumber);  
                        } else {
                            args2.push(0);
                        }
                    }
                    break;
                default:
                    for(var property in this.clientStateArgs.actionArgs){
                        var arg = this.clientStateArgs.actionArgs[property];
                        args.push(arg);
                    }
                    break;
            }

            var actionArgs = args.join(",");
            var actionArgs2 = args2.join(",");

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/buildingAction.html", { lock: true,
                buildingAction: this.clientStateArgs.buildingAction,
                companyShortName: this.clientStateArgs.companyShortName,
                workerId: this.clientStateArgs.partnerId,
                factoryNumber: this.clientStateArgs.factoryNumber,
                actionArgs: actionArgs,
                actionArgs2: actionArgs2
            }, this, function( result ) {} );
        },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your cityofthebigshoulders.game.php file.
        
        */
        setupNotifications: function()
        {
            dojo.subscribe( 'startCompany', this, "notif_startCompany" );
            this.notifqueue.setSynchronous( 'startCompany', 500 );

            dojo.subscribe( 'automationTokensCreated', this, "notif_automationTokensCreated" );
            this.notifqueue.setSynchronous( 'automationTokensCreated', 200 );

            dojo.subscribe( 'certificateBought', this, "notif_certificateBought" );
            this.notifqueue.setSynchronous( 'certificateBought', 500 );

            dojo.subscribe ('buildingsSelected', this, "notif_buildingsSelected");
            this.notifqueue.setSynchronous( 'buildingsSelected', 500 );

            dojo.subscribe ('buildingsRemoved', this, "notif_buildingsRemoved");

            dojo.subscribe ('workersAdded', this, "notif_workersAdded");
            this.notifqueue.setSynchronous( 'workersAdded', 500 );

            dojo.subscribe('workersHired', this, "notif_workersHired");
            this.notifqueue.setSynchronous('workersHired', 500);

            dojo.subscribe('actionUsed', this, "notif_actionUsed");
            this.notifqueue.setSynchronous('actionUsed', 500);

            dojo.subscribe('countersUpdated', this, "notif_countersUpdated");
            this.notifqueue.setSynchronous('countersUpdated', 500);

            dojo.subscribe('appealIncreased', this, "notif_appealIncreased");
            this.notifqueue.setSynchronous('appealIncreased', 500);

            dojo.subscribe('managerHired', this, "notif_managerHired");
            this.notifqueue.setSynchronous('managerHired', 500);

            dojo.subscribe('salespersonHired', this, "notif_salespersonHired");
            this.notifqueue.setSynchronous('salespersonHired', 500);

            dojo.subscribe('dividendEarned', this, "notif_dividendEarned");
            this.notifqueue.setSynchronous('dividendEarned', 500);

            dojo.subscribe('shareValueChange', this, "notif_shareValueChange");
            this.notifqueue.setSynchronous('shareValueChange', 500);

            dojo.subscribe('goodsProduced', this, "notif_goodsProduced");
            this.notifqueue.setSynchronous('goodsProduced', 500);

            dojo.subscribe('factoryAutomated', this, "notif_factoryAutomated");
            this.notifqueue.setSynchronous('factoryAutomated', 500);

            dojo.subscribe('resourcesBought', this, "notif_resourcesBought");
            this.notifqueue.setSynchronous('resourcesBought', 500);

            dojo.subscribe('resourcesConsumed', this, "notif_resourcesConsumed");
            this.notifqueue.setSynchronous('resourcesConsumed', 500);

            dojo.subscribe('goodsDistributed', this, "notif_goodsDistributed");
            this.notifqueue.setSynchronous('goodsDistributed', 500);

            dojo.subscribe('earningsWithhold', this, "notif_earningsWithhold");
            this.notifqueue.setSynchronous('earningsWithhold', 500);

            dojo.subscribe('resourcesShifted', this, "notif_resourcesShifted");
            this.notifqueue.setSynchronous('resourcesShifted', 200);

            dojo.subscribe('resourcesDrawn', this, "notif_resourcesDrawn");
            this.notifqueue.setSynchronous('resourcesDrawn', 200);

            dojo.subscribe('resourcesDiscarded', this, "notif_resourcesDiscarded");
            this.notifqueue.setSynchronous('resourcesDiscarded', 200);

            dojo.subscribe('assetDiscarded', this, "notif_assetDiscarded");
            this.notifqueue.setSynchronous('assetDiscarded', 200);

            dojo.subscribe('assetsShifted', this, "notif_assetsShifted");
            this.notifqueue.setSynchronous('assetsShifted', 200);

            dojo.subscribe('demandDiscarded', this, "notif_demandDiscarded");
            this.notifqueue.setSynchronous('demandDiscarded', 200);

            dojo.subscribe('demandShifted', this, "notif_demandShifted");
            this.notifqueue.setSynchronous('demandShifted', 200);

            dojo.subscribe('demandDrawn', this, "notif_demandDrawn");
            this.notifqueue.setSynchronous('demandDrawn', 200);

            dojo.subscribe('partnersReturned', this, "notif_partnersReturned");
            this.notifqueue.setSynchronous('partnersReturned', 200);

            dojo.subscribe('shareSold', this, "notif_shareSold");
            this.notifqueue.setSynchronous('shareSold', 200);

            dojo.subscribe('shareTransferred', this, "notif_shareTransferred");
            this.notifqueue.setSynchronous('shareTransferred', 200);

            dojo.subscribe('emergencyFundraise', this, "notif_emergencyFundraise");
            this.notifqueue.setSynchronous('emergencyFundraise', 200);

            dojo.subscribe('buildingsDealt', this, "notif_buildingsDealt");
            this.notifqueue.setSynchronous('buildingsDealt', 200);

            dojo.subscribe('assetGained', this, "notif_assetGained");
            this.notifqueue.setSynchronous('assetGained', 200);

            dojo.subscribe('resourcesTraded', this, "notif_resourcesTraded");
            this.notifqueue.setSynchronous('resourcesTraded', 200);

            dojo.subscribe('assetUsed', this, "notif_assetUsed");
            this.notifqueue.setSynchronous('assetUsed', 200);

            dojo.subscribe('workerReceived', this, "notif_workerReceived");
            this.notifqueue.setSynchronous('workerReceived', 200);

            dojo.subscribe('companyAssetDiscarded', this, "notif_companyAssetDiscarded");
            this.notifqueue.setSynchronous('companyAssetDiscarded', 200);

            dojo.subscribe('marketSquareReset', this, "notif_marketSquareReset");
            this.notifqueue.setSynchronous('marketSquareReset', 200);

            dojo.subscribe('appealBonusGoodsTokenReceived', this, "notif_appealBonusGoodsTokenReceived");
            this.notifqueue.setSynchronous('appealBonusGoodsTokenReceived', 200);

            dojo.subscribe('directorshipChange', this, "notif_directorshipChange");
            this.notifqueue.setSynchronous('directorshipChange', 200);

            dojo.subscribe('scoreUpdated', this, "notif_scoreUpdated");

            dojo.subscribe('newRound', this, "notif_newRound");

            dojo.subscribe('newPhase', this, "notif_newPhase");

            dojo.subscribe('dealMarkerReceived', this, "notif_dealMarkerReceived");

            dojo.subscribe('playerOrderInitialized', this, "notif_playerOrderInitialized");

            dojo.subscribe('playerOrderChanged', this, "notif_playerOrderChanged");
        },

        notif_directorshipChange: function(notif){
            var shortName = notif.args.short_name;
            var previous_owner_id = notif.args.previous_owner_id;
            var new_owner_id = notif.args.new_owner_id;
            var hash = this.hashString(shortName);

            // all the content of the company needs to be transferred to the new element being created
            var companyContentElement = dojo.byId("company_" + shortName);

            var from = 'company_area_'+previous_owner_id+'_item_'+shortName;
            this['companyArea'+new_owner_id].addToStockWithId(hash, shortName, from);
            var stockElementId = this['companyArea'+new_owner_id].getItemDivId(shortName);
            dojo.place(companyContentElement, stockElementId, "only");

            this['companyArea'+previous_owner_id].removeFromStockById(shortName);

            // transfer director share
            var stockId = notif.args.director_id;
            var stockType = shortName + '_director';
            var hashStockType = this.hashString(stockType);

            var from = 'personal_area_' + previous_owner_id + '_item_' + stockType + '_' + stockId; //personal_area_2319929_item_brunswick_common_133
            this['personal_area_' + new_owner_id].addToStockWithId(hashStockType, stockType+'_'+stockId, from);
            this['personal_area_' + previous_owner_id].removeFromStockById(stockType+'_'+stockId);
        },

        notif_playerOrderChanged: function(notif){
            var orderWeight = {};
            for(var index in notif.args.player_order){
                var player = notif.args.player_order[index];
                var hash = this.hashString(player.color);
                orderWeight[hash] = Number(player.player_order);

                var itemDiv = this.player_order.getItemDivId(player.player_name);
                dojo.style(itemDiv, "z-index", player.player_order);
            }
            
            this.player_order.changeItemsWeight( orderWeight ); // { 1: 10, 2: 20, itemType: Weight }
        },

        notif_playerOrderInitialized: function(notif){
            var orderWeight = {};
            for(var index in notif.args.player_order){

                var player = notif.args.player_order[index];
                var hash = this.hashString(player.color);
                this.player_order.addToStockWithId(hash, player.player_name);
                orderWeight[hash] = Number(player.player_order);

                var itemDiv = this.player_order.getItemDivId(player.player_name);
                dojo.style(itemDiv, "z-index", player.player_order);
            }
            
            this.player_order.changeItemsWeight( orderWeight ); // { 1: 10, 2: 20, itemType: Weight }
        },

        notif_appealBonusGoodsTokenReceived: function(notif){
            this.placeExtraGood(notif.args.company_short_name, notif.args.total_goods, true);
        },

        notif_dealMarkerReceived: function(notif){
            var playerId = notif.args.player_id;

            dojo.query(".priority-holder>").removeClass("priority-marker");
            dojo.query("#priority_" + playerId).addClass("priority-marker");
        },

        notif_marketSquareReset: function(notif){
            this.haymarket.removeAll();
            for(var index in notif.args.resources){
                this.placeResource(notif.args.resources[index]);
            }
        },

        notif_companyAssetDiscarded: function(notif){
            var companyShortName = notif.args.short_name;
            this[companyShortName + '_asset'].removeAll();
        },

        notif_scoreUpdated: function(notif){
            for(var index in notif.args.scores){
                var score = notif.args.scores[index];
                this.scoreCtrl[score.player_id].incValue(score.score_delta);
            }
        },

        notif_newRound: function(notif){
            dojo.query(".asset-tile").removeClass('exhausted');

            var round = notif.args.round + 1;
            var roundMarker = $('round_marker');
            var parent = roundMarker.parentNode;
            dojo.place(roundMarker, 'round_' + round);
            this.placeOnObject(roundMarker, parent);
            this.slideToObject(roundMarker, 'round_' + round).play();
        },

        notif_newPhase: function(notif){
            var phase = notif.args.phase + 1;
            var phaseMarker = $('phase_marker');
            var parent = phaseMarker.parentNode;
            dojo.place(phaseMarker, 'phase_' + phase);
            this.placeOnObject(phaseMarker, parent);
            this.slideToObject(phaseMarker, 'phase_' + phase).play();
        },

        notif_assetUsed: function(notif){
            var assetName = notif.args.asset_short_name;
            var asset = dojo.query('div[asset-name="' + assetName  + '"]');
            asset.addClass('exhausted');
            asset.removeClass('active');
        },

        notif_assetGained: function(notif){
            var assetName = notif.args.asset.card_type;
            var assetId = notif.args.asset.card_id;
            var fromItemDiv = this.capital_assets.getItemDivId(assetName + '_' + assetId);
            this.placeAsset(notif.args.asset, 'capital_assets', fromItemDiv);
            this.capital_assets.addToStock(0);
        },

        notif_buildingsDealt: function(notif){
            for(var index in notif.args.dealt_buildings){
                var dealtBuilding = notif.args.dealt_buildings[index];
                var hashBuildingType = this.hashString(dealtBuilding.card_type);
                var itemId = dealtBuilding.card_type+'_'+dealtBuilding.card_id;

                this.buildings.addToStockWithId(hashBuildingType, itemId, 'main_board');
                var div = this.buildings.getItemDivId(itemId);

                var buildingMaterial = this.gamedatas.all_buildings[dealtBuilding.card_type];
                this.addTooltip( div, _( buildingMaterial.tooltip ), "");
            }
        },

        notif_resourcesTraded: function(notif){
            var toHaymarket1 = notif.args.to_haymarket1;
            var toHaymarket2 = notif.args.to_haymarket2;
            var toCompany = notif.args.to_company;

            this.placeResource(toHaymarket1, toCompany.card_location + '_resources', toCompany.card_location + '_resources_item_' + toHaymarket1.card_id);
            this.placeResource(toHaymarket2, toCompany.card_location + '_resources', toCompany.card_location + '_resources_item_' + toHaymarket2.card_id);
            this.placeResource(toCompany, 'haymarket', 'haymarket_item_' + toCompany.card_id);
        },

        notif_emergencyFundraise: function(notif){
            for(var index in notif.args.stocks){
                var stock = notif.args.stocks[index];
                var item_id = stock.card_type + '_' + stock.card_id;
                var hashStockType = this.hashString(stock.card_type);
                var from = 'available_shares_company_item_' + item_id;
                this.available_shares_bank.addToStockWithId(hashStockType, item_id, from);
                this.available_shares_company.removeFromStockById(item_id);
            }

            this.updateCounters(notif.args.counters);
        },

        notif_shareTransferred: function(notif){
            var stockId = notif.args.id;
            var playerId = notif.args.player_id;
            var stockType = notif.args.type;

            var hashStockType = this.hashString(stockType);

            var from = 'personal_area_' + notif.args.from_id + '_item_' + stockType + '_' + stockId; //personal_area_2319929_item_brunswick_common_133
            this['personal_area_' + playerId].addToStockWithId(hashStockType, stockType+'_'+stockId, from);
            this['personal_area_' + notif.args.from_id].removeFromStockById(stockType+'_'+stockId);
        },

        notif_shareSold: function(notif){
            var stockId = notif.args.id;
            var playerId = notif.args.player_id;
            var stockType = notif.args.type;
            var hashStockType = this.hashString(stockType);
            var from = 'personal_area_' + playerId + '_item_' + stockType + '_' + stockId; //personal_area_2319929_item_brunswick_common_133
            this.available_shares_bank.addToStockWithId(hashStockType, stockType+'_'+stockId, from);
            this['personal_area_' + playerId].removeFromStockById(stockType+'_'+stockId);
        },

        notif_partnersReturned: function(notif){
            var workerSpots = dojo.query('.worker_spot');
            for(var i = 0; i < workerSpots.length; i++){
                var workerSpot = workerSpots[i];
                this[workerSpot.id + '_holder'].removeAll();
            }

            this.updateCounters(notif.args.counters);
        },

        notif_demandDrawn: function(notif){
            var demand = notif.args.demand;

            this.placeDemand(demand);
            this.placeOnObject(demand.card_type, 'main_board');
            this.slideToObject(demand.card_type, demand.card_location).play();
        },

        notif_demandShifted: function(notif){
            var demand = notif.args.demand;
            var from = notif.args.from;

            dojo.place(demand.card_type, demand.card_location);
            this.placeOnObject(demand.card_type, from);
            this.slideToObject(demand.card_type, demand.card_location).play();
        },

        notif_demandDiscarded: function(notif){
            var demandNumber = notif.args.demand_number;

            // remove all goods from card
            this[demandNumber + '_goods'].removeAll();

            // some demand tiles don't exist on the front-end because they are used only when demand deck is empty
            // so make sure the demand actually exists before destroying it
            if($(demandNumber) != null) 
                this.fadeOutAndDestroy( demandNumber, 200);
        },

        notif_assetsShifted: function(notif){
            var newAsset = notif.args.new_asset;
            var hash = this.hashString(newAsset.card_type);
            var newWeights = {};
            newWeights[hash] = 0;

            for(var index in notif.args.assets){
                var asset = notif.args.assets[index];
                hash = this.hashString(asset.card_type);
                newWeights[hash] = 80-Number(asset.card_location);
            }

            // remove any white space that would be added during a player's turn when buying assets
            this.capital_assets.removeFromStock(0);
            this.capital_assets.changeItemsWeight( newWeights );

            this.placeAsset(newAsset);

            // add empty assets when asset deck is empty
            var items = this.capital_assets.getAllItems();
            var emptySpaces = 5 - items.length;
            for(var i = 0; i < emptySpaces; i++){
                this.capital_assets.addToStock(0);
            }
        },

        notif_assetDiscarded: function(notif){
            var assetName = notif.args.asset_name;
            var assetId = notif.args.asset_id;
            this.capital_assets.removeFromStockById(assetName + '_' + assetId);
        },

        notif_resourcesDiscarded: function(notif){
            for(var index in notif.args.resource_ids_types){
                var resource = notif.args.resource_ids_types[index];
                var fromItemDiv = 'supply_10_item_' + resource.id;
                this.placeResource({
                    card_id: resource.id,
                    card_type: resource.type,
                    card_location: 'haymarket'
                }, 'supply_10', fromItemDiv);
            }
        },

        notif_resourcesDrawn: function(notif){
            var location = notif.args.location;
            for(var index in notif.args.resource_ids_types){
                var resource = notif.args.resource_ids_types[index];
                var resourceId = resource.id;
                var resourceType = resource.type;
                var hash = this.hashString(resourceType);
                this['supply_' + location].addToStockWithId(hash, resourceId, 'player_boards');
                var div = this['supply_' + location].getItemDivId(resourceId);
                switch(resourceType)
                {
                    case 'livestock':
                        this.addTooltip( div, _('Livestock'), "");
                        break;
                    case 'coal':
                        this.addTooltip( div, _('Coal'), "");
                        break;
                    case 'wood':
                        this.addTooltip( div, _('Wood'), "");
                        break;
                    case 'steel':
                        this.addTooltip( div, _('Steel'), "");
                        break;
                }
            }
        },

        notif_resourcesShifted: function(notif){
            var from = notif.args.from;
            var location = notif.args.location;
            for(var index in notif.args.resource_ids_types){
                var resource = notif.args.resource_ids_types[index];
                var resourceId = resource.id;
                var resourceType = resource.type;
                var hash = this.hashString(resourceType);
                var fromItemDiv = 'supply_' + from + '_item_' + resourceId;
                this['supply_' + location].addToStockWithId(hash, resourceId, fromItemDiv);
                this['supply_' + from].removeFromStockById(resourceId);
                var div = this['supply_' + location].getItemDivId(resourceId);
                switch(resourceType)
                {
                    case 'livestock':
                        this.addTooltip( div, _('Livestock'), "");
                        break;
                    case 'coal':
                        this.addTooltip( div, _('Coal'), "");
                        break;
                    case 'wood':
                        this.addTooltip( div, _('Wood'), "");
                        break;
                    case 'steel':
                        this.addTooltip( div, _('Steel'), "");
                        break;
                }
            }
        },

        notif_earningsWithhold: function(notif){
            this.updateCounters(notif.args.counters);
        },

        notif_goodsDistributed: function(notif){
            var shortName = notif.args.short_name;

            for(var index in notif.args.demand_ids){
                var demandId = notif.args.demand_ids[index];
                var targetId = 'demand' + demandId;
                var goodId = notif.args.good_ids[index];

                // place good on demand tile
                this.placeGood({
                    card_location: targetId + '_goods',
                    card_id: goodId
                }, 'company', shortName + '_goods')
            }
        },

        notif_resourcesConsumed: function(notif){
            for(var index in notif.args.resources){
                var resource = notif.args.resources[index];
                resource.card_location = 'haymarket';
                var fromId = this[notif.args.company_short_name + '_resources'].getItemDivId(resource.card_id);
                this.placeResource(resource, notif.args.company_short_name + '_resources', fromId)
            }
        },

        notif_resourcesBought: function(notif){
            for(var resourceId in notif.args.resource_ids){
                var resource = notif.args.resource_ids[resourceId];
                var fromId = null;
                if(resource.from == 'haymarket'){
                    fromId = this.haymarket.getItemDivId(resource.card_id);
                } else {
                    fromId = this['supply_' + resource.from].getItemDivId(resource.card_id);
                }
                this.placeResource(resource, resource.from, fromId);
            }

            this.updateCounters(notif.args.counters);
        },

        notif_factoryAutomated: function(notif){
            var companyShortName = notif.args.company_short_name;
            var factoryNumber = notif.args.factory_number;
            var workerId = notif.args.worker_id;
            
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var worker = $('worker_' + workerId);
            var automation = null;
            var from = worker.parentNode.id;
            if(this.isCurrentPlayerActive()){
                // when doing a double automation, it's possible that the worker is already in its spot
                // check that first-automation worker exists
                var elements = dojo.query('.first-automation');
                if(elements.length > 0)
                {
                    // in this case everything is already in place for the active player
                    elements.removeClass('first-automation');
                    return;
                }

                // automation is already in the right place, so nothing to do
            } else {
                
                automation = dojo.query(factorySelector + '>.automation_holder>.automation_token')[0];

                // put worker where automation is to be able to move automation in its place
                dojo.place(worker, automation.parentNode.id);

                var item = {
                    card_id: automation.id.split('_')[1],
                    card_type: 'automation',
                    card_location: companyShortName + "_worker_holder_" + factoryNumber};
                this.placeAutomationToken(item, automation.parentNode.id);
            }

            var item = {
                card_id: workerId,
                card_type: 'worker',
                card_location: notif.args.worker_relocation};

            if(notif.args.worker_relocation == 'job_market'){
                this.placeWorker(item)
            } else if (notif.args.worker_relocation == 'supply') {
                this.fadeOutAndDestroy( worker );
            } else {
                this.placeWorkerInFactory(item, 'factory', from)
            }
        },

        notif_goodsProduced: function(notif){
            for(var index in notif.args.good_ids){
                good_id = notif.args.good_ids[index];
                this.placeGood({
                    card_id: good_id,
                    card_location: notif.args.company_short_name
                }, 'supply');
            }
        },

        notif_shareValueChange: function(notif){
            var shortName = notif.args.company_short_name;
            this['share_zone_'+notif.args.previous_share_value_step].removeFromZone('share_token_'+shortName, false);
            this['share_zone_'+notif.args.share_value_step].placeInZone('share_token_'+shortName);

            var allCompanies = this.gamedatas.all_companies;
            this.addTooltip( 'share_token_'+shortName, allCompanies[shortName].name, "");
        },

        notif_dividendEarned: function(notif){
            this.updateCounters(notif.args.counters);

            if(notif.args.player_id){
                this.scoreCtrl[notif.args.player_id].incValue(notif.args.earning);
            }
        },

        notif_salespersonHired: function(notif){
            this.placeSalesperson({
                card_id: notif.args.salesperson_id,
                card_location: notif.args.location
            }, true);
        },

        notif_managerHired: function(notif){
            dojo.query('.manager.temp').forEach(dojo.destroy);
            this.placeManager({
                card_id: notif.args.manager_id,
                card_location: notif.args.location
            }, true);
        },

        notif_appealIncreased: function(notif){
            this.placeAppealToken({
                short_name: notif.args.company_short_name,
                appeal: notif.args.appeal,
            }, null, notif.args.previous_appeal);

            this.updateAppealTokens(notif.args.appeal, notif.args.order);

            this.updateCounters(notif.args.counters);
        },

        notif_workerReceived: function(notif){
            dojo.query('.worker.temp').forEach(dojo.destroy); // when receiving double worker from supply
            workerId = notif.args.worker_id;
            this.placeWorkerInFactory({
                card_id: workerId,
                card_location: notif.args.factory_id
            }, 'supply');
        },

        notif_workersHired: function(notif){

            for(var index in notif.args.worker_ids){
                worker = notif.args.worker_ids[index];
                this.placeWorkerInFactory(worker, 'market');
            }

            // destroy all temp workers
            dojo.query('.worker.temp').forEach(dojo.destroy);

            // create new workers if any
            for(var index in notif.args.all_worker){
                var worker = notif.args.all_worker[index];

                this.placeWorkerInFactory({
                    card_id: worker.card_id,
                    card_location: worker.card_location
                }, 'market');
            }
        },

        notif_countersUpdated: function(notif){
            this.updateCounters(notif.args.counters);
        },

        notif_actionUsed: function(notif){
            // create worker if it doesn't exist and move it to building
            this.placePartner({
                card_type: notif.args.worker_id,
                card_location: notif.args.building_action
            });

            // update counters
            this.updateCounters(notif.args.counters);
        },

        notif_startCompany: function( notif )
        {
            var shortName = notif.args.short_name;
            var appeal = notif.args.appeal;
            var playerId = notif.args.owner_id;
            var initialShareValueStep = notif.args.initial_share_value_step;
            var stocks = notif.args.stocks;

            this.updateCounters(notif.args.counters);

            this.placeCompany({
                    short_name: shortName,
                    owner_id: playerId,
                    share_value_step: initialShareValueStep,
                }, 'available_companies');
            this.available_companies.removeFromStockById(shortName);

            for(var index in stocks)
            {
                var stock = stocks[index];
                this.placeStock(stock, 'available_companies');
                var counterName = "stock_" + stock.card_id;
                this.gamedatas.counters[counterName] = { 'counter_name': counterName, 'counter_value': 0 };
            }

            this.placeAppealToken({
                short_name: shortName,
                appeal: appeal,
                owner_id: playerId
            });

            this.updateAppealTokens(appeal, notif.args.order);
        },

        notif_automationTokensCreated: function(notif) {
            var automationTokens = notif.args.automation_tokens;
            for(var index in automationTokens){
                var automation = automationTokens[index];
                this.placeAutomationToken(automation);
            }
        },

        notif_certificateBought: function(notif) {
            this.moveStock(notif.args.stock, notif.args.from);

            this.updateCounters(notif.args.counters);
        },

        notif_workersAdded: function(notif){
            for(var i in notif.args.all_workers){
                var worker = notif.args.all_workers[i];
                this.placeWorker(worker);
            }
        },

        notif_buildingsRemoved: function(notif){
            var buildingToDiscard = notif.args.buildings.discard;
            var buildingToPlay = notif.args.buildings.play;
            var items = this.buildings.getAllItems();
            for(var index in items){
                var item = items[index];
                var divId = this.buildings.getItemDivId(item.id);
                if(item.id == buildingToDiscard.card_type + '_' + buildingToDiscard.card_id){
                    // remove discarded building
                    this.buildings.removeFromStockById(item.id);
                } else if (item.id == buildingToPlay.card_type + '_' + buildingToPlay.card_id){
                    // remove building that was moved to the building track
                    this.placeBuilding(buildingToPlay, divId);
                    this.buildings.removeFromStockById(item.id);
                }
            }
        },

        notif_buildingsSelected: function(notif){
            for(var i in notif.args.buildings){
                var building = notif.args.buildings[i];
                
                var split = building.card_location.split('_'); // pattern building_track_playerId
                var playerId = split[2];

                if(this.isSpectator)
                    this.placeBuilding(building, 'main_board');
                else if(playerId != this.player_id)
                    this.placeBuilding(building, 'building_area_'+playerId);
            }
        }
   });             
});
