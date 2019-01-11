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

        if ($return !== true || is_array($return)) {
            $body = [];

            foreach ($return as $propertyPath) {
                $value = $this->response->getValue('body.' . $propertyPath);
                $body = $this->response->assignValueByPath($body, $propertyPath, $value);
            }

            $this->response->setBody( $this->normalizeBody($body) );
        }
    }

    private function normalizeBody($body) {
        Utils::walkArrayValues($body, function (&$val) {
            if ( is_array($val) && Utils::allNumericKeys($val) && !Utils::isSeqArray($val) ) {
                $val = Utils::fillMissingKeys($val, null);
                ksort($val);
            }
        });

        return json_decode( json_encode($body) );
    }

    public function retrieveData($property) {
        return $this->response->getValue($property);
    }
}