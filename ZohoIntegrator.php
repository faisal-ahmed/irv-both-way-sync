<?php
/*
 * Created on April 24, 2013
 * @author - Mohammad Faisal Ahmed <faisal.ahmed0001@gmail.com>
 *
 */

abstract class ZohoIntegrator
{
    private $zohoApiUrl;
    private $zohoModuleName;
    private $zohoApiOperationType;
    private $zohoXmlColumn;
    private $zohoAuthToken;
    private $zohoScope;
    private $zohoEncoding;
    private $xmlData;
    private $requestMethod;
    private $requestUriToZoho;
    private $zohoResponse;
    private $uriParameter;
    private $wfTrigger;
    private $uriParameterExtended;
    private $multipleOperation;

    public function resetWithDefaults()
    {
        $this->setZohoApiUrl('https://crm.zoho.com/crm/private/xml');
        $this->setZohoScope('crmapi');
        $this->setZohoEncoding('ISO-8859-1');
        $this->setRequestMethod('GET');
        $this->wfTrigger = 'false';
        $this->multipleOperation = 'false';
        $this->xmlData = '';
        $this->zohoApiOperationType = '';
        $this->zohoModuleName = '';
        $this->uriParameter = array();
        $this->requestUriToZoho = '';
        $this->zohoResponse = '';
        $this->uriParameterExtended = '';
        return true;
    }

    public function setZohoApiUrl($apiUrl)
    {
        if ($apiUrl == '') return 'ZohoApiUrl cannot be empty';
        $this->zohoApiUrl = $apiUrl;
        return true;
    }

    public function setZohoModuleName($moduleName)
    {
        if ($moduleName == '') return 'ZohoModuleName cannot be empty';
        $this->zohoModuleName = $moduleName;
        return true;
    }

    public function setZohoApiOperationType($operationType)
    {
        if ($operationType == '') return 'ZohoApiOperationType cannot be empty';
        $this->zohoApiOperationType = $operationType;
        return true;
    }

    public function setZohoXmlColumnNameAndValue($xmlColumn)
    {
        if (!is_array($xmlColumn)) return 'ZohoXmlColumnNameAndValue must be an array';

        $this->zohoXmlColumn = $xmlColumn;

        return true;
    }

    public function addZohoXmlColumnNameAndValue($xmlColumn)
    {
        if (!is_array($this->zohoXmlColumn)) $this->zohoXmlColumn = array();

        if (!is_array($xmlColumn)) $this->zohoXmlColumn = array_merge($this->zohoXmlColumn, (array)$xmlColumn);
        else $this->zohoXmlColumn = array_merge($this->zohoXmlColumn, $xmlColumn);

        return true;
    }

    public function setZohoExtendedUriParameter($parameter)
    {
        if (!is_array($parameter)) return 'ZohoExtendedUriParameter must be an array';

        $this->uriParameterExtended = $parameter;

        return true;
    }

    public function addZohoExtendedUriParameter($parameter)
    {
        if (!is_array($this->uriParameterExtended)) $this->uriParameterExtended = array();

        if (!is_array($parameter)) $this->uriParameterExtended = array_merge($this->uriParameterExtended, (array)$parameter);
        else $this->uriParameterExtended = array_merge($this->uriParameterExtended, $parameter);

        return true;
    }

    public function setZohoAuthToken($authToken)
    {
        if ($authToken == '') return 'ZohoAuthToken cannot be empty';
        $this->zohoAuthToken = $authToken;
        return true;
    }

    public function setZohoScope($scope)
    {
        if ($scope == '') return 'ZohoScope cannot be empty';
        $this->zohoScope = $scope;
        return true;
    }

    public function setZohoEncoding($encoding)
    {
        if ($encoding == '') return 'ZohoEncoding cannot be empty';
        $this->zohoEncoding = $encoding;
        return true;
    }

    public function setRequestMethod($requestMethod)
    {
        if ($requestMethod == '') return 'RequestMethod cannot be empty';
        $this->requestMethod = $requestMethod;
        return true;
    }

    public function setWfTrigger($trigger)
    {
        if (!(strtolower($trigger) === 'true' || strtolower($trigger) === 'false')) return 'WfTrigger must be either true or false';
        $this->wfTrigger = strtolower($trigger);
        return true;
    }

    public function setMultipleOperation($operation)
    {
        $this->multipleOperation = $operation;
        return true;
    }

    public function getWfTrigger()
    {
        return $this->wfTrigger;
    }

    public function getMultipleOperation()
    {
        return $this->multipleOperation;
    }

    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    public function getZohoEncoding()
    {
        return $this->zohoEncoding;
    }

    public function getZohoApiUrl()
    {
        return $this->zohoApiUrl;
    }

    public function getZohoModuleName()
    {
        return $this->zohoModuleName;
    }

    public function getZohoApiOperationType()
    {
        return $this->zohoApiOperationType;
    }

    public function getZohoXmlColumn()
    {
        return $this->zohoXmlColumn;
    }

    public function getZohoAuthToken()
    {
        return $this->zohoAuthToken;
    }

    public function getZohoScope()
    {
        return $this->zohoScope;
    }

    public function getExtendedParameter()
    {
        return $this->uriParameterExtended;
    }

    public function getFullRequestUriToZoho()
    {
        $ret = $this->requestUriToZoho;
        foreach ($this->uriParameter as $key => $value) {
            $ret .= "&$key=$value";
        }
        return $ret;
    }

    public function getXMLData()
    {
        return $this->xmlData;
    }

    private function XMLGeneration($xmlArray, $rowsCount = 1, $rowsName = 'row', $keyAdd = true)
    {
        $xmlData = '';
        foreach ($xmlArray as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) $finalOutput = $this->XMLGeneration($value, $key, $rowsName);
                else $finalOutput = $this->XMLGeneration($value, 1, $key, false);
            } else {
                $finalOutput = $value;
            }
            if (is_string($key) && (is_string($value) || is_integer($value) || is_double($value) || !array_key_exists(1, $value))) {
                $xmlData .= "<FL val='$key'><![CDATA[$finalOutput]]></FL>";
            }
            else $xmlData .= $finalOutput;
        }

        if ($keyAdd === true) return "<$rowsName no='$rowsCount'>$xmlData</$rowsName>";
        return $xmlData;
    }

    protected function buildXML()
    {
        if (empty($this->zohoXmlColumn))
            return 'Please set the XML values correctly';

        $xmlData = '';
        $count = 1;
        foreach ($this->zohoXmlColumn as $key => $value) {
            $xmlString = $this->XMLGeneration($value, 1, 'row', false);
            if ($xmlString == '') {
                $count++;
                continue;
            }
            $xmlData .= "<row no='$count'>" . $xmlString . "</row>";
            $count++;
        }

        $this->xmlData = "<?xml version='1.0' encoding='{$this->zohoEncoding}'?>" . "<{$this->zohoModuleName}>" . $xmlData . "</{$this->zohoModuleName}>";

        return true;
    }

    protected function buildRequestUri()
    {
        if (empty($this->zohoApiUrl))
            return 'Please set the Zoho URL correctly';
        else if (empty($this->zohoModuleName))
            return 'Please set the Zoho Module correctly';
        else if (empty($this->zohoApiOperationType))
            return 'Please set the Zoho Operation type correctly';
        else if (empty($this->zohoAuthToken))
            return 'Please set the Zoho Authtoken correctly';
        else if (empty($this->zohoScope))
            return 'Please set the Zoho scope correctly';

        $this->requestUriToZoho = $this->zohoApiUrl . '/' . $this->zohoModuleName . '/' . $this->zohoApiOperationType . "?authtoken={$this->zohoAuthToken}&scope={$this->zohoScope}&newFormat=1";

        return true;
    }

    private function setParameter($key, $value)
    {
        $this->uriParameter["$key"] = $value;
        return true;
    }

    protected function buildUriParameter()
    {
        $this->buildXML();

        if (isset($this->xmlData) && $this->xmlData != '')
            $this->setParameter('xmlData', $this->xmlData);

        if (isset($this->wfTrigger) && $this->wfTrigger != 'false')
            $this->setParameter('wfTrigger', $this->wfTrigger);

        if ($this->multipleOperation != 'false')
            $this->setParameter('version', $this->multipleOperation);

        if (isset($this->uriParameterExtended) && is_array($this->uriParameterExtended)) {
            foreach ($this->uriParameterExtended as $key => $value)
                $this->setParameter($key, $value);
        }

        return true;
    }

    protected function sendCurl()
    {
        if (!isset($this->requestUriToZoho) || strlen($this->requestUriToZoho) == 0) return "Request URI not set";
        try {
            /* initialize curl handle */
            $ch = curl_init();
            /* set url to send request */
            curl_setopt($ch, CURLOPT_URL, $this->requestUriToZoho);
            /* allow redirects */
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            /* return a response into a variable */
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            /* times out after 30s */
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            /* set POST method */
            curl_setopt($ch, CURLOPT_POST, 1);
            /*To activate RC4-SHA which causes the SSL connection error
            **Zoho uses RC4-SHA, which was not enabled in cURL by default*/
            //curl_setopt( $ch, CURLOPT_SSL_CIPHER_LIST, 'rsa_rc4_128_sha' );
            /* add POST fields parameters */
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->uriParameter);
            /* execute the cURL */
            $this->zohoResponse = curl_exec($ch);

            if (FALSE === $this->zohoResponse) {
                throw new Exception(curl_error($ch), curl_errno($ch));
            }

            curl_close($ch);
        } catch (Exception $exception) {
            $this->zohoResponse = $exception;
            echo 'Exception Message: ' . $exception->getMessage() . '<br/>';
            echo 'Exception Trace: ' . $exception->getTraceAsString();
        }

        return $this->zohoResponse;
    }

    abstract public function doRequest();
}

?>