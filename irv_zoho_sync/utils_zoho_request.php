<?php

include_once 'Utilities.php';
include_once 'ZohoIntegrator.php';

/***********************************************************************
 *
 * INCLUDED ZOHO API        --- API NAME IN THIS LIBRARY
 * ---------------------------------------------------------------------
 * getRecordById            --- getRecordById
 * insertRecords            --- insertRecords
 * updateRecords            --- updateRecords
 * deleteRecords            --- deleteRecords
 * getFields                --- getFields
 *
 **********************************************************************/

class ZohoDataSync extends ZohoIntegrator
{
    public function __construct($token = null)
    {
        $this->resetWithDefaults();
        $authToken = ($token === null) ? AUTH_TOKEN : $token;
        $authtokenSet = $this->setZohoAuthToken($authToken);
        if ($authtokenSet !== true) {
            echo 'Please provide authtoken or set auth token first';
            die();
        }
    }

    public function doRequest()
    {
        $response = $this->buildRequestUri();
        if ($response !== true) return $response;
        $response = $this->buildUriParameter();
        if ($response !== true) return $response;
        return $this->sendCurl();
    }
	
    public function updateRecords($moduleName, $id, $xmlArray, $wfTrigger = 'false')
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('updateRecords');
        $this->setRequestMethod('POST');
        $extraParameter = array(
            "id" => "$id",
        );
        if ($wfTrigger != 'false') $this->setWfTrigger($wfTrigger);
        if (($xmlSet = $this->setZohoXmlColumnNameAndValue($xmlArray)) !== true) return $xmlSet;

        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
    }

    public function deleteRecords($moduleName, $id)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('deleteRecords');
        $this->setRequestMethod('POST');
        $extraParameter = array(
            "id" => "$id",
        );

        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
    }

    public function getRecordById($moduleName, $id, $newFormat = 1)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('getRecordById');
        $extraParameter = array(
            "id" => "$id",
            "newFormat" => $newFormat
        );
        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
    }

    public function insertRecords($moduleName, $xmlArray, $duplicateCheck = 'false', $wfTrigger = 'false', $version = 'false')
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('insertRecords');
        $this->setRequestMethod('POST');
        if ($duplicateCheck != 'false' && count($xmlArray) > 1 ) {
            $version = 4;
            $this->setZohoExtendedUriParameter(array('duplicateCheck' => (int)$duplicateCheck));
        }
        if ($wfTrigger != 'false') $this->setWfTrigger($wfTrigger);
        if ($version != 'false') $this->setMultipleOperation($version);
        if (($xmlSet = $this->setZohoXmlColumnNameAndValue($xmlArray)) !== true) return $xmlSet;

        return $this->doRequest();
    }

    /*
     * Param: @type
     * Empty - To retrieve all fields from the module
     * 1 - To retrieve all fields from the summary view
     * 2 - To retrieve all mandatory fields from the module
     *
    */
    public function getFields($moduleName, $type = null) // 1 for all fields and 2 for mandatory fields
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('getFields');
        if ($type != null) {
            $extraParameter = array(
                "type" => "$type"
            );
            $this->setZohoExtendedUriParameter($extraParameter);
        }

        return $this->doRequest();
    }

    public function errorFound($xml) {
        if ((isset($xml->nodata->code) && trim($xml->nodata->code) !== "")
            || (isset($xml->error->code) && trim($xml->error->code) !== "")) {
            return true;
        }
        return false;
    }
}

?>