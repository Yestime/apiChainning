<?php

namespace apiChain;

class apiResponse {
    public $href;
    public $method;
    public $status;
    public $response;

    function __construct($resource, $method, $status, $headers, $body, $return) {
        $this->href = $resource;
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

            foreach ($return as $propertyPath => $alias) {
                if ( is_numeric($propertyPath) ) {
                    $propertyPath = $alias;
                }

                $value = $this->valueFromBody($propertyPath);
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
        return $this->href;
    }

    public function asJSON() {
        return json_encode($this->response->body);
    }

    public function valueFromBody($name) {
        return $this->response->getValue('body.' . $name);
    }

    public function retrieveData($property) {
        return $this->response->getValue($property);
    }
}