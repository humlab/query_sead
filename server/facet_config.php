<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once __DIR__ . '/config/facet_definitions.php';
require_once __DIR__ . '/query_builder.php';
require_once __DIR__ . '/sql_query_builder.php';
require_once __DIR__ . '/lib/utility.php';

class FacetsConfig2
{
    public $requestId = "";
    public $language = "";
    public $requestType = "";       // Request specifier ("populate", ...)
    public $targetCode = "";        // Target facet code i.e. facet for which new data is requested
    public $triggerCode = "";       // Facet code that triggerd the request (some preceeding facet)
    public $facetConfigs = [];      // Current client facet configurations
    public $inactiveConfigs = [];   // Those having unset position

    public $targetFacet = NULL;     // Target facet definition
    public $triggerFacet = NULL;    // Trigger facet definition

    public $targetConfig = NULL;    // Target facet config

    function __construct($requestId, $language, $facetConfigs, $requestType = "", $targetCode = "", $triggerCode = "") {
        $this->requestId = $requestId;
        $this->language = $language;
        $this->requestType = $requestType;
        $this->targetCode = $targetCode;
        $this->triggerCode = $triggerCode;
        $this->facetConfigs = $facetConfigs;

        $this->targetFacet = empty($targetCode) ? NULL : FacetRegistry::getDefinition($targetCode);
        $this->triggerFacet = empty($triggerCode) ? NULL : FacetRegistry::getDefinition($triggerCode);
        $this->targetConfig = $this->facetConfigs[$targetCode];
    }

    public function getConfig($facetCode)
    {
        return $this->facetConfigs[$facetCode];
    }

    // TODO Rename to getFacetCodes getCodesOfActiveFacets
    public function getFacetCodes()
    {
        $facetCodes = [];
        foreach ($this->facetConfigs as $facetCode => $item) {
            $facetCodes[$item->position] = $facetCode;
        }
        ksort($facetCodes);
        return $facetCodes;
    }

    //public function getUserPickGroups()
    public function getFacetConfigsWithPicks()
    {
        $configs = array_filter($this->facetConfigs, function ($item) { return count($item->picks) > 0; });
        return $configs;
    }

    public function getFacetCodesWithPicks()
    {
        return array_keys($this->getFacetConfigsWithPicks());
    }

    public function deletePicks()
    {
        foreach ($this->facetConfigs as $config)
            $config->clearPicks();
    }

    public function collectUserPicks($onlyCode = false)
    {
        $matrix = [ 'counts' => [] ];
        foreach ($this->facetConfigs as $config) {
            if (count($config->picks) == 0)
                continue;
            if ($onlyCode !== false && $onlyCode != $config->facetCode)
                continue;
            $picks = $config->getPickValues();
            $matrix[$facetCode] = [
                "display_title" => $config->facet->display_title,
                // FIXME: Don't implode! let client handle this?
                'selections' => $config->facet->isOfTypeRange() ? [ implode(" - ", $picks) ] : $picks
            ];
            $matrix['counts'][$config->facet->facet_type] += count($config->picks);
        }
        return $matrix;
    }

    public function hasPicks($facetType = NULL)
    {
        foreach ($this->facetConfigs as $config) {
            if (count($config->picks) == 0)
                continue;
            if ($facetType == NULL || $facetType == $config->facet->facet_type)
                return true;
        }
        return false;
    }

    public function deleteBogusPicks()
    {
        $result = DeleteBogusPickService::deleteBogusPicks($this);
        return $result;
    }

    public function getPicksCacheId()
    {
        $key = "";
        foreach ($this->getFacetConfigsWithPicks() as $config) {
            $key .= $config->facetCode . '_' . implode("_", $config->getPickValues(true));
        }
        return $key;
    }

    public function getCacheId()
    {
        $filter = ConfigRegistry::getFilterByText() ? $this->targetFacet->textFilter : "no_text_filter";
        return $this->targetCode . '_' . implode("", $this->getFacetCodes()) .
               '_' . $this->getPicksCacheId() .
               '_' . $this->language . '_' . $filter;
    }

    public function getTargetTextFilter()
    {
        return isset($this->targetConfig) ? trim($this->targetConfig->textFilter) : "";
    }
}

class FacetConfig2
{
    public $facetCode = "";
    public $position = 0;
    public $startRow = 0;
    public $rowCount = 0;
    public $textFilter = "";
    public $picks = [];
    public $facet = NULL;
    public $textFilterClause = "";

    function __construct($facetCode, $position, $startRow, $rowCount, $filter, $picks) {
        $this->facetCode = $facetCode;
        $this->position = $position;
        $this->startRow = $startRow;
        $this->rowCount = $rowCount;
        $this->textFilter = $filter == "undefined" ? "" : trim($filter);
        $this->picks = $picks;
        $this->facet = FacetRegistry::getDefinition($facetCode);
        $this->textFilterClause = $this->getTextFilterClause();
    }

    public function getPickValues($sort = false)
    {
        $values = array_map(function ($x) { return $x->value; }, $this->picks);
        if ($sort === true)
            sort($values);
        return $values;
    }

    public function clearPicks()
    {
        if (count($this->picks) > 0)
            $this->picks = [];
    }

    // TODO: Move to SqlQueryBuilder
    function getTextFilterClause()
    {
        return empty($this->textFilter) ? "" : " AND {$this->facet->name_column} ILIKE '{$this->textFilter}' ";
    }

    public function getPage()
    {
        return [ $this->startRow, $this->rowCount ];
    }

    public function getPickedLowerUpperBounds()
    {
        $bounds = [];
        foreach ($this->picks as $pick) {
            $bounds[$pick->type] = $pick->value;
        }
        return $bounds;
    }

    // FIXME: Move to a FacetRepository?
    public function getStorageLowerUpperBounds()
    {
        $sql = RangeLowerUpperSqlQueryBuilder::compile(NULL, $this->facet);
        $facet_bound = ConnectionHelper::queryRow($sql);
        return $facet_bound;
    }
}

class FacetConfigPick
{
    public $type = NULL;
    public $value = NULL;
    public $text = "";
    function __construct($type, $value, $text) {
        $this->type = $type;
        $this->value = $value;
        $this->text = $text;
    }
}

/*
file: facet_config.php
This file holds all functions to process and handle params and xml-data from client
*/

class DeleteBogusPickService
{

    //***************************************************************************************************************************************************
    /*
    function:  deleteBogusPicks
    Removes invalid selections e.g. hidden selections still being sent from the client.
    The client keep them since they can be visible when the filters changes
    This is only applicable for discrete facets (range facet selection are always visible)
    */
    public static function deleteBogusPicks(&$facetsConfig)
    {
        foreach ($facetsConfig->getFacetCodes() ?: [] as $facetCode) {
            $config = $facetsConfig->getConfig($facetCode);
            if ($config->facet->facet_type != "discrete" || count($config->picks) == 0) {
                continue;
            }
            $query = QuerySetupService::setup2($facetsConfig, $facetCode);
            $sql = ValidPicksSqlQueryBuilder::compile($query, $config->facet, $config->getPickValues());
            $rows = ConnectionHelper::queryRows($sql) ?: [];
            $values = array_map(function ($x) { return new FacetConfigPick("discrete", $x["pick_id"], $x["name"]); }, $rows);
            $config->picks = $values;
        }
        return $facetsConfig;
    }

}
