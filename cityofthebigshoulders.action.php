<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * CityOfTheBigShoulders implementation : © Gabriel Gohier-Roy <ggohierroy@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * cityofthebigshoulders.action.php
 *
 * CityOfTheBigShoulders main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/cityofthebigshoulders/cityofthebigshoulders/myAction.html", ...)
 *
 */
  
  
  class action_cityofthebigshoulders extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "cityofthebigshoulders_cityofthebigshoulders";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 

    public function startCompany()
    {
      self::setAjaxMode();

      // Retrieve arguments
      // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
      $company_short_name = self::getArg( "company_short_name", AT_alphanum, true );
      $initial_share_value_step = self::getArg( "initialShareValueStep", AT_posint, true );

      // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
      $this->game->startCompany( $company_short_name, $initial_share_value_step );

      self::ajaxResponse( );
    }

    public function sellShares()
    {
      self::setAjaxMode();

      // Retrieve arguments
      // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
      $selected_shares_raw = self::getArg( "selected_shares", AT_numberlist, true );

      // Removing last ';' if exists
      if( substr( $selected_shares_raw, -1 ) == ';' )
          $selected_shares_raw = substr( $selected_shares_raw, 0, -1 );

      if( $selected_shares_raw == '' )
          $selected_shares = array();
      else
          $selected_shares = explode( ';', $selected_shares_raw );

      // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
      $this->game->sellShares( $selected_shares );

      self::ajaxResponse( );
    }

    public function skipBuy()
    {
      self::setAjaxMode();

      $this->game->skipBuy();

      self::ajaxResponse();
    }

    public function buyCertificate()
    {
      self::setAjaxMode();

      // Retrieve arguments
      // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
      $certificate = self::getArg( "certificate", AT_alphanum, true );

      // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
      $this->game->buyCertificate( $certificate );

      self::ajaxResponse( );
    }

    public function skipSell()
    {
      self::setAjaxMode();

      $this->game->skipSell();

      self::ajaxResponse();
    }

    public function passStockAction()
    {
      self::setAjaxMode();

      $this->game->passStockAction();

      self::ajaxResponse();
    }

    public function selectBuildings()
    {
      self::setAjaxMode();

      // Retrieve arguments
      // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
      $played_building_id = self::getArg( "playedBuildingId", AT_posint, true );
      $discarded_building_id = self::getArg( "discardedBuildingId", AT_posint, true );

      $this->game->selectBuildings( $played_building_id, $discarded_building_id );

      self::ajaxResponse( );
    }

    public function buildingAction()
    {
      self::setAjaxMode();

      // buildingAction: this.clientStateArgs.buildingAction,
      // companyShortName: this.clientStateArgs.companyShortName,
      // workerId: this.clientStateArgs.workerId,
      // factoryId: this.clientStateArgs.factoryId,
      // actionArgs: $actionArgs
      $building_action = self::getArg( "buildingAction", AT_alphanum, true );
      $company_short_name = self::getArg( "companyShortName", AT_alphanum, true );
      $factory_number = self::getArg( "factoryNumber", AT_posint, false );
      $action_args = self::getArg( "actionArgs", AT_numberlist, false );

      $this->game->buildingAction( $building_action, $company_short_name, $factory_number, $action_args );

      self::ajaxResponse( );
    }

    public function automateFactory()
    {
      self::setAjaxMode();

      $company_short_name = self::getArg( "companyShortName", AT_alphanum, true );
      $factory_number = self::getArg( "factoryNumber", AT_posint, true );
      $relocate_number = self::getArg( "relocateNumber", AT_posint, false );

      $this->game->automateFactory( $company_short_name, $factory_number, $relocate_number );

      self::ajaxResponse( );
    }

    public function tradeResources()
    {
      self::setAjaxMode();

      $haymarket_resource_id = self::getArg( "haymarketResourceId", AT_posint, true );
      $company_resource_id1 = self::getArg( "companyResourceId1", AT_posint, true );
      $company_resource_id2 = self::getArg( "companyResourceId2", AT_posint, true );

      $this->game->tradeResources( $haymarket_resource_id, $company_resource_id1, $company_resource_id2 );

      self::ajaxResponse( );
    }

    public function passFreeActions()
    {
      self::setAjaxMode();

      $this->game->passFreeActions();

      self::ajaxResponse( );
    }

    public function useAsset()
    {
      self::setAjaxMode();

      $asset_name = self::getArg( "assetName", AT_alphanum, true );

      $this->game->useAsset( $asset_name );

      self::ajaxResponse();
    }

    public function gainAppealBonus()
    {
      self::setAjaxMode();

      $factory_number = self::getArg( "factoryNumber", AT_posint, false );
      $relocate_number = self::getArg( "relocateFactoryNumber", AT_posint, false );

      $this->game->gainAppealBonus( $factory_number, $relocate_number );

      self::ajaxResponse( );
    }

    public function forfeitAppealBonus()
    {
      self::setAjaxMode();

      $this->game->forfeitAppealBonus();

      self::ajaxResponse( );
    }

    public function hireWorker()
    {
      self::setAjaxMode();

      $company_short_name = self::getArg( "companyShortName", AT_alphanum, true );
      $factory_number = self::getArg( "factoryNumber", AT_posint, true );

      $this->game->hireWorker( $company_short_name, $factory_number);

      self::ajaxResponse( );
    }

    public function skipAssetBonus()
    {
      self::setAjaxMode();

      $this->game->skipAssetBonus();

      self::ajaxResponse( );
    }

    public function buyResources()
    {
      self::setAjaxMode();
      $resource_ids = self::getArg( "resourceIds", AT_numberlist, true );
      $this->game->buyResources($resource_ids);
      self::ajaxResponse( );
    }

    public function managerBonusGainResources()
    {
      self::setAjaxMode();
      $resource_ids = self::getArg( "resourceIds", AT_numberlist, true );
      $this->game->managerBonusGainResources($resource_ids);
      self::ajaxResponse( );
    }

    public function skipBuyResources()
    {
      self::setAjaxMode();
      $this->game->skipBuyResources();
      self::ajaxResponse( );
    }

    public function produceGoods()
    {
      self::setAjaxMode();
      $this->game->produceGoods();
      self::ajaxResponse( );
    }

    public function distributeGoods()
    {
      self::setAjaxMode();
      $demand_ids = self::getArg( "demandIds", AT_numberlist, true );
      $this->game->distributeGoods($demand_ids);
      self::ajaxResponse( );
    }

    public function skipProduceGoods()
    {
      self::setAjaxMode();
      $this->game->skipProduceGoods();
      self::ajaxResponse( );
    }

    public function skipDistributeGoods()
    {
      self::setAjaxMode();
      $this->game->skipDistributeGoods();
      self::ajaxResponse( );
    }

    public function payDividends()
    {
      self::setAjaxMode();
      $this->game->payDividends();
      self::ajaxResponse( );
    }

    public function withhold()
    {
      self::setAjaxMode();
      $this->game->withhold();
      self::ajaxResponse( );
    }

    public function undo()
    {
      self::setAjaxMode();
      $this->game->undo();
      self::ajaxResponse( );
    }

  }
  

