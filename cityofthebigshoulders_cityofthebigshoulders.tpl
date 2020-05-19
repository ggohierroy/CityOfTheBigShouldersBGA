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

<div id="main_board_wrapper" class="whiteblock">
    <div id="main_board" class="center">
        <div id="capital_assets"></div>
        <div id="job_market_worker" class="worker_spot">
            <div id="job_market_worker_holder"></div>
        </div>
        <div id="haymarket_square">
            <div id="haymarket"></div>
        </div>
        <div id="appeal_1" bonus="worker" class="appeal-bonus"></div>
        <div id="appeal_2" bonus="manager" class="appeal-bonus"></div>
        <div id="appeal_3" bonus="automation" class="appeal-bonus"></div>
        <div id="appeal_4" bonus="partner" class="appeal-bonus"></div>
        <div id="appeal_5" bonus="good" class="appeal-bonus"></div>
        <div id="appeal_6" bonus="bump" class="appeal-bonus"></div>
        <div id="appeal_7" bonus="good" class="appeal-bonus"></div>
        <div id="appeal_8" bonus="bump" class="appeal-bonus"></div>
        <div id="supply_x" class="supply"></div>
        <div id="supply_30" class="supply"></div>
        <div id="supply_20" class="supply"></div>
        <div id="supply_10" class="supply"></div>
        <div id="job_market" class="supply"></div>
        <div id="dry_goods_50" class="demand dry_goods"></div>
        <div id="dry_goods_20" class="demand dry_goods" identifier="27">
            <div id="demand27_goods"></div>
        </div>
        <div id="dry_goods_0" class="demand dry_goods" identifier="28">
            <div id="demand28_goods"></div>
        </div>
        <div id="meat_packing_50" class="demand meat_packing"></div>
        <div id="meat_packing_20" class="demand meat_packing" identifier="29">
            <div id="demand29_goods"></div>
        </div>
        <div id="meat_packing_0" class="demand meat_packing" identifier="30">
            <div id="demand30_goods"></div>
        </div>
        <div id="food_and_dairy_50" class="demand food_and_dairy"></div>
        <div id="food_and_dairy_20" class="demand food_and_dairy" identifier="25">
            <div id="demand25_goods"></div>
        </div>
        <div id="food_and_dairy_0" class="demand food_and_dairy" identifier="26">
            <div id="demand26_goods"></div>
        </div>
        <div id="shoes_50" class="demand shoes"> </div>
        <div id="shoes_20" class="demand shoes" identifier="31">
            <div id="demand31_goods"></div>
        </div>
        <div id="shoes_0" class="demand shoes" identifier="32">
            <div id="demand32_goods"></div>
        </div>
        <div id="advertising" class="worker_spot general-action">
            <div id="advertising_holder"></div>
        </div>
        <div id="fundraising_40" class="worker_spot general-action">
            <div id="fundraising_40_holder"></div>
        </div>
        <div id="fundraising_60" class="worker_spot general-action">
            <div id="fundraising_60_holder"></div>
        </div>
        <div id="fundraising_80" class="worker_spot general-action">
            <div id="fundraising_80_holder"></div>
        </div>
        <div id="hire_manager" class="worker_spot general-action">
            <div id="hire_manager_holder"></div>
        </div>
        <div id="hire_salesperson" class="worker_spot general-action">
            <div id="hire_salesperson_holder"></div>
        </div>
        <div id="capital_investment" class="worker_spot general-action">
            <div id="capital_investment_holder"></div>
        </div>
        <div id="extra_dividends" class="worker_spot general-action">
            <div id="extra_dividends_holder"></div>
        </div>
        <!-- BEGIN building_track -->
        <div id="building_track_{PLAYER_ID}" class="building_track" style="top: {TOP}px"></div>
        <!-- END building_track -->
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
    <div id="goals"></div>
</div>

<div id=player_area_wrapper>
    <!-- BEGIN player_area -->
    <div id="player_{PLAYER_ID}" class="whiteblock">
        <h3 style="color: #{PLAYER_COLOR};">{PLAYER_NAME}</h3>
        <div id="building_area_{PLAYER_ID}"></div>
        <div class="personal_share_area">
            <h3>Personal Shares</h3>
            <div id="personal_area_{PLAYER_ID}"></div>
        </div>
        <div class="owned_companies_area">
            <h3>Owned Companies</h3>
            <div id="company_area_{PLAYER_ID}"></div>
        </div>
    </div>
    <!-- END player_area -->
</div>

<div class="whiteblock" >
    <h3>Available Shares From Companies</h3>
    <div id="available_shares_company"></div>
    <h3>Available Shares From Bank</h3>
    <div id="available_shares_bank"></div>
</div>

<div class="whiteblock">
    <h3>Available Companies</h3>
    <div id="available_companies"></div>
</div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

var jstpl_player_board = '\<div class="cp_board">\
    <div class="board_item"><div class="panel-token money"></div><span id="money_${id}">0</span></div>\
    <div class="board_item"><div class="panel-token meeple meeple-${color}"></div><span id="partner_current_${id}">0</span>/<span id="partner_${id}">0</span></div>\
</div>';

var jstpl_company_content = '<div id="${id}" class="company-content">\
    <div class="board_item company_money"><div class="panel-token money"></div><span id="money_${short_name}">0</span></div>\
    </div>';

var jstpl_share_token = '<div id="share_token_${short_name}" class="company_token ${short_name}_token"></div>';
var jstpl_appeal_token = '<div id="${id}" class="company_token ${short_name}_token"></div>';

var jstpl_automation_holder = '<div id="${short_name}_automation_holder_${factory}_${number}" class="automation_holder" style="left:${left}px;"</div>';
var jstpl_token = '<div id="${token_id}" class="${token_class}"></div>';

var jstpl_factory = '<div id="${id}" class="factory" style="left:${left}px; width:${width}px;"</div>';
var jstpl_worker_holder = '<div id="${id}" class="worker-holder" style="left:${left}px;"</div>';

var jstpl_manager_holder = '<div id="${id}" class="manager-holder"</div>';

var jstpl_generic_div = '<div id="${id}" class="${class}"</div>';

var jstpl_salesperson_holder = '<div id="${id}" class="salesperson-holder" style="top:${top}px;"</div>';

var jstpl_temp_worker = '<div class="worker temp"</div>';

var jstpl_temp_manager = '<div class="moveable-token meeple meeple-tan manager temp"</div>';

</script>  

{OVERALL_GAME_FOOTER}
