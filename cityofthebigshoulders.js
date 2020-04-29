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
                this.createShareStock(gamedatas.all_companies, player_id);
                
                // TODO: Setting up players boards if needed
                var player_board_div = $('player_board_'+player_id);
                dojo.place( this.format_block('jstpl_player_board', player ), player_board_div );
            }

            // create share value zones
            this.createShareValueZones();
            this.createAppealZones();

            // create available companies stock
            this.createCompaniesStock(gamedatas.all_companies);
            dojo.connect( this.available_companies, 'onChangeSelection', this, 'onCompanySelected' );
            
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
                case 'client_playerStockPhaseSellShares':
                    var playerId = this.getActivePlayerId();
                    this['personal_area_'+playerId].setSelectionMode(2);
                    break;
            
            /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */
           
           
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
/*              
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                    case 'client_playerTurnSelectStartingShareValue':
                        this.addActionButton( 'initial_share_35', '$35', 'onStartCompany');
                        this.addActionButton( 'initial_share_40', '$40', 'onStartCompany');
                        this.addActionButton( 'initial_share_50', '$50', 'onStartCompany');
                        this.addActionButton( 'initial_share_60', '$60', 'onStartCompany');
                        break;
                    
                    case 'playerStockPhase':
                        this.addActionButton( 'sell_buy', _('Sell/Buy Shares'), 'onSellBuyShare');
                        if(args.round > 0){
                            this.addActionButton( 'start_company', _('Start Company'), 'onStockStartCompany');
                        }
                        this.addActionButton( 'stock_pass', _('Pass'), 'onStockPass');
                        break;
                    
                    case 'client_playerStockPhaseSellShares':
                        this.addActionButton( 'confirm_sell', _('Confirm'), 'onConfirmShareSell');
                        this.addActionButton( 'cancel_sell', _('Cancel'), 'onCancel');
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

        placeAppealTokens: function(company_order){
            var count = company_order.length;
            for(var i in company_order){
                var company = company_order[i];
                this.placeAppealToken(company, count - company.order);
            }
        },

        placeAppealToken: function(company, weight){
            dojo.place( this.format_block( 'jstpl_appeal_token', {
                short_name: company.short_name
            } ) , 'main_board' );

            this.placeOnObject( 'appeal_token_'+company.short_name, 'overall_player_board_'+company.owner_id );
            this['appeal_zone_'+company.appeal].placeInZone('appeal_token_'+company.short_name, weight);

            if(weight){
                dojo.setStyle('appeal_token_'+company.short_name, 'z-index', weight);
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

        placeItemsOnBoard: function(gamedatas){

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
                }

            //dojo.addClass("company_stock_holder_" + shortName, shortName + " preferred")

            }
        },

        placeStock: function(stock, from){
            var stockType = stock.card_type;
            
            if(stock.owner_type == 'player'){
                var hashStockType = this.hashString(stockType);
                this[stock.card_location].addToStockWithId(hashStockType, stockType+'_'+stock.card_id, from);
            } else if (stock.owner_type == 'company') {
                var typeInfo = stockType.split('_');
                dojo.place( this.format_block( 'jstpl_stock', {
                    short_name: typeInfo[0],
                    stock_type: typeInfo[1]
                } ) , stock.card_location );
            }
        },

        setupCompany: function(company_div, company_type_id, item_id){
            // Add some custom HTML content INSIDE the Stock item:
            // company_div_id looks like this : company_area_2319930_item_libby or available_companies_item_libby

            var array = item_id.split('_');
            var companyShortName = array[array.length - 1];
            dojo.place( this.format_block( 'jstpl_company_content', {
                short_name: companyShortName
            } ), company_div.id );

            var company = this.gamedatas.all_companies[companyShortName];
            
            var factoryWidth = companyShortName == 'henderson' ? 93 : 97;
            var distanceToLastAutomation = companyShortName == 'henderson' ? 76 : 80;
            for(var index in company.factories){
                var factory = company.factories[index];
                // add automation token spots
                var numberOfAutomations = factory['automation'];
                for(var i = 0; i < numberOfAutomations; i++){
                    dojo.place( this.format_block( 'automation_holder', {
                        short_name: companyShortName,
                        factory: index,
                        number: i,
                        left: 65+factoryWidth*(index-1)+distanceToLastAutomation-20*(numberOfAutomations-i-1) // left of first factory + factory width * factory # + left of last automation - distance between automation
                    } ), company_div.id );
                }

                // add worker spots

                // add manager spots
            }

            

            // add salesperson spots

            // add resource stock

            // add goods stock
        },

        placeCompany: function(company, from){
            var hash = this.hashString(company.short_name);
            this['companyArea'+company.owner_id].addToStockWithId(hash, company.short_name, from);
            this.placeShareValue(company.share_value_step, company.short_name, company.owner_id);
        },

        placeAutomationToken: function(automation){
            dojo.place( this.format_block( 'jstpl_automation_token', {
                card_type: automation.card_type,
            } ), automation.card_location );
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

        createShareStock: function(allCompanies, playerId){
            var newStock = new ebg.stock();
            propertyName = 'personal_area_'+playerId;
            id = 'personal_area_'+playerId;
            newStock.create( this, $(id), 109, 73);
            this[propertyName] = newStock;
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

        onCancel : function(event) {
            dojo.stopEvent(event);
            if (this.on_client_state) {
                this.restoreServerGameState();
            } else {
                this.ajaxAction('selectCancel', {});
            }
        },

        onCompanySelected: function(control_name, item_id){
            if(!this.checkAction('startCompany'))
            {
                return;
            }

            var items = this.available_companies.getSelectedItems();
            if(items.length == 1){
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
            var buttonName = event.target.id;
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

        onStockStartCompany: function(){

        },

        onStockPass: function(){

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
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 

            dojo.subscribe( 'startCompany', this, "notif_startCompany" );
            this.notifqueue.setSynchronous( 'startCompany', 500 );
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */

        notif_startCompany: function( notif )
        {
            var shortName = notif.args.short_name;
            var appeal = notif.args.appeal;
            var playerId = notif.args.owner_id;
            var initialShareValueStep = notif.args.initial_share_value_step;
            var automationTokens = notif.args.automation_tokens;
            var directorStockId = notif.args.director_stock_id;

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

            this.placeStock({
                card_type: shortName + "_director",
                card_location: "personal_area_" + playerId,
                owner_type: 'player',
                card_id: directorStockId
            }, 'available_companies')

            this.placeStock({
                card_type: shortName + "_preferred",
                card_location: "company_stock_holder_" + shortName,
                owner_type: 'company'
            });

            this.placeAppealToken({
                short_name: shortName,
                appeal: appeal,
                owner_id: playerId
            });

            this.updateAppealTokens(appeal, notif.args.order);

            this.updateCounters(notif.args.counters);
        },
   });             
});
