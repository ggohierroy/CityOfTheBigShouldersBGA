{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- CityOfTheBigShoulders implementation : © Gabriel Gohier-Roy <ggohierroy@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    cityofthebigshoulders_cityofthebigshoulders.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->

<!--<a href="#" id="start_company" class="bgabutton bgabutton_blue"><span>Start Company</span></a>-->

<div id="main_board_wrapper">
    <div id="main_board" class="center">
        <div id="share_track">
            <!-- BEGIN share_track -->
            <div id="share_zone_{ZONE_ID}" class="share_zone" style="top: {TOP}px; z-index: {Z_INDEX}"></div>
            <!-- END share_track -->
        </div>
        <div id="appeal_track">
            <!-- BEGIN appeal_track -->
            <div id="appeal_zone_{ZONE_ID}" class="appeal_zone" style="top: {TOP}px; z-index: {Z_INDEX}"></div>
            <!-- END appeal_track -->
        </div>
    </div>
    <div id="public_goals">public goals</div>
</div>

<div id=player_area_wrapper>
    <div id="building_area">my buildings</div>
    <!-- BEGIN player_area -->
    <div id="player_{PLAYER_ID}" class="clearfix">
        <h1>{PLAYER_ID}/{PLAYER_NAME}/{PLAYER_COLOR}</h1>
        <div id="personal_area_{PLAYER_ID}">personal area</div>
        <div id="company_area_wrapper">
            <h2>owned companies area</h2>
            <div id="company_area_{PLAYER_ID}" class="clearfix"></div>
        </div>
    </div>
    <!-- END player_area -->
</div>

<div id="companies_wrapper" class="clearfix">
    <h1>available companies</h1>
    <div id="companies" class="clearfix"></div>
</div>

<div id="available_companies"></div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

var jstpl_player_board = '\<div class="cp_board">\
    <div class="board_item"><div id="money_icon_${id}" class="token money"></div><span id="money_${id}">0</span></div>\
</div>';

var jstpl_company_content = '<div id="company_stock_holder_${short_name}" class="company_stock_holder"></div>\
    <div class="board_item company_money"><div id="money_icon_${short_name}" class="token money"></div><span id="money_${short_name}">0</span></div>';

var jstpl_stock = '<div class="stock ${short_name} ${stock_type}"></div>';

var jstpl_stock_interior = '<div class="stock_percent"></div>';

var jstpl_share_token = '<div id="share_token_${short_name}" class="company_token ${short_name}_token"></div>';
var jstpl_appeal_token = '<div id="appeal_token_${short_name}" class="company_token ${short_name}_token"></div>';

var automation_holder = '<div id="${short_name}_automation_holder_${factory}_${number}" class="automation_holder" style="left:${left}px;"</div>'
var jstpl_automation_token = '<div id="${card_type}" class="automation_token"></div>'
</script>  

{OVERALL_GAME_FOOTER}
