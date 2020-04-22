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

<div id=main_board_wrapper>
    <div id=main_board>main board</div>
    <div id=public_goals>public goals</div>
    <div id=bank>bank</div>
</div>

<div id=player_area_wrapper>
    <!-- BEGIN player_area -->
    <div id="player_{PLAYER_ID}" class="player">
        <h1>{PLAYER_ID}/{PLAYER_NAME}/{PLAYER_COLOR}</h1>
        <div id="building_area_{PLAYER_ID}">my buildings</div>
        <div id="personal_area_{PLAYER_ID}">personal area</div>
        <div id="company_area_wrapper">
            <h2>owned companies area</h2>
            <div id="company_area_{PLAYER_ID}"></div>
        </div>
    </div>
    <!-- END player_area -->
</div>

<div id=companies_wrapper>
    <h1>available companies</h1>
    <div id="companies"></div>
</div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

var jstpl_company='<div class="company" id="${short_name}">${short_name}</div>';

</script>  

{OVERALL_GAME_FOOTER}
