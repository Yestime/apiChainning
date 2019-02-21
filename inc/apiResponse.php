<?php

namespace apiChain;

class apiResponse {
    public $url;
    public $method;
    public $status;
    public $response;

    use UtilsTrait;

    function __construct($resource, $method, $status, $headers, $body, $return) {
        $this->url = $resource;
        $this->method = $method;
        $this->status = $status;

        $this->response = new HTTPResponse();
        $this->response->setHeaders($headers);
        $this->response->setBody($body);

        $this->processBody($return);
    }

    public function processBody($return) {
        if ($return !== true || is_array($return)) {
            $body = [];

            foreach ($return as $alias => $propertyPath) {
                if ( is_numeric($alias) ) {
                    $alias = $propertyPath;
                }

                

                if($this->hasCallBacks($propertyPath)){

                    $value = $this->valueFromCallBacks($propertyPath);
                }
                else{
                    $value = $this->valueFromBody($propertyPath);
                }

                
                $body = $this->response->assignValueByPath($body, $alias, $value);
            }

            $this->response->setBody( $this->normalizeBody($body) );
        }
    }

    public function valueFromBody($path) {
        $value = $this->response->getValue('body.' . $path);
        return ($value === null ? $this->response->getValueByPath($path) : $value);
    }

    private function normalizeBody($body) {
        ArrayUtils::walkValues($body, function (&$val) {
            if ( is_array($val) && ArrayUtils::allNumericKeys($val) && !ArrayUtils::isSequential($val) ) {
                $val = ArrayUtils::fillMissingNumericKeys($val, null);
                ksort($val);
            }
        });

        return json_decode( json_encode($body) );
    }

    public function getUrl() {
        return $this->url;
    }

    public function asJSON() {
        return json_encode($this->response->body);
    }

    public function retrieveData($property) {
        return $this->response->getValue($property);
    }


    public function hasCallBacks($variableString){
        return preg_match('/^\${callback_(.+)}$/i', $variableString);
    }

    public function parseCallBacks($variableString){
        if (preg_match('/^\${callback_(.+)}$/i', $variableString, $match)) {
             return $match[1];
        }

        return false;
    }

    public function parseCallBackParams($variableString){
        if (preg_match('/^.+\((.+)\)$/i', $variableString, $match)) {
             return explode(",",$match[1]);

        }

        return false;
    }

    public function valueFromCallBacks($variableString){


        $callbackString = $this->parseCallBacks($variableString);

        if($callbackString){
            

            $paramsStringArray = $this->parseCallBackParams($callbackString);

            if($paramsStringArray){
                $params = [];

                foreach( $paramsStringArray as $index => $paramsPath ){
                    

                        $params[$index] = is_numeric($paramsPath)?$paramsPath:$this->valueFromBody($paramsPath);
                    

                }

                $callbackArray = explode("(", $callbackString);

                $functionName = $callbackArray[0];

                $value  = $this->executeCallBack($functionName, $params);


                
                return $this->setNegativeConditionDefaultType($value);
            }

        }

        return false;
    }

     protected function executeCallBack($functionName, Array $params){
         
            
            if(function_exists($functionName) ){

                return call_user_func_array($functionName, $params);
            }
            else if(method_exists($this, $functionName)){
                
                return call_user_func_array(array($this, $functionName), $params);
            }
        return false;
     }

     protected function setNegativeConditionDefaultType($value){

        switch(gettype($value)){
        case "boolean":
            return $value == true?$value:false;
        case "integer":
            return $value?$value:0;
        case  "double": 
            return $value?$value:0.0;
        case  "string":
            return strlen($value)?$value:"";
        case "array":
            return !empty($value)?$value:false;
        case "object":
            return $value?$value:null;
        case "resource": 
            return $value?$value:null;
        case "NULL":
            return $value;
        case "unknown type":
            return $value?$value:null;
        default:
            return $value?$value:null;
        }
     }
}