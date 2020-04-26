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
    "ebg/stock"
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
                
                // create player stocks
                this.createCompaniesStock(gamedatas.all_companies, player_id);
                
                // TODO: Setting up players boards if needed
                var player_board_div = $('player_board_'+player_id);
                dojo.place( this.format_block('jstpl_player_board', player ), player_board_div );
            }

            this.updateCounters(gamedatas.counters);

            // create available companies stock
            this.createCompaniesStock(gamedatas.all_companies);
            dojo.connect( this.availableCompanies, 'onChangeSelection', this, 'onCompanySelected' );
            
            for(var i in gamedatas.owned_companies){
                var ownedCompany = gamedatas.owned_companies[i];

                var company = gamedatas.all_companies[ownedCompany.short_name];
                company.inPlay = true;
                company.owner_id = ownedCompany.owner_id;
            }

            // TODO: Set up your game interface here, according to "gamedatas"
            for(var property in gamedatas.all_companies){
                var company = gamedatas.all_companies[property];
                this.placeCompany(company.short_name, company.owner_id, company.inPlay);
            }
 
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
                        this.availableCompanies.setSelectionMode(1);
                    } else {
                        this.availableCompanies.setSelectionMode(0);
                    }
                    break;
                case 'client_playerTurnSelectStartingShareValue':
                    this.availableCompanies.setSelectionMode(1);
                    break;
                case 'gameStartFirstCompany':
                    this.availableCompanies.setSelectionMode(0);
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
                case 'client_playerTurnSelectStartingShareValue':
                    //this.availableCompanies.setSelectionMode(0);
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
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

        placeCompany: function(short_name, owner_id, inPlay){
            var hash = this.hashString(short_name);
            if(inPlay){
                this['companyArea'+owner_id].addToStockWithId(hash, short_name);
            } else {
                this.availableCompanies.addToStockWithId(hash, short_name);
            }
        },

        createCompaniesStock: function(allCompanies, playerId){
            var newStock = new ebg.stock();
            var id;
            var propertyName;
            if(playerId != null){
                propertyName = 'companyArea'+playerId;
                id = 'company_area_'+playerId;
            } else {
                propertyName = 'availableCompanies';
                id = 'available_companies';
                newStock.centerItems = true;
            }

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

            return newStock;
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

            var items = this.availableCompanies.getSelectedItems();
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

            var items = this.availableCompanies.getSelectedItems();
            if(items.length == 1){
                var companyShortName = items[0].id;

                this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/startCompany.html", {
                    company_short_name: companyShortName,
                    initialShareValueStep: initialShareValueStep //can be 4,5,6,7 for $35,$40,$50,$60
                }, this, function( result ) {} );
            }
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
            var playerId = notif.args.owner_id;
            var hash = this.hashString(shortName);

            this['companyArea'+playerId].addToStockWithId(hash, shortName, 'available_companies');
            this.availableCompanies.removeFromStockById(shortName);

            debugger;
            this.updateCounters(notif.args.counters);
        },
   });             
});
