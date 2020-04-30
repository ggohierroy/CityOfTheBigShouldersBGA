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
  	
  	// TODO: defines your action entry points there


    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

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

  }
  

