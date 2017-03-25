<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php
session_start();
$sid = session_id();

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);
require_once("server/language/language.php");

$view_state_id = empty($_GET["view_state"]) ? get_default_view_state() : $_GET["view_state"];
$client_language = get_view_state_language($view_state_id);

?>
<?php
/*
file: index.php

Query-based Multidimensional browsing Over Relational databases. [HUMLab facetted browser]

http://www.humlab.umu.se


This system enabled browsing and retrieval content from  a database.


Overall the systems is:
- javascript and html-pages
- php-server scripts in /server
- map-files for minnesota map server


Infrastructure:
- Apache-webserver (http://projects.apache.org/projects/http_server.html)
- Php5 (http://www.php.net)
- Database backend postgressql 8.4 (http://www.postgresql.org/)
- PostGIS 1.4 (http://postgis.refractions.net/)
- Minnesota map-server (http://mapserver.org/)
- Batik rasterizer (http://xmlgraphics.apache.org/batik/tools/rasterizer.html)
- jquery http://jquery.com/
- Highchart (educational use) http://www.highcharts.com/

Postgres installation notes:
- sudo apt-get install postgresql-8.4-postgis sudo su postgres createdb -p 5433 postgistemplate createlang -p 5433 plpgsql postgistemplate psql -p 5433 -d postgistemplate -f /usr/share/postgresql/8.4/contrib/postgis-1.5/postgis.sql
- psql -p 5433 -d postgistemplate -f /usr/share/postgresql/8.4/contrib/postgis-1.5/spatial_ref_sys.sql
- psql -p 5433 -d postgistemplate -c "SELECT postgis_full_version();"
- createdb -p 5433 -T postgistemplate -O ships ships
- pg_restore -p 5433 -d ships -U postgres ships.dump
- create user regio

Batik rasterizer (jar-files):
- Located in jslib/highchart/exporting_server/batik-1.7
- Sun java should be used

Directort permission notes:
- cache needs to have write permissions
- api/cache and subdirectories need to have write permissionss

Javascript libries:
Own core libraries:
- <control_bar.js>
- <facet.discrete.js>
- <facet.geo.js>
- <facet.js>
- <facet.range.js>
- <layout.js>
- <main.js>
- <result.js>
- <slot.js>
- <user.js>
- <util.js>

Result modules javascript libraries:
- <diagram_module.js (SHIPS)>
- <list_module.js (SHIPS)>
- <map_module.js (SHIPS)>


PHP-scripts used in ajax requests and request:
- <load_facet.php> loads facet information for different type of facets
- <load_result.php>; loads result_information for different types of results (map, table and diagram)
- <get_data_table.php>; get the result zip-file with tab-separated data and documentation into a zip-file
- <map_download.php>; get the map as png with a world-file as well as the placenames of the relevant polygons in the  map.(parished or counties)
- <get_view_state.php>; get a view state from database to be used to recreate a view state
- <save_view_state.php>; saves a view_state into the database
- REMOVED <diagram_symbol.php>; renders a image based on color and type
- REMOVED (SEAD): <get_xy_statistics.php>; get a textfile with statistics for a point in the map

The start-up parameters are:
view_state - which view state to start from
client_language -  which language to be used sv_SE

Initialization sequence:
* <js_config.php> defines application properties
* <interface.php>  outlines  properties and heading of the html-pagee
* layout.php - outlines the div and table structure and this is different for each application (SEAD/SHIPS/DIABAS) see <layout.php (SHIPS)> and <layout.php (SEAD)>
* Stylesheet to be used. style.css
* <js_facet_def.php> getting the javascript defintions for the facets
* <js_result_def.php> getting the javascript defintion of the result variables and load the result modules from the result_modules directory
* <language_init.php> - get the phrases to be used/translated
* <script_config.php> load specific js-library for a application
* loads all js-libraries


Typical facet-oriented user activities:
* Add a  facet from controlbar using <control_bar_click_callback> and also later  <facet_create_facet> in <facet.js>
* Remove a facet using javascript function <facet_remove_facet> in <facet.js>
* Change ordering of facets by drag and drop  starting with function <slot_action_callback>
* Minimize the size of a facet using <facet_collapse_toggle> in <facet.js>
* Restore the size of facet using <facet_collapse_toggle> in  <facet.js>
* Make a selection in a discrete facet by clicking on row see <facet_row_clicked_callback> in <facet.js>
* Remove  a selection in a discrete facet by clicking on row see <facet_row_clicked_callback> in <facet.js>
* Make a selection in a range(interval) facet by change the lower or upper limits (via text-forms or sliders). <facet_range_changed_callback> is being called from the flash-component
* Make a selection in geo/mapfilter facet by adding a rectangle in the map-filter  calling  <facet_geo_marker_tool_click_callback> when selection rectangle is completed
* Remove a selection in the geo/mapfilter facet <facet_geo_get_marker_pair_by_marker> ,<facet_geo_points_is_within_critical_proximity> <facet_geo_destroy_marker_pair>
* Scroll in a discrete facet and when the client cache data does need to be populate <facet_load_data> is called in <facet.js>

General result-oriented user activities:
* Maximize the result area to make the area bigger <result_maximize>
* Restore the result area to fit it into the whole webpage (only when it is maximized)
* Activate the map view using <result_switch_view> with the "map" as a argument and later <result_render_view_map>
* Activate the diagram view <result_switch_view> with the "diagram" as a argument and later on <result_render_view_diagram>
* Activate the list view <result_switch_view> with the "list" as a argument and later <result_render_view_list>
* Change aggregation level for the result view (SHIPS) which triggeres <result_switch_view> and loads new data and renders the view
* Add  a result variable for the result views triggering <result_switch_view>  and loads new data and renders the view
* Remove a result variable for the result views triggering <result_switch_view>  and loads new data and renders the view

Result list oriented user activities:
* Download zip-file with data and documentation by calling <get_data_table.php>

Result map oriented user activities (SHIPS):
* Select active variable for the thematic visualisation. Triggering <result_load_data>
* Set the year that the map should show using a time-bar. Triggering <result_map_time_bar_changed_callback> and later <result_load_data>
* Zoom and pan in the map.
* Download the thematic visualisation-layer with a historic background map using <map_download.php> with use_historic=true as PNG-image
* Download the thematic visualisation-layer   using <map_download.php>  as PNG-image
* Download coordinate file for map layer with a historic background map , via a link to a file on the server (pgw-file)
* Download coordinate file for map layer without background map via a link to a file on the server (pgw-file))
* Download legend as a image by calling <map_legend_image.php>

Result diagram oriented user activities (SHIPS):
* Select which result variable to use as x-axis.
* Select the diagram mode "one variable for all aggregation units"  and select which variable to use from the list
* Select the diagram mode "one aggregation unit all result variables" and select which aggregation unit to use from the list which show all select variables
* Show a tooltip for a datapoint.
* Print the diagram
* Download the diagram as png, or svg

The client is mainly sending request to server using
* <facet_load_data> in <facet.js> for facet content (discrete values, intervals and geo-information)
* <result_load_data> in <result.js> for content into the result area (map, diagram and "list-like" information"

(see applications/sead/theme/images/SeadLogo.jpg)

*/
require_once("js_config.php");
include_once("interface.php");
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title><?php echo $applicationTitle?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.12.1/themes/cupertino/jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="applications/sead/theme/style.css" />

    <script type="text/javascript" src="//code.jquery.com/jquery-3.2.0.min.js"></script>
    <script type="text/javascript" src="//code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.9/highcharts.js"></script>
    <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/highcharts/5.0.9/js/modules/exporting.js"></script>
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?key=AIzaSyDEnaCiVoQ54k1MFbUECGJttDU1Vj7pPOw&sensor=false"></script>

    <script type="text/javascript" src="client/client_definitions.js"></script>
    <script type="text/javascript">
    var client_language = "<?=$client_language?>";
    var current_view_state_id = "<?=$view_state_id?>";
    </script>

    <script type="text/javascript" src="client/user.js"></script>
    <script type="text/javascript">
        var application_address = "<?php echo get_js_server_address() ?>";
        var application_prefix_path = "<?php echo get_js_server_prefix_path() ?>";
        var application_name = "<?php echo getApplication() ?>";
        var filter_by_text = "<?php echo $filter_by_text ?>";
        var currentUser = new User();
        var use_web_socket = false; 
        currentUser.sessionKey = "<?php echo generateSessionKey() ?>";
    </script>

    <script type="text/javascript">
<?php
    require_once("api/js_facet_def.php");
    echo "</script>";
    require_once("api/js_result_def.php");
    require_once("server/language/language.php");
    // if(isset($_GET['f']) && $_GET['f'] == "language_update")
        // echo language_perform_update();
    echo language_create_javascript_languages_definition_array();
    echo language_create_javascript_translation_arrays();
?>
    <script type="text/javascript" src="client/client_ui_definitions.js"></script>
    <script type="text/javascript" src="client/util.js"></script>
    <script type="text/javascript" src="client/slot.js"></script>
    <script type="text/javascript" src="client/facet_list.js"></script>
    <script type="text/javascript" src="client/facet.range.js"></script>
    <script type="text/javascript" src="client/facet.discrete.js"></script>
    <script type="text/javascript" src="client/facet.geo.js"></script>
    <script type="text/javascript" src="client/facet_view_render.js"></script>
    <script type="text/javascript" src="client/facet_view.js"></script>
    <script type="text/javascript" src="client/facet_presenter.js"></script>
    <script type="text/javascript" src="client/facet.js"></script>
    <script type="text/javascript" src="client/nodejs-client.js"></script>
    <script type="text/javascript" src="client/notifyservice.js"></script>
    <script type="text/javascript" src="client/layout.js"></script>
    <script type="text/javascript" src="client/control_bar.js"></script>
    <script type="text/javascript" src="client/result.js"></script>
    <script type="text/javascript" src="client/info_area.js"></script>

<?php
    if(!empty($_GET['view_state'])) {
        echo "<script type=\"text/javascript\"> current_view_state_id = ".$_GET['view_state']."; </script>";
    }
?>
<script type="text/javascript" src="client/main.js"></script>
</head>
<body>

<div id="title_bar_container">
    <table style="border-collapse:collapse;">
        <tr>
            <td id="title_bar_left_container">
            <div id="title_bar_left"></div>
            </td>
            <td id="title_bar_middle_container">
                <div id="title_bar_middle">
                    <span style="color:#ffffff;font-weight:bold;font-size:12px;font-style:italic;">SEAD</span>
                    <span style="color:#ffffff;font-size:10px;position:relative;top:-1px;"> - The Strategic Environmental Archaeology Database</span>
                    <span id="aux_buttons_container">
                        <table style="border-collapse:collapse;position:relative;top:-1px;left:6px;margin:0px;padding:0px;">
                        <tbody>
                            <tr>
                                <td onclick="info_area_open_about('<?=t("http://www.sead.se", $client_language);?>')">
                                                                   
                                    <span id="about_sead_button"> <?=interface_render_title_button("About SEAD");?></span>
                                </td>
                                <td onclick="info_area_open_about('<?=t("http://www.sead.se/help", $client_language);?>')">
                                    <span id="help_button"><?=interface_render_title_button("Help", true);?></span>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                    </span>
                    <span id="view_state_buttons_container">
                        <table style="border-collapse:collapse;position:relative;top:-1px;">
                            <tbody>
                                <tr>
                                    <td>
                                        <span id="save_view_state_button"><?=interface_render_title_button("Save view");?></span>
                                    </td>
                                    <td>
                                        <span id="load_view_state_button"><?=interface_render_title_button("Load view");?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </span>
                    <span id="language_selection_button_container">
                        <table style="border-collapse:collapse;position:relative;top:-1px;">
                            <tbody>
                                <tr>
                                    <td>
                                        
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </span>
                </div>
            </td>
            <td id="title_bar_right_container">
                <div id="title_bar_right"></div>
            </td>
        </tr>
    </table>
</DIV>
<div id="vertical_control_container">
    <table style="border-collapse:collapse;">
        <tr>
            <td id="vertical_control_middle_container">
                
                            <div id="facet_controller_outer">
                            <table class="generic_table">
                                <tbody>
                                    <tr>
                                        <td class="generic_table_top_left"></td>
                                        <td class="generic_table_top_middle"></td>
                                        <td class="generic_table_top_right"></td>
                                    </tr>
                                    <tr>
                                        <td class="generic_table_middle_left"></td>
                                        <td class="generic_table_middle_middle content_container"><?=$facet_control?></td>
                                        <td class="generic_table_middle_right"></td>
                                    </tr>
                                    <tr>
                                        <td class="generic_table_bottom_left"></td>
                                        <td class="generic_table_bottom_middle"></td>
                                        <td class="generic_table_bottom_right"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
            </td>
        </tr>
    </table>
</DIV>
<img id="umu_logo" src="applications/sead/theme/images/umu.png" />
<table>
    <tr>
        <td style="vertical-align:top;">
            <div id="facet_workspace"></div>
        </td>
        <td style="vertical-align:top;">
            <div id="info_area"><?=interface_render_info_area2();?></div>
            <div id="status_area_outer">
                <table class="generic_table">
                    <tbody>
                        <tr>
                            <td class="generic_table_top_left"></td>
                            <td class="generic_table_top_middle"></td>
                            <td class="generic_table_top_right"></td>
                        </tr>
                        <tr>
                            <td class="generic_table_middle_left"></td>
                            <td class="generic_table_middle_middle content_container">
                                <?=$status_area?></td><td class="generic_table_middle_right">
                            </td>
                        </tr>
                        <tr>
                            <td class="generic_table_bottom_left"></td>
                            <td class="generic_table_bottom_middle"></td>
                            <td class="generic_table_bottom_right"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="result_workspace"><?=$result_workspace?></div>
        </td>
            <td style="vertical-align:top;">
            
            <div id="result_controller_outer" >
                <table class="generic_table">
                    <tbody>
                        <tr>
                            <td class="generic_table_top_left"></td>
                            <td class="generic_table_top_middle"></td>
                            <td class="generic_table_top_right"></td>
                        </tr>
                        <tr>
                            <td class="generic_table_middle_left"></td>
                            <td class="generic_table_middle_middle content_container"><?=$result_control?></td>
                            <td class="generic_table_middle_right"></td>
                        </tr>
                        <tr>
                            <td class="generic_table_bottom_left"></td>
                            <td class="generic_table_bottom_middle"></td>
                            <td class="generic_table_bottom_right"></td>
                        </tr>
<tr>
                            <td class="generic_table_top_left"></td>
                            <td class="generic_table_top_middle"></td>
                            <td class="generic_table_top_right"></td>
                        </tr>
                        <tr>
                            <td class="generic_table_middle_left"></td>
                        <td class="generic_table_middle_middle content_container">
                        <SPAN ID="LOGO_AREA">
                        <IMG SRC="applications/sead/theme/images/SeadLogo.jpg" ALT="LOGO_sead">
                        <BR>
                        An international standard database for environmental archaeology data is under development at the Environmental Archaeology Lab (MAL), in collaboration with HUMlab, at Umeå University, Sweden.
<BR><BR>
SEAD is financed by The Swedish Research Council and Umeå University Faculty of Humanities and Department of Historical, Philosophical and Religious Studies.
<BR><BR>
<BR>
<BR>
<a href src="http://www.idesam.umu.se/english/mal/?languageId=1"><img src="applications/sead/theme/images/logo_mal_x116_notext.jpg"></a >
<BR>
<BR>
<BR>
<a href src="http://www.humlab.umu.se"><img src="applications/sead/theme/images/logo_humlab_x116.gif"></a >
<BR>
<BR>
<BR>
<a href src="http://www.umu.se"><img src="applications/sead/theme/images/logo_umu_x116.gif"></a >
<BR>
<BR>
<BR>
<a href src="http://www.lunduniversity.lu.se/"><img src="applications/sead/theme/images/450.gif" width="116"></a >
<BR>
<BR>
                        </span>
                        <td class="generic_table_middle_right"></td>
                        <tr>
                            <td class="generic_table_bottom_left"></td>
                            <td class="generic_table_bottom_middle"></td>
                            <td class="generic_table_bottom_right"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </td>
    </tr>
</table>

<div id="msg" style="background-color:#eee;color:#000;position:absolute;left:1230px;top:0px;">
</div>
<div id="msg2" style="float:left;background-color:#ffe;color:#000;font-size:10px;"></div>
<div id="msg3" style="float:left;background-color:#ffe;color:#000;font-size:10px;"></div>


</body>
</html>