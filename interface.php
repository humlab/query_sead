<?php
/*
File: interface.php
This file is definining some headiing and titles of element in the html-interface
This files is inlclude when running the index.php

Ordinary sequence:
2 - <interface_render_status_area_content> set the title of the status area
3 - <interface_render_result_workspace_content>

See also:
REMOVED: <layout.php (SEAD)>
*/ 
require_once "server/language/t.php";
require_once "server/config/environment.php";

$status_area = interface_render_status_area_content();
$result_workspace = interface_render_result_workspace_content();

/*
function: interface_render_status_area_content
Returns the title of the "status area"
*/
function interface_render_status_area_content() {
	global $client_language;
	return "<div id=\"status_area\"><span class=\"h3_without_line_break\"></span> <span id=\"show_active_filters_link\">".t("Visa aktuella val",$client_language)."</span><div id=\"status_area_content_container\"></div></div>";
}

/*
function: interface_render_result_workspace_content
Returns the needed and initial structure structure of the result area containing divs and tables. Later javasript functions will add more divs and table within this structure

*/
function interface_render_result_workspace_content() {
    $out = <<<EOS
    <table id="result_workspace_table_top" style="z-index:2;"><tbody>
	 <tr id="result_workspace_tab_area">
	 <td style=""><div class="result_loading_indicator"></div></td>
	 <td id="result_max_min_button_cell"><span class="js_link"><img id="result_max_min_button" style="cursor:pointer;" src="client/theme/images/button_expand.png" alt="button_expand.png"/></span></td></tr>
	 </tbody></table>
     <table id="result_workspace_table" class="generic_table"><tbody>
	 <tr>
	 <td class="result_table_top_left"></td><td class="result_table_top_middle">
	 </td><td class="result_table_top_right"></td>
	 </tr>
	 <tr>
	 <td class="result_table_middle_left"></td><td class="result_table_middle_middle">
	 <div>
	 	<div id="result_workspace_content_container"></div>
	 	</div>
	 </td><td class="result_table_middle_right"></td>
	 </tr>
	 <tr>
	 <td class="result_table_bottom_left"></td><td class="result_table_bottom_middle"></td><td class="result_table_bottom_right"></td>
	 </tr>
	 </tbody></table>
EOS;
	return $out;
}

function interface_render_title_button($text, $terminating_button = false) {
	$button_class = $terminating_button ? "title_button_right_round" : "title_button_right";
	$out = <<<EOX
	<table style="border-collapse:collapse;height:14px;"><tbody><tr>
	<td class="title_button_left"></td>
	<td class="title_button_middle">$text</td>
	<td class="$button_class"></td>
	</tr></tbody></table>
EOX;
	return $out;
}
?>
