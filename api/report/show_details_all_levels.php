<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>

<HEAD>
    <TITLE>Aggregated data report</TITLE>
    <META NAME="Generator" CONTENT="Netbeans">
    <META NAME="Author" CONTENT="SEAD">
    <META NAME="Keywords" CONTENT="sead">
    <META NAME="Description" CONTENT="Details of SEAD ">
    <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>

    <link rel="stylesheet" href="//cdn.datatables.net/1.9.4/css/jquery.dataTables.css">
    <link rel="stylesheet" href="/client/theme/reporting.css">
    <link rel="stylesheet" href="//cdn.datatables.net/tabletools/2.1.5/css/TableTools.css">

    <script type="text/javascript" charset="utf-8" language="javascript"
            src="//code.jquery.com/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" charset="utf-8" language="javascript"
            src="//cdn.datatables.net/1.9.4/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" charset="utf-8" language="javascript"
            src="//cdn.datatables.net/tabletools/2.1.5/js/TableTools.min.js"></script>

    <script type="text/javascript" charset="utf-8">
        /* Table initialisation */
        $(document).ready(function () {
            $("table[id|='d_table']").each(function () {
                $(this).dataTable({
                    "bPaginate": false, // Turned pagination off
                    "oTableTools": {
                        "sSwfPath": "//cdn.datatables.net/tabletools/2.1.5/swf/copy_csv_xls_pdf.swf",
                        "aButtons": ["copy", "csv", "pdf", "print"]
                    },
                    "sDom": "T<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>", // table setup

                    "bInfo": false, // Turned info off
                    "oLanguage": {
                        "sLengthMenu": "_MENU_ records per page",
                        "sSearch": "Filter results: _INPUT_" // Renamed Search to Filter
                    },
                });
            });
        });
    </script>

</HEAD>

<BODY>

<?php
/*
* file: show_details_all_level.php
* Make a report for a set of sites
*
* see also:
* - <site_queries.php>
* - <report_module.php>
*
* uses:
*- <site_reporter->site_info_report>
*- <site_reporter->dating_report>
*- <site_reporter->reference_report>
*- <site_reporter->sample_group_report>
*- <site_reporter->dataset_report>
*- <sample_group_reporter->sample_group_agg_summary>
*- <sample_group_reporter->species_report>
*- <sample_group_reporter->measured_values_report>
*/
require_once __DIR__ . '/../../server/connection_helper.php';
require_once __DIR__ . '/site_queries.php';
require_once __DIR__ . '/sample_group_queries.php';
require_once __DIR__ . '/report_module.php';

$cache_id = $_REQUEST["cache_id"];

ConnectionHelper::openConnection();

$reporter = new report_module();
$site_reporter = new site_reporter();
$site_id = null;

$sample_group_reporter = new sample_group_reporter();
echo $site_reporter->site_info_report($site_id, $cache_id);
echo $site_reporter->dating_report($site_id, $cache_id);
echo $site_reporter->reference_report($site_id, $cache_id);
echo $site_reporter->sample_group_report($site_id, $cache_id);
echo $site_reporter->dataset_report($site_id, $cache_id);

$sample_group_id = null;
echo $sample_group_reporter->sample_group_agg_summary($sample_group_id, $cache_id);
echo $sample_group_reporter->species_report($sample_group_id, $cache_id);
echo $sample_group_reporter->measured_values_report($sample_group_id, $cache_id);

ConnectionHelper::closeConnection();

?>

</BODY>

</HTML>