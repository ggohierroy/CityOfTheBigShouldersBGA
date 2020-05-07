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
                'price_protection': 50, 'backroom_deals': 51, 'union_stockyards': 52, 'regrigeration': 53, 'foundry': 54, 
                'workshop': 55, 'michigan_lumber': 56, 'abattoir': 57, 'pennsylvania_coal': 58, 'cincinnati_steel': 59,
                'brilliant_marketing': 60
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
                    id: player.id,
                    color: player.color
                } ), player_board_div );

                // create building tracks
                this['building_track_'+player_id] = this.createBuildingsStock(gamedatas.all_buildings, 'building_track_'+player_id, 'setupNewTrackBuilding');
                this['building_track_'+player_id].item_margin=4;
            }

            this.player_color = gamedatas.players[this.player_id].color;
            this.clientStateArgs = {};
            this.clientStateArgs.actionArgs = {};

            // create zones and stocks
            this.createShareValueZones();
            this.createAppealZones();
            this.createJobMarketZone();
            this.createWorkerZones(gamedatas.general_action_spaces);
            this.createAssetTileStock(gamedatas.all_capital_assets);
            this.createResourceStock('haymarket');
            this.createResourceStock('supply_x');
            this.createResourceStock('supply_30');
            this.createResourceStock('supply_20');
            this.createResourceStock('supply_10');

            // create available shares stock
            this.createShareStock(gamedatas.all_companies, 'available_shares_company');
            dojo.connect( this.available_shares_company, 'onChangeSelection', this, 'onAvailableShareSelected' );

            // create available companies stock
            this.createCompaniesStock(gamedatas.all_companies);
            dojo.connect( this.available_companies, 'onChangeSelection', this, 'onCompanySelected' );

            // create buildings personal stock
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

            // add items to board
            this.placeItemsOnBoard(gamedatas);

            // update counters
            this.updateCounters(gamedatas.counters);

            // connect all worker spots
            dojo.query(".worker_spot.general-action").connect( 'onclick', this, 'onWorkerSpotClicked' );
            dojo.connect($('job_market_worker'), 'onclick', this, 'onWorkerSpotClicked');
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

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
                case 'playerStartFirstCompany':
                    if(this.isCurrentPlayerActive())
                    {
                        this.available_companies.setSelectionMode(1);
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
                    var playerId = this.getActivePlayerId();
                    this['personal_area_'+playerId].setSelectionMode(2);
                    break;
                case 'playerSkipSellBuyPhase':
                    this.available_companies.setSelectionMode(1);
                    this.available_companies.unselectAll();
                    this.available_shares_company.setSelectionMode(1);
                    this.available_shares_company.unselectAll();
                    break;
                case 'playerBuyPhase':
                    this.available_companies.setSelectionMode(1);
                    this.available_companies.unselectAll();
                    this.available_shares_company.setSelectionMode(1);
                    this.available_shares_company.unselectAll();
                    break;
                case 'playerBuildingPhase':
                    
                    break;
                case 'playerActionPhase':
                    // reset action args
                    this.clientStateArgs.actionArgs = {};
                    
                    if(this.isCurrentPlayerActive()){
                        dojo.query('.worker_spot').addClass('active');
                    } else {
                        dojo.query('.worker_spot').removeClass('active');
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
                    // activate all factories in player area
                    dojo.query('#player_'+this.player_id+' .factory').addClass('active');
                    break;
                case 'client_actionChooseCompany':
                    // activate all companies in player area
                    dojo.query('#player_'+this.player_id+' .company').addClass('active');
                    break;
                case 'client_chooseFactoryRelocate':
                    // activate all factories in the current company
                    var shortName = this.clientStateArgs.companyShortName;
                    dojo.query('#company_'+shortName+ ' .factory').addClass('active');
                    break;
                case 'playerBuyResourcesPhase':
                    if(this.isCurrentPlayerActive()){
                        this.supply_10.unselectAll();
                        this.supply_20.unselectAll();
                        this.supply_30.unselectAll();
                        this.supply_10.setSelectionMode(2);
                        this.supply_20.setSelectionMode(2);
                        this.supply_30.setSelectionMode(2);
                    } else {
                        this.supply_10.setSelectionMode(0);
                        this.supply_20.setSelectionMode(0);
                        this.supply_30.setSelectionMode(0);
                    }
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
                case 'client_playerStockPhaseSellShares':
                    var playerId = this.getActivePlayerId();
                    this['personal_area_'+playerId].setSelectionMode(0);
                    break;
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
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
                    case 'playerSkipSellBuyPhase':
                        this.addActionButton( 'stock_pass', _('Pass Stock Action'), 'onStockPass');
                        break;
                    case 'playerBuyPhase':
                        this.addActionButton( 'skip_buy', _('Skip'), 'onSkipBuy');
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
                        this.addActionButton( 'concel_buy', _('Cancel'), 'onCancel');
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
                        this.addActionButton( 'concel_buy', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_actionChooseCompany':
                        this.addActionButton( 'concel_buy', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_chooseNumberOfWorkers':
                        this.addActionButton( 'less_workers', _('-'), 'onRemoveWorker', null, false, 'gray');
                        this.addActionButton( 'more_workers', _('+'), 'onAddWorker', null, false, 'gray');
                        this.addActionButton( 'confirm_action', _('Confirm'), 'onConfirmAction');
                        this.addActionButton( 'concel_buy', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_chooseFactoryRelocate':
                        this.addActionButton( 'concel_buy', _('Cancel'), 'onCancelAction');
                        break;
                    case 'client_playerTurnConfirmBuyResources':
                        this.addActionButton( 'confirm_buy_resources', _('Confirm'), 'onConfirmBuyResources');
                        break;
                    case 'playerBuyResourcesPhase':
                        this.addActionButton( 'skip_buy_resources', _('Skip'), 'onSkipBuyResources');
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

        automateWorker: function(companyShortName, factoryNumber){
            // check if there is at least one spot to put the worker, otherwise confirm action
            var companySelector = '#company_' + companyShortName;
            var workerHolders = dojo.query(companySelector + ' .worker-holder:empty');
            if(workerHolders.length == 0){
                this.onConfirmAction();
                return;
            }

            // if there is a choice between multiple factories,
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var automationHolder = dojo.query(factorySelector + '>.automation_holder:not(:empty)')[0];
            var automation = automationHolder.firstChild;
            var holderPosition = automationHolder.id.split('_')[4]; // spalding_automation_holder_2_0
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

            this.undo = function(){
                dojo.place(automation, automationHolder);
                dojo.place(worker, workerHolder);
            };

            // then change client state to choose where to put worker
            this.setClientState("client_chooseFactoryRelocate", {
                descriptionmyturn : dojo.string.substitute(_('Choose a factory in which to relocate the automated worker'),{
                })
            });
        },
       
        canAutomateFactory: function(companyShortName, factoryNumber){
            // get first automation holder with a child (automation token)
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var automationHolders = dojo.query(factorySelector + '>.automation_holder:not(:empty');

            // if not found, factory is completely automated
            if(automationHolders.length == 0)
                return false;
            
            var automationHolder = automationHolders[0]; // spalding_automation_holder_2_0
            var holderPosition = automationHolder.id.split('_')[4];

            // get the worker holder in the same position as this automation holder
            // check that it exists
            var holderId = [companyShortName, factoryNumber, 'worker_holder', holderPosition];
            var workerHolder = dojo.byId(holderId.join('_')); // spalding_2_worker_holder_0

            return workerHolder != null && workerHolder.childNodes.length > 0;
        },

        createResourceStock: function(stockName){
            var newStock = new ebg.stock();

            newStock.create( this, $(stockName), 14, 14);

            // Specify that there are 5 buildings per row
            newStock.image_items_per_row = 13;
            newStock.setSelectionMode(0);
            newStock.item_margin = 1;
            newStock.centerItems = true;

            var i = 0;
            var resources = {'livestock': 11, 'coal': 8, 'wood': 9, 'steel': 3};
            for(var resourceName in resources){
                var imagePosition = resources[resourceName];
                var hash = this.hashString(resourceName);
                newStock.addItemType( hash, i, g_gamethemeurl+'img/tokens.png', imagePosition );
            }

            this[stockName] = newStock;

            dojo.connect( this[stockName], 'onChangeSelection', this, 'onResourceSelected' );
        },

        createAssetTileStock: function(capitaAssets){
            var newStock = new ebg.stock();

            newStock.create( this, $('asset_track'), 50, 50);

            // Specify that there are 5 buildings per row
            newStock.image_items_per_row = 5;
            newStock.setSelectionMode(0);
            newStock.item_margin=5.5;

            var i = 0;
            for(var assetName in capitaAssets){
                var hash = this.hashString(assetName);

                var imagePosition = this.assetNameToImagePosition[assetName];
                newStock.addItemType( hash, i, g_gamethemeurl+'img/buildings_small.png', imagePosition );

                i++;
            }

            this.capital_assets = newStock;
        },

        placeManager: function(manager, slideFromSupply = false){
            var tokenId = 'manager_' + manager.card_id;
            var holder = manager.card_location + '_manager_holder';
            dojo.place( this.format_block( 'jstpl_token', {
                token_id: tokenId, 
                token_class: 'moveable-token meeple meeple-tan'
            } ), holder);
            
            if(slideFromSupply){
                this.placeOnObject(tokenId, 'main_board');
                this.slideToObject(tokenId, holder).play();
            }
        },

        placeResource: function(item, from, fromItemDiv){
            var resourceName = item.card_type;
            var hash = this.hashString(resourceName);
            switch(item.card_location){
                case 'haymarket':
                    this.haymarket.addToStockWithId(hash, item.card_id);
                    break;
                case 'x':
                    this.supply_x.addToStockWithId(hash, item.card_id);
                    break;
                case '30':
                    this.supply_30.addToStockWithId(hash, item.card_id);
                    break;
                case '20':
                    this.supply_20.addToStockWithId(hash, item.card_id);
                    break;
                case '10':
                    this.supply_10.addToStockWithId(hash, item.card_id);
                    break;
                default:
                    // this is going in a company (card_location = company short name)
                    this[item.card_location + '_resources'].addToStockWithId(hash, item.card_id, fromItemDiv);
                    if(from != null){
                        this['supply_' + from].removeFromStockById(item.card_id);
                    }
                    break;
            }
        },

        placeAsset: function(item){
            var assetName = item.card_type;
            var hash = this.hashString(assetName);
            this.capital_assets.addToStockWithId(hash, assetName);
            return hash;
        },

        placeSalesperson: function(salesperson, slideFromSupply = false){
            var tokenId = 'salesperson_' + salesperson.card_id;
            var companyShortName = salesperson.card_location;
            
            originalSpot = companyShortName + '_salesperson_holder';
            if(slideFromSupply){
                originalSpot = 'main_board';
            }

            dojo.place( this.format_block( 'jstpl_token', {
                token_id: tokenId, 
                token_class: 'moveable-token meeple meeple-black'
            } ), originalSpot);
            
            this[companyShortName + '_salesperson_holder'].placeInZone(tokenId);
        },

        companyHasSpaceForSalesperson: function(companyShortName){
            var currentNumberSalersperon = this[companyShortName+'_salesperson_holder'].getAllItems.length;
            var company = this.gamedatas.all_companies[companyShortName];
            return company.salesperson_number > currentNumberSalersperon;
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
                id: building,
                class: 'worker_spot building-action'
            } ) , item_div );

            // create the partner zone
            dojo.place( this.format_block( 'jstpl_generic_div', {
                id: building + '_holder',
                class: ''
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
                newStock.addItemType( hash, i, g_gamethemeurl+'img/buildings_small.png', imagePosition );

                i++;
            }
            
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
                    id: tokenId,
                    short_name: company.short_name
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
        },

        updateAppealTokens: function(appeal, company_order){
            var count = company_order.length;

            this['appeal_zone_'+appeal].removeAll();
            
            for(var i in company_order){
                var company = company_order[i];
                if(company.appeal != appeal)
                    continue;
                var weight = count - company.order;
                this['appeal_zone_'+company.appeal].placeInZone('appeal_token_'+company.short_name, weight);
                dojo.setStyle('appeal_token_'+company.short_name, 'z-index', weight);
            }
        },

        placeShareValue: function(share_value_step, short_name, playerId){
            dojo.place( this.format_block( 'jstpl_share_token', {
                short_name: short_name
            } ) , 'main_board' );

            this.placeOnObject( 'share_token_'+short_name, 'overall_player_board_'+playerId );
            this['share_zone_'+share_value_step].placeInZone('share_token_'+short_name);
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
            zone.create( this, 'job_market', 12, 27 );
            zone.setPattern('custom');
            this.job_market = zone;
            this.job_market.itemIdToCoords = function( i, control_width ) {
                if( i%12==0 )
                {   return {  x:9,y:0, w:12, h:27 }; }
                else if( i%12==1 )
                {   return {  x:38,y:0, w:12, h:27 }; }
                else if( i%12==2 )
                {   return {  x:9,y:27, w:12, h:27 }; }
                else if( i%12==3 )
                {   return {  x:38,y:27, w:12, h:27 }; }
                else if( i%12==4 )
                {   return {  x:9,y:59, w:12, h:27 }; }
                else if( i%12==5 )
                {   return {  x:38,y:59, w:12, h:27 }; }
                else if( i%12==6 )
                {   return {  x:9,y:86, w:12, h:27 }; }
                else if( i%12==7 )
                {   return {  x:38,y:86, w:12, h:27 }; }
                else if( i%12==8 )
                {   return {  x:9,y:118, w:12, h:27 }; }
                else if( i%12==9 )
                {   return {  x:38,y:118, w:12, h:27 }; }
                else if( i%12==10 )
                {   return {  x:9,y:145, w:12, h:27 }; }
                else if( i%12==11 )
                {   return {  x:38,y:145, w:12, h:27 }; }
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
                        this.placeManager(item);
                        break;
                    case 'salesperson':
                        this.placeSalesperson(item);
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
                }
            }

            this.capital_assets.changeItemsWeight( newWeights ) // { 1: 10, 2: 20, itemType: Weight }

            for(var i = 0; i < companyWorkers.length; i++){
                this.placeWorkerInFactory(companyWorkers[i]);
            }
        },

        placeWorker: function(worker){

            // never more than 12 workers in the market
            if(this.job_market.getItemNumber() == 12)
                return;

            var itemId = worker.card_type + '_' + worker.card_id;

            if(!dojo.byId(itemId)){
                dojo.place( this.format_block( 'jstpl_token', {
                    token_id: itemId, 
                    token_class: 'worker'
                } ), worker.card_location );
            }

            this.job_market.placeInZone(itemId);
        },

        placeBuilding: function(building, from){
            var hashBuildingType = this.hashString(building.card_type);
            var itemId = building.card_type+'_'+building.card_id;

            var location = building.card_location; // building_track_2319929 or player_2319930 or player_2319930_play

            if (location.indexOf('building_track') !== -1){
                this[location].addToStockWithId(hashBuildingType, itemId, from);
                return;
            }

            this.buildings.addToStockWithId(hashBuildingType, itemId, from);

            if(location.indexOf('_play') !== -1){
                var div = this.buildings.getItemDivId(itemId);
                dojo.addClass(div, "building_to_play");
            } else if(location.indexOf('_discard') !== -1){
                var div = this.buildings.getItemDivId(itemId);
                dojo.addClass(div, "building_to_discard");
            }

        },

        placeStock: function(stock, from){
            var stockType = stock.card_type;
            
            if(stock.owner_type == 'player'){
                var hashStockType = this.hashString(stockType);
                this[stock.card_location].addToStockWithId(hashStockType, stockType+'_'+stock.card_id, from);
            } else if (stock.owner_type == 'company') {
                var hashStockType = this.hashString(stockType);
                this.available_shares_company.addToStockWithId(hashStockType, stockType+'_'+stock.card_id, from);
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

        setupCompany: function(company_div, company_type_id, item_id){
            // Add some custom HTML content INSIDE the Stock item:
            // company_div_id looks like this : company_area_2319930_item_libby or available_companies_item_libby

            var array = item_id.split('_');
            var companyShortName = array[array.length - 1];
            var companyId = 'company_' + companyShortName;
            dojo.place( this.format_block( 'jstpl_company_content', {
                id: companyId,
                short_name: companyShortName
            } ), company_div.id );

            var company = this.gamedatas.all_companies[companyShortName];

            dojo.addClass(company_div, 'company');
            dojo.connect( $(company_div), 'onclick', this, 'onCompanyClicked' );
            
            var factoryWidth = companyShortName == 'henderson' ? 93 : 97;
            var distanceToLastAutomation = companyShortName == 'henderson' ? 76 : 80;
            for(var factoryNumber in company.factories){
                var factory = company.factories[factoryNumber];
                
                var factoryId = companyShortName + '_factory_' + factoryNumber;
                dojo.place( this.format_block( 'jstpl_factory', {
                    id: factoryId,
                    left: 65+factoryWidth*(factoryNumber-1), // left of first factory + factory width * factory #
                    width: factoryWidth
                } ), companyId );

                dojo.connect( $(factoryId), 'onclick', this, 'onFactoryClicked' );

                // add automation token spots
                var numberOfAutomations = factory.automation;
                for(var i = 0; i < numberOfAutomations; i++){
                    dojo.place( this.format_block( 'jstpl_automation_holder', {
                        short_name: companyShortName,
                        factory: factoryNumber,
                        number: i,
                        left: distanceToLastAutomation-20*(numberOfAutomations-i-1) // left of first factory + factory width * factory # + left of last automation - distance between automation
                    } ), factoryId );
                }

                // add worker spots
                var numberOfWorkers = factory.workers;
                var initialWorkerLeft = 43;
                if(numberOfWorkers == 2){
                    initialWorkerLeft = 29;
                } else if (numberOfWorkers == 3){
                    initialWorkerLeft = 15;
                }
                for(var i = 0; i < numberOfWorkers; i++){
                    dojo.place( this.format_block( 'jstpl_worker_holder', {
                        id: companyShortName+'_'+factoryNumber+'_worker_holder_'+i,
                        left: initialWorkerLeft+27*i // left of first factory + factory width * factory # + left of last automation - distance between automation
                    } ), factoryId );
                }

                // add manager spots
                dojo.place( this.format_block( 'jstpl_manager_holder', {
                    id: companyShortName+'_'+factoryNumber+'_manager_holder'
                } ), factoryId );
            }

            // add salesperson spots

            dojo.place( this.format_block( 'jstpl_salesperson_holder', {
                id: companyShortName+'_salesperson_holder',
                top: 89
            } ), companyId );

            var zone = new ebg.zone();
            zone.create( this, companyShortName + '_salesperson_holder', 15, 15 );
            zone.setPattern( 'custom' );
            zone.itemIdToCoords = function( i, control_width ) {
                return {x:0, y: 33*i, w:15, h:15}
            };
            this[companyShortName + '_salesperson_holder'] = zone;

            // add resource stock
            dojo.place( this.format_block( 'jstpl_generic_div', {
                id: companyShortName + '_resources',
                class: 'company-resources'
            } ), companyId );
            this.createResourceStock(companyShortName + '_resources');

            // add goods zone
            dojo.place( this.format_block( 'jstpl_generic_div', {
                id: companyShortName+'_goods',
                class: 'goods-holder'
            } ), companyId );
            
            var zone = new ebg.zone();
            zone.create( this, companyShortName + '_goods', 13, 22 );
            this[companyShortName + '_goods'] = zone;
        },

        placeGood: function(good, from){

            var companyShortName = good.card_location.split('_')[0]; // brunswick_goods
            if(from == 'supply'){
                // good produced -> create good
                dojo.place( this.format_block( 'jstpl_generic_div', {
                    id: 'good_' + good.card_id, 
                    class: 'good_token'
                } ), 'main_board' );

                this[companyShortName + '_goods'].placeInZone('good_' + good.card_id);

            } else if (from == 'company'){
                // good exists -> move it
            } else {
                // page refresh -> create good
                dojo.place( this.format_block( 'jstpl_generic_div', {
                    id: 'good_' + good.card_id, 
                    class: 'good_token'
                } ), companyShortName + '_goods' );

                this[companyShortName + '_goods'].placeInZone('good_' + good.card_id);
            }
        },

        placeCompany: function(company, from){
            var hash = this.hashString(company.short_name);
            this['companyArea'+company.owner_id].addToStockWithId(hash, company.short_name, from);
            this.placeShareValue(company.share_value_step, company.short_name, company.owner_id);
        },

        placePartner: function(partner){
            var partnerId = partner.card_type; // worker_{playerId}_{workerNumber}
            
            if(!dojo.byId(partnerId)){
                var playerId = partnerId.split('_')[1];
                var playerColor = this.gamedatas.players[playerId].color;
                dojo.place( this.format_block( 'jstpl_token', {
                    token_id: partnerId, 
                    token_class: 'moveable-token meeple meeple-'+playerColor
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
                        token_id: 'automation_' + automation.card_id,
                        token_class: 'automation_token'
                    } ), workerSpotId );
                }
            }
            else{
                // TODO: fill from right to left and disregard location
                var automationSpotId = this.getNextAvailableAutomationSpot(companyShortName, factoryNumber);
                dojo.place( this.format_block( 'jstpl_token', {
                    token_id: 'automation_' + automation.card_id,
                    token_class: 'automation_token'
                } ), automationSpotId );
            }
        },

        placeWorkerInFactory: function(worker, from, fromId){
            var tokenId = 'worker_' + worker.card_id;
            var workerSpotId = this.getNextAvailableWorkerSpot(worker.card_location);
            if(from == 'market'){
                this.job_market.removeFromZone(tokenId);
                dojo.place( this.format_block( 'jstpl_token', {
                    token_id: tokenId,
                    token_class: 'worker'
                } ), workerSpotId );
                this.placeOnObject( tokenId, 'job_market');
                this.slideToObject( tokenId, workerSpotId ).play();
            } else if (from == 'supply'){
                // some buildings allow hiring workers from the supply
            } else if (from == 'factory'){
                // happens when a worker is relocated (it already exists)
                dojo.place(tokenId, workerSpotId);
                this.placeOnObject(tokenId, fromId);
                this.slideToObject(tokenId, workerSpotId).play();
            } else {
                // just place worker directly
                dojo.place( this.format_block( 'jstpl_token', {
                    token_id: tokenId,
                    token_class: 'worker'
                } ), workerSpotId );
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
                newStock.addItemType( hash, i, g_gamethemeurl+'img/all_companies_small.png', imagePosition );

                i++;
            }
        },

        createShareStock: function(allCompanies, location){
            var newStock = new ebg.stock();
            newStock.create( this, $(location), 109, 73);
            this[location] = newStock;
            newStock.image_items_per_row = 3;
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
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        onCompanyClicked: function(event){
            var state = this.gamedatas.gamestate.name;
            if(state != 'client_actionChooseCompany')
                return;

            dojo.stopEvent(event);

            if(!this.checkAction('buildingAction'))
                return;
            
            var companyTargetId = event.currentTarget.id; // company_area_2319930_item_swift
            if(!dojo.hasClass(companyTargetId, "active"))
                return;
            
            var split = companyTargetId.split('_');
            var companyShortName = split[4];

            // deselect companies other than the current one
            dojo.query('#player_'+this.player_id+' .company').removeClass('active');
            dojo.addClass(companyTargetId, 'active');

            var actionOk = this.executeActionForCompany(companyShortName);

            if(!actionOk){
                // reactivate all companies
                dojo.query('#player_'+this.player_id+' .company').addClass('active');
                return;
            } 
        },

        onFactoryClicked: function(event){

            var state = this.gamedatas.gamestate.name;
            if(state != 'client_actionChooseFactory' && state != 'client_chooseFactoryRelocate')
                return;

            dojo.stopEvent(event);

            if(!this.checkAction('buildingAction'))
                return;

            var factoryTargetId = event.currentTarget.id;
            if(!dojo.hasClass(factoryTargetId, "active"))
                return;
            
            var split = factoryTargetId.split('_'); // brunswick_factory_2
            var companyShortName = split[0];
            var factoryNumber = split[2];

            // this is a special state that only happens when workers are automated
            // once we know where to put the automated worker, we can confirm the action
            if(state == 'client_chooseFactoryRelocate'){
                this.clientStateArgs.actionArgs.relocateFactoryNumber = factoryNumber;
                this.onConfirmAction();
                return;
            }

            // deselect factories other than the current one
            var factorySelector = '#'+factoryTargetId;
            dojo.query('#player_'+this.player_id+' .factory').removeClass('active');
            dojo.query(factorySelector).addClass('active');
            
            var actionOk = this.executeActionForCompany(companyShortName, factoryNumber);

            if(!actionOk){
                // reactivate all factories
                dojo.query('#player_'+this.player_id+' .factory').addClass('active');
                return;
            }
        },

        executeActionForCompany(companyShortName, factoryNumber){
            
            var actionOk = true;
            var message = "";

            var buildingMaterial = this.gamedatas.all_buildings[this.clientStateArgs.buildingAction] ||
                this.gamedatas.general_action_spaces[this.clientStateArgs.buildingAction];
            if(!buildingMaterial){
                this.showMessage( _("Could not find associated building"), 'error' );
            }

            var cost = buildingMaterial.cost;
            var buildingAction = this.clientStateArgs.buildingAction;

            // make checks specific to action and get cost of action
            switch(buildingAction){
                case "job_market_worker":
                    // check if there are empty worker spots
                    var emptyWorkerSpots = this.getEmptyWorkerSpotsInFactory(companyShortName, factoryNumber);
                    if(emptyWorkerSpots == 0){
                        actionOk = false;
                        message = _("This factory doesn't have any more space for workers");
                        break;
                    }
                    cost = this.getCostOfWorkers(1);
                    break;
                case "capital_investment":
                    break;
                case "hire_manager":
                case "building11":
                case "building14":
                    if(this.doesFactoryEmployManager(companyShortName, factoryNumber)){
                        actionOk = false;
                        message = _("This factory already employs a manager");
                    }
                case "hire_salesperson":
                case "building2":
                case "building22":
                    if(!this.companyHasSpaceForSalesperson(companyShortName)){
                        actionOk = false;
                        message = _("This company cannot hire any more salesperson");
                    }
                    break;
                case "building6":
                case "building24":
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

            if(actionOk){

                // save client state args
                this.clientStateArgs.factoryNumber = factoryNumber;
                this.clientStateArgs.companyShortName = companyShortName;
                
                // execute action specific stuff
                switch(buildingAction){
                    case 'job_market_worker':
                        this.clientStateArgs.actionArgs.numberOfWorkersToBuy = 1;
                        this.setClientState("client_chooseNumberOfWorkers", {
                            descriptionmyturn : dojo.string.substitute(_('Hire 1 worker for $${cost}'),{
                                cost: cost,
                            })
                        });
                        break;
                    case 'building6':
                    case "building24":
                        this.automateWorker(companyShortName, factoryNumber);
                        break;
                    default:
                        this.onConfirmAction();
                        break;
                }
            } else {
                this.showMessage( message, 'info' );
            }

            return actionOk;
        },

        checkCompanyMoney(companyShortName, cost){
            var counterName = 'money_'+companyShortName;
            var companyTreasury = this.gamedatas.counters[counterName]['counter_value'];
            if(companyTreasury >= cost)
                return true;
            return false;
        },

        getCostOfWorkers(numberOfWorkers){
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

        getEmptyWorkerSpotsInFactory(companyShortName, factoryNumber){
            var company = this.gamedatas.all_companies[companyShortName];
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var workerHolderChilds = dojo.query(factorySelector + '>.worker-holder>');
            var numberOfWorkers = company.factories[factoryNumber].workers;
            return numberOfWorkers - workerHolderChilds.length;
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
            
            if(workerNumber == 0)
                return;
            
            var workerId = 'worker_'+playerId+'_'+workerNumber;

            // TODO: check if spot can be used multiple times
            
            // create worker
            var target = event.currentTarget;
            this.placePartner({
                card_type: workerId,
                card_location: target.id
            });

            // update meeple counter for current player (not reflected on the server yet)
            dojo.byId(counterName).textContent = workerNumber - 1;

            // remove highlight from worker spots
            dojo.query('.worker_spot').removeClass('active');

            this.clientStateArgs.buildingAction = target.id;
            this.clientStateArgs.workerId = workerId;
            
            switch(target.id){
                case "job_market_worker":
                case "hire_manager":
                case "building6":
                case "building11":
                case "building14":
                case "building44":
                    this.chooseFactory();
                    break;
                case "building21":
                case "building25":
                case "building26":
                case "building35":
                case "building42":
                case "building43":
                    // double actions
                    break;
                default:
                    this.chooseCompany();
                    break;
            }
        },

        onCancelSelectBuildings: function(event){
            dojo.stopEvent(event);
            dojo.query('.building_to_play').removeClass('building_to_play');
            dojo.query('.building_to_discard').removeClass('building_to_discard');
            this.restoreServerGameState();
        },

        onCancelAction: function(event){
            // destroy meeple
            var workerId = this.clientStateArgs.workerId;
            var buildingAction = this.clientStateArgs.buildingAction;
            this[buildingAction + '_holder'].removeFromZone(workerId, true);

            // reset counters with server-side counters
            this.updateCounters(this.gamedatas.counters);

            // make factories and companies inactive
            dojo.query('.factory').removeClass('active');
            dojo.query('.company').removeClass('active');

            // execute undo
            if (typeof this.undo === "function") { this.undo(); this.undo = undefined; }

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

        onPersonalShareSelected: function(control_name, item_id){
            if(!this.checkAction('sellShares'))
            {
                return;
            }
            
            var playerId = this.player_id;
            var numberOfItems = this['personal_area_'+playerId].getSelectedItems().length;
            
            this.setClientState("client_playerTurnConfirmSellShares", {
                descriptionmyturn : dojo.string.substitute(_('Sell ${numberOfCertificates} certificates'),{
                    numberOfCertificates: numberOfItems
                })
            });
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

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/buyResources.html", {
                resourceIds: resourceIds.join(",")
            }, this, function( result ) {} );
        },

        onSkipBuyResources: function(){
            if(!this.checkAction('buyResources'))
            {
                return;
            }
            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/skipBuyResources.html", {
            }, this, function( result ) {} );
        },

        onAvailableShareSelected: function(control_name, item_id){
            if(!this.checkAction('buyCertificate'))
            {
                return;
            }

            var items = this.available_shares_company.getSelectedItems();

            if(items.length == 0){
                this.restoreServerGameState();
                return;
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

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/selectBuildings.html", {
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
                if(gamestate.name == 'playerSkipSellBuyPhase' || gamestate.name == 'playerBuyPhase')
                {
                    if(gamestate.args.round == 0){
                        this.showMessage( _('You cannot start a new company during the first decade'), 'info' );
                        this.available_companies.unselectAll();
                        return;
                    }
                }

                this.available_shares_company.unselectAll();
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

                this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/startCompany.html", {
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

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/sellShares.html", {
                selected_shares: ids.join(';') 
            }, this, function( result ) {} );
        },

        onSkipBuy: function(){
            if(!this.checkAction('buyCertificate'))
            {
                return;
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/buyCertificate.html", {
                certificate: ''
            }, this, function( result ) {} );
        },

        onSkipSell: function(){
            if(!this.checkAction('sellShares'))
            {
                return;
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/skipSell.html", {}, this, function( result ) {} );
        },

        onConfirmBuy: function(){
            if(!this.checkAction('buyCertificate'))
            {
                return;
            }

            var selectedShares = this['available_shares_company'].getSelectedItems();
            if(selectedShares.length == 0)
            {
                this.showMessage( _('You must select at least one certificate to buy'), 'info' );
                return;
            }

            var item = selectedShares[0];

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/buyCertificate.html", {
                certificate: item.id
            }, this, function( result ) {} );
        },

        onStockPass: function(){
            if(!this.checkAction('passStockAction'))
            {
                return;
            }

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/passStockAction.html", {}, this, function( result ) {} );
        },

        onAddWorker: function(){
            var numberOfWorkersToBuy = this.clientStateArgs.actionArgs.numberOfWorkersToBuy;
            var factoryNumber = this.clientStateArgs.factoryNumber;
            var companyShortName = this.clientStateArgs.companyShortName;
            var numberOfEmptySpots = this.getEmptyWorkerSpotsInFactory(companyShortName, factoryNumber);
            
            if(numberOfWorkersToBuy == numberOfEmptySpots)
                return;

            numberOfWorkersToBuy++;
            this.clientStateArgs.actionArgs.numberOfWorkersToBuy = numberOfWorkersToBuy;

            var companyShortName = this.clientStateArgs.companyShortName;
            var cost = this.getCostOfWorkers(numberOfWorkersToBuy);
            if(!this.checkCompanyMoney(companyShortName, cost)){
                this.showMessage( _("Not enough money to hire more workers"), 'info' );
                return;
            }

            this.setClientState("client_chooseNumberOfWorkers", {
                descriptionmyturn : dojo.string.substitute(_('Hire ${numberOfWorkers} worker for $${cost}'),{
                    cost: cost,
                    numberOfWorkers: numberOfWorkersToBuy
                })
            });
        },

        onRemoveWorker: function(){
            var numberOfWorkersToBuy = this.clientStateArgs.actionArgs.numberOfWorkersToBuy;
            if(numberOfWorkersToBuy == 1)
                return;

            numberOfWorkersToBuy--;
            this.clientStateArgs.actionArgs.numberOfWorkersToBuy = numberOfWorkersToBuy;
            
            var cost = this.getCostOfWorkers(numberOfWorkersToBuy);

            this.setClientState("client_chooseNumberOfWorkers", {
                descriptionmyturn : dojo.string.substitute(_('Hire ${numberOfWorkers} worker for $${cost}'),{
                    cost: cost,
                    numberOfWorkers: numberOfWorkersToBuy
                })
            });
        },

        onConfirmAction: function(){
            if(!this.checkAction('buildingAction'))
                return;
            
            var args = [];
            for(var property in this.clientStateArgs.actionArgs){
                var arg = this.clientStateArgs.actionArgs[property];
                args.push(arg);
            }
            var actionArgs = args.join(",");

            this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/buildingAction.html", {
                buildingAction: this.clientStateArgs.buildingAction,
                companyShortName: this.clientStateArgs.companyShortName,
                workerId: this.clientStateArgs.workerId,
                factoryNumber: this.clientStateArgs.factoryNumber,
                actionArgs: actionArgs
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

            dojo.subscribe( 'certificateBought', this, "notif_certificateBought" );
            this.notifqueue.setSynchronous( 'certificateBought', 500 );

            dojo.subscribe ('buildingsSelected', this, "notif_buildingsSelected");
            this.notifqueue.setSynchronous( 'notif_buildingsSelected', 500 );

            dojo.subscribe ('workersAdded', this, "notif_workersAdded");
            this.notifqueue.setSynchronous( 'notif_workersAdded', 500 );

            dojo.subscribe('workersHired', this, "notif_workersHired");
            this.notifqueue.setSynchronous('notif_workersHired', 500);

            dojo.subscribe('actionUsed', this, "notif_actionUsed");
            this.notifqueue.setSynchronous('notif_actionUsed', 500);

            dojo.subscribe('countersUpdated', this, "notif_countersUpdated");
            this.notifqueue.setSynchronous('notif_countersUpdated', 500);

            dojo.subscribe('appealIncreased', this, "notif_appealIncreased");
            this.notifqueue.setSynchronous('notif_appealIncreased', 500);

            dojo.subscribe('managerHired', this, "notif_managerHired");
            this.notifqueue.setSynchronous('notif_managerHired', 500);

            dojo.subscribe('salespersonHired', this, "notif_salespersonHired");
            this.notifqueue.setSynchronous('notif_salespersonHired', 500);

            dojo.subscribe('dividendEarned', this, "notif_dividendEarned");
            this.notifqueue.setSynchronous('notif_dividendEarned', 500);

            dojo.subscribe('shareValueChange', this, "notif_shareValueChange");
            this.notifqueue.setSynchronous('notif_shareValueChange', 500);

            dojo.subscribe('goodsProduced', this, "notif_goodsProduced");
            this.notifqueue.setSynchronous('notif_goodsProduced', 500);

            dojo.subscribe('factoryAutomated', this, "notif_factoryAutomated");
            this.notifqueue.setSynchronous('notif_factoryAutomated', 500);

            dojo.subscribe('resourcesBought', this, "notif_resourcesBought");
            this.notifqueue.setSynchronous('notif_resourcesBought', 500);
        },

        notif_resourcesBought: function(notif){

            for(var resourceId in notif.args.resource_ids){
                var resource = notif.args.resource_ids[resourceId];
                var from = this['supply_' + resource.from].getItemDivId(resource.card_id);
                this.placeResource(resource, resource.from, from);
            }

            this.updateCounters(notif.args.counters);
        },

        notif_factoryAutomated: function(notif){
            var companyShortName = notif.args.company_short_name;
            var factoryNumber = notif.args.factory_number;
            
            var factorySelector = '#'+companyShortName+'_factory_'+factoryNumber; //#brunswick_factory_2
            var worker = null;
            var automation = null;

            if(this.isCurrentPlayerActive()){
                // the worker has been moved to the automation spot
                // we need to send it to its final destination (either job market or factory)
                worker = dojo.query(factorySelector + '>.automation_holder>.worker')[0];

                // automation is already in the right place, so nothing to do
            } else {
                // worker is in a worker spot => take the first one
                worker = dojo.query(factorySelector + '>.worker-holder>.worker')[0];
                automation = dojo.query(factorySelector + '>.automation_holder>.automation_token')[0];
            }

            var item = {
                card_id: worker.id.split('_')[1],
                card_type: 'worker',
                card_location: notif.args.worker_relocation};

            if(notif.args.worker_relocation == 'job_market'){
                this.placeWorker(item)
            } else {
                this.placeWorkerInFactory(item, 'factory', worker.parentNode.id)
            }

            if(automation != null){
                // when current player is not active, we need to move the automation token
                // which hasn't already been moved
                var item = {
                    card_id: automation.id.split('_')[1],
                    card_type: 'automation',
                    card_location: companyShortName + "_worker_holder_" + factoryNumber};
                this.placeAutomationToken(item, automation.parentNode.id);
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
            this['share_zone_'+notif.args.previous_share_value_step].removeFromZone('share_token_'+shortName);
            this['share_zone_'+notif.args.share_value_step].placeInZone('share_token_'+shortName);
        },

        notif_dividendEarned: function(notif){
            this.updateCounters(notif.args.counters);
        },

        notif_salespersonHired: function(notif){
            this.placeSalesperson({
                card_id: notif.args.salesperson_id,
                card_location: notif.args.location
            }, true);
        },

        notif_managerHired: function(notif){
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
        },

        notif_workersHired: function(notif){
            for(var index in notif.args.worker_ids){
                workerId = notif.args.worker_ids[index];
                this.placeWorkerInFactory({
                    card_id: workerId,
                    card_location: notif.args.factory_id
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
            var automationTokens = notif.args.automation_tokens;
            var directorStockId = notif.args.director_stock_id;
            var stocks = notif.args.stocks;

            this.placeCompany({
                    short_name: shortName,
                    owner_id: playerId,
                    share_value_step: initialShareValueStep,
                }, 'available_companies');
            this.available_companies.removeFromStockById(shortName);

            for(var index in automationTokens){
                var automation = automationTokens[index];
                this.placeAutomationToken(automation);
            }

            for(var index in stocks)
            {
                var stock = stocks[index];
                this.placeStock(stock, 'available_companies');
            }

            this.placeAppealToken({
                short_name: shortName,
                appeal: appeal,
                owner_id: playerId
            });

            this.updateAppealTokens(appeal, notif.args.order);

            this.updateCounters(notif.args.counters);
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

        notif_buildingsSelected: function(notif){
            var currentPlayerId = this.player_id;
            var currentPlayerBuilding = null;
            for(var i in notif.args.buildings){
                var building = notif.args.buildings[i];
                
                var split = building.card_location.split('_'); // pattern building_track_playerId
                var playerId = split[2];

                if(playerId != currentPlayerId)
                    this.placeBuilding(building, 'building_area_'+playerId);
                else
                    currentPlayerBuilding = building;
            }

            var items = this.buildings.getAllItems();
            for(var index in items){
                var item = items[index];
                var divId = this.buildings.getItemDivId(item.id);
                if(dojo.hasClass(divId, 'building_to_discard')){
                    // remove discarded building
                    this.buildings.removeFromStockById(item.id);
                } else if (dojo.hasClass(divId, 'building_to_play')){
                    // remove building that was moved to the building track
                    this.placeBuilding(currentPlayerBuilding, divId);
                    this.buildings.removeFromStockById(item.id);
                }
            }
        }
   });             
});
