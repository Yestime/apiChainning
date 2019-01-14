<?php

namespace apiChain;


class RequestChainResult {
    private $responses;

    public function __construct($responses) {
        $this->responses = $responses;
    }

    /**
     * @return apiResponse|null
     */
    public function getLastResponse() {
        return ArrayUtils::last($this->responses);
    }
}