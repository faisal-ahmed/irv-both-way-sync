<?php

include_once 'Utilities.php';
include_once 'ZohoIntegrator.php';

/***********************************************************************
 *
 * INCLUDED ZOHO API        --- API NAME IN THIS LIBRARY
 * ---------------------------------------------------------------------
 * getRecords               --- getRecordsOfZoho
 * getRecordById            --- getRecordById
 * getCVRecords             --- searchCVRecords
 * insertRecords            --- insertRecords
 * updateRecords            --- updateRecords
 * getSearchRecords         --- searchRecordsWithCustomField
 * getSearchRecordsByPDC    --- getSearchRecordsByPDC
 * getRelatedRecords        --- getRelatedRecords
 * updateRelatedRecords     --- addRelatedRecords
 * uploadFile               --- uploadFile
 * uploadPhoto              --- uploadPhoto
 * deleteRecords            --- deleteRecords
 * convertLead              --- convertLeadWithoutPotential
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
	
	public function getSearchRecordsByPDC($moduleName, $searchColumn, $searchValue, $selectColumns = 'All')
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('getSearchRecordsByPDC');
        $selectColumnString = '';
        if (is_array($selectColumns)) {
            foreach ($selectColumns as $key => $value) {
                $selectColumnString .= ( ($selectColumnString === '') ? $value : ",$value" );
            }
            $selectColumnString = "$moduleName($selectColumnString)";
        } else {
            $selectColumnString = $selectColumns;
        }

        $extraParameter = array(
            "selectColumns" => "$selectColumnString",
            "searchColumn" => "$searchColumn",
            "searchValue" => "$searchValue",
            "newFormat" => 2,
        );

        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
    }

    public function searchRecordsWithCustomField($moduleName, $fieldName, $fieldValue, $matchingExpression = 'contains', $fromIndex = null, $toIndex = null)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('getSearchRecords');
        if ($matchingExpression == 'contains') $fieldValue = '*' . $fieldValue . '*';
        $extraParameter = array(
            "searchCondition" => "($fieldName|$matchingExpression|$fieldValue)",
            "selectColumns" => "All",
        );

        if ($fromIndex != null) {
            $extraParameter['fromIndex'] = $fromIndex;
        } else {
            $extraParameter['fromIndex'] = 1;
        }
        if ($toIndex != null) {
            $extraParameter['toIndex'] = $toIndex;
        } else {
            $extraParameter['toIndex'] = 200;
        }
        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
    }

    public function searchCVRecords($moduleName, $viewName, $fromIndex = 1, $lastModifiedTime = null)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('getCVRecords');
        $extraParameter = array(
            "selectColumns" => "All",
            "fromIndex" => $fromIndex,
            "toIndex" => ($fromIndex + 199),
            "cvName" => "$viewName",
            "newFormat" => 2,
        );
        if (isset($lastModifiedTime)) $extraParameter['lastModifiedTime'] = $lastModifiedTime;
        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
    }

    public function addRelatedRecords($moduleName, $id, $relatedModule, $xmlArray)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('updateRelatedRecords');
        $this->setRequestMethod('POST');
        $extraParameter = array(
            "id" => "$id",
            "relatedModule" => "$relatedModule"
        );
        if (($xmlSet = $this->setZohoXmlColumnNameAndValue($xmlArray)) !== true) return $xmlSet;

        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
    }

    public function getRelatedRecords($moduleName, $id, $relatedModule)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('getRelatedRecords');
        $this->setRequestMethod('POST');
        $extraParameter = array(
            "id" => "$id",
            "parentModule" => "$relatedModule",
            "fromIndex" => 1,
            "toIndex" => 200,
            "newFormat" => 2,
        );

        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
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

    public function convertLeadWithoutPotential($leadId, $assignTo, $newFormat = 1)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName(LEAD_MODULE);
        $this->setZohoApiOperationType('convertLead');
        $this->setRequestMethod('POST');
        $xmlData = <<<xml
<Potentials>
<row no="1">
<option val="createPotential">false</option>
<option val="assignTo">$assignTo</option>
<option val="notifyLeadOwner">false</option>
<option val="notifyNewEntityOwner">false</option>
</row>
</Potentials>
xml;

        $extraParameter = array(
            "leadId" => "$leadId",
            "newFormat" => $newFormat,
            "xmlData" => $xmlData
        );
        $this->setZohoExtendedUriParameter($extraParameter);

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
    public function uploadFile($moduleName, $id, $fileUrl)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('uploadFile');
        $extraParameter = array(
            "id" => "$id",
            "content" => "@$fileUrl"
        );

        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
        // Response trim($xml->result->message) == "File has been attached successfully"
    }

    public function uploadPhoto($moduleName, $id, $fileUrl)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('uploadPhoto');
        $extraParameter = array(
            "id" => "$id",
            "content" => "@$fileUrl"
        );

        $this->setZohoExtendedUriParameter($extraParameter);

        return $this->doRequest();
        // Response trim($xml->result->message) == "Photo uploaded succuessfully"
    }

    public function getRecordsOfZoho($moduleName, $lastModifiedTime = null, $sortColumnString = null, $sortOrderString = 'desc', $fromIndex = null, $toIndex = null)
    {
        $this->resetWithDefaults();
        $this->setZohoModuleName("$moduleName");
        $this->setZohoApiOperationType('getRecords');
        $extraParameter = array(
            "sortOrderString" => "$sortOrderString"
        );
        if (isset($lastModifiedTime)) $extraParameter['lastModifiedTime'] = $lastModifiedTime;
        if (isset($fromIndex)) $extraParameter['fromIndex'] = $fromIndex;
        if (isset($toIndex)) $extraParameter['toIndex'] = $toIndex;
        if (isset($sortColumnString)) $extraParameter['sortColumnString'] = $sortColumnString;

        $this->setZohoExtendedUriParameter($extraParameter);

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