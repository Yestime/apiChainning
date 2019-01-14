<?php

namespace apiChain;


class RequestChainResult {
    private $responses;

    public function __construct($responses) {
        $this->responses = $responses;
    }
}