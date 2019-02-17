<?php

namespace apiChain;

use Flow\JSONPath\JSONPath;
use Flow\JSONPath\JSONPathException;

class HTTPResponse {
    public $headers;
    public $body;

    /** @var JSONPath */
    private $jsonPath;

    public function getHeaders() {
        return $this->headers;
    }

    public function setHeaders($headers) {
        $this->headers = $headers;
    }

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        $this->body = $body;
        $this->jsonPath = new JSONPath($body);
    }

    public function assignValueByPath($arr, $path, $value, $separator = '.') {
        return ArrayUtils::setValueByPath($arr, $this->parsePath($path, $separator), $value);
    }

    public function getValue($path, $pathSeparator = '.') {
        $path = str_replace(['{', '}'], '', $path);
        $parts = $this->parsePath($path, $pathSeparator);
        $resp = $this;

        foreach ($parts as $part) {
            if ( isset($resp->$part) ) {
                $resp = $resp->$part;
            } else if ( is_array($resp) && array_key_exists($part, $resp) ) {
                $resp = $resp[$part];
            } else {
                return null;
            }
        }

        return $resp;
    }

    /**
     * @param $path
     * @return JSONPath
     */
    public function getValueByPath($path) {
        try {
            return $this->jsonPath->find($path);
        } catch (JSONPathException $e) {
            return null;
        }
    }

    /**
     * Parses string path. Considers array subscripts.
     * @example "path.array[0]" => ["path", "array", "0"]
     * @param string $path
     * @param string $separator parts separator
     * @return array
     */
    private function parsePath($path, $separator) {
        $keys = explode($separator, $path);
        $result = [];

        foreach ($keys as $key) {
            $nestedKeys = array_map(function ($part) {
                return trim($part, ']');
            }, explode('[', $key));

            $result[] = $nestedKeys;
        }

        return ArrayUtils::flatten($result);
    }
}