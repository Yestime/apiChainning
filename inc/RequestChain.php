<?php

namespace apiChain;


class RequestChain {
    private $defaultRule;
    private $rules;
    private $handler;
    private $saveHandler;
    private $globals;

    public function __construct(array $defaultRule) {
        $this->defaultRule = $defaultRule;
        $this->rules = [];
        $this->globals = [];
        $this->handler = function () {};
        $this->saveHandler = function () {};
    }

    public function addRule(array $value) {
        $this->rules[] = array_merge($this->defaultRule, $value);
    }

    public function setHandler(callable $callback) {
        $this->handler = $callback;
    }

    public function onResponse(callable $callback) {
        $this->saveHandler = $callback;
    }

    /**
     * @return RequestChainResult
     * @throws ApiChainError
     */
    public function run() {
        $chain = new apiChain(json_encode($this->rules), $this->handler, false,
            $this->globals, false, $this->saveHandler);

        return new RequestChainResult($chain->responses);
    }
}