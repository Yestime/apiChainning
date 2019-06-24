<?php

namespace apiChain;

class apiChain {
    public $parentData = false;
    public $callsRequested = 0;
    public $callsCompleted = 0;
    public $globals;
    public $responses = [];
    public $lastResponse;

    private $handler;
    private $saveHandler;
    private $chain;

    /**
     * apiChain constructor.
     * @param $chain
     * @param bool $handler
     * @param bool $lastResponse
     * @param array $globals
     * @param bool $parentData
     * @param callable|false $saveHandler
     * @throws ApiChainError
     */
    function __construct($chain, $handler = false, $lastResponse = false, $globals = [], $parentData = false, $saveHandler = false) {
        $this->chain = json_decode($chain);

        if (json_last_error()) {
            throw new ApiChainError('Error while parsing chain config: ' . json_last_error_msg());
        }

        $this->parentData = $parentData;
        $this->handler = $handler;
        $this->saveHandler = $saveHandler;
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];
        $this->responses[] = $lastResponse;
        $this->globals = $globals;
        $this->callsRequested = count($this->chain);

        $this->run($handler);
    }

    public function getCallPer() {
        return $this->callsCompleted / $this->callsRequested;
    }

    public function getOutput() {
        $this->responses = array_slice($this->responses, 1);
        return json_encode($this);
    }

    public function getRawOutput() {
        $this->responses = array_slice($this->responses, 1);
        return $this;
    }

    /**
     * @param $handler
     * @throws ApiChainError
     */
    public function run($handler) {
        foreach ($this->chain as $link) {
            if (is_array($link)) {
                $this->callsRequested--;

                $this->lastResponse = $this->parentData ? $this->parentData : ArrayUtils::last($this->responses);
                $this->parentData = $this->parentData ?: $this->lastResponse;

                $newChain = new apiChain(json_encode($link), $handler, $this->lastResponse, $this->globals);
                $this->responses[] = [$newChain->getRawOutput()];
            } elseif ( !$this->validateLink($link) ) {
                return;
            }
        }
    }

    private function validateLink($link) {
        $response = end($this->responses);
        $link->doOn = trim($link->doOn);

        $link->url = $this->replaceGlobals($link->url);
        $link->url = $this->replacePlaceholders($link->url, $response);

        if ($link->doOn != 'always' && !empty($link->doOn)) {
            $link->doOn = $this->replacePlaceholders($link->doOn, $response, true);

            // TODO review code to ensure no workarounds/ hacks
            if (!$this->isValidCondition($link->doOn)) {
                return false;
            }

            // TODO change to case insensitive
            $link->doOn = str_replace(
                ['*', '|', 'regex'],
                ['.+', ' || ', 'preg_match'],
                $link->doOn);

            // Identify Status Codes, Wildcards, and REGEX
            // TODO: add in regex to ignore numbers not by themselves, currently based on if in quotes or wildcard
            $link->doOn = preg_replace('/(([0-9]|(\.\+)){2,3})/', 'preg_match("/$1/", ' . $response->status . ')', $link->doOn);

            if ( !$this->evaluate($link->doOn) ) {
                return false;
            }
        }

        foreach ($link->data as $k => $v) {
            $link->data->$k = $this->replacePlaceholders($v, $response);
        }


        $link->headers = (isset($link->headers) ? $link->headers : []);
        foreach ($link->headers as $name => $val) {
            $link->headers->$name = $this->replacePlaceholders($val, $response);
        }

        $data = $this->handler($link->url, $link->method, $link->data, $link->headers);

        $newResponse = new apiResponse($link->url, $link->method, $data['status'], $data['headers'], $data['body'], $link->return);

        if ( isset($link->globals) ) {
            foreach ($link->globals as $k => $v) {
                $this->globals[$k] = $newResponse->retrieveData($v);
            }
        }

        if ( is_callable($this->saveHandler) ) {
            call_user_func($this->saveHandler, $link->name, $link->url, $link->method, $link->headers, $link->data, $this->globals, $newResponse);
        }

        $this->responses[] = $newResponse;
        $this->callsCompleted++;

        return true;
    }

    private function evaluate($str) {
        try {
            return eval('return ' . $str . ';');
        } catch (\ParseError $e) {
            return false;
        }
    }

    private function isValidCondition($condition) {
        return !preg_match('/[^a-z0-9\s\|&!\(\)\*\'"\\=]|([a-z\s]+\()|^[a-z\s_\-\(\)]+$/i', $condition);
    }

    private function replaceGlobals($content) {
        if (preg_match_all('/\${?global\.([a-z0-9_\.]+)}?/i', $content, $matches)) {
            foreach($matches as $index => $match){
                return str_replace($match[0], $this->globals[$match[1]], $content);
            }
        }

        return $content;
    }

    private function replacePlaceholders($content, $response, $withQuotes = false) {

        if(gettype($content) == "string"){
            while( strpos($content,"\$") !==false ){
                if (preg_match('/(\${?[a-z:_]+(\[[0-9]+\])?)(\.[a-z:_]+(\[[0-9]+\])?)*}?/i', $content, $match)) {
                    if ($response instanceof apiResponse) {

                        $value = $response->retrieveData(substr($match[0], 1));
                        //$value = is_string($value)?$value:'';
                        if(is_array($value)){
                            // dd([$content,$match[0],$value]);
                        }
                        $content = str_replace($match[0], ($withQuotes ? sprintf('"%s"', $value) : $value), $content);


                    }
                }
            }
        }
        return $content;
    }

    private function handler($resource, $method, $body, $requestHeaders) {
        if ( is_callable($this->handler) ) {
            return call_user_func($this->handler, $resource, $method, $requestHeaders, $body);
        }

        return [
            'status' => 0,
            'headers' => [],
            'body' => ''
        ];
    }
}
