<?php

namespace apiChain;

class HTTPResponse {
    public $headers;
    public $body;

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