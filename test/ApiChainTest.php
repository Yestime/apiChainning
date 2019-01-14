<?php

use PHPUnit\Framework\TestCase;
use apiChain\apiChain;
use apiChain\apiResponse;

class ApiChainTest extends TestCase {
    /**
     * @expectedException \apiChain\ApiChainError
     * @expectedExceptionMessage Error while parsing chain config: Syntax error
     */
    public function testInvalidJSONThrowsException() {
        new \apiChain\apiChain("invalid json");
    }

    public function testDecreaseCallsRequestedForEachLink() {
        $chain = new apiChain(json_encode([[]]), false);
        $this->assertEquals(0, $chain->callsRequested);
        $this->assertEquals(0, $chain->callsCompleted);
    }

    public function testUseParentDataAsLastResponse() {
        $chain = new apiChain(json_encode([[]]), false, false, [], 'data');
        $this->assertEquals('data', $chain->lastResponse);
    }

    public function testReplaceGlobalsInLink() {
        $config = json_encode([
            $this->createRule(['url' => '/$global.main/${global.sub}/${global.nonexistent}'])
        ]);

        $handler = function ($resource) {
            $this->assertEquals('/path//${global.nonexistent}', $resource);
        };

        new apiChain($config, $handler, $this->createResponse(), [
            'main' => 'path',
            'sub' => 'test'
        ]);
    }

    public function testReplacePlaceholdersInData() {
        $config = json_encode([
            $this->createRule(['data' => ['key' => '$body.key']])
        ]);

        $handler = function ($_1, $_2, $_3, $body) {
            $this->assertEquals('val', $body->key);
        };

        new apiChain($config, $handler, $this->createResponse(['key' => 'val']));
    }

    public function testChainWithoutHandler() {
        $config = json_encode([
            $this->createRule(['globals' => ['key' => 'val']])
        ]);

        $chain = new apiChain($config);
        $this->assertEquals(null, $chain->globals['key']);
    }

    public function testCheckForEvilHackers() {
        $config = json_encode([
            $this->createRule(['doOn' => 'always']),
            $this->createRule(['doOn' => 'hack']),
        ]);

        $chain = new \apiChain\apiChain($config);
        $this->assertEquals(2, $chain->callsRequested);
        $this->assertEquals(1, $chain->callsCompleted);
        $this->assertEquals(0.5, $chain->getCallPer());


        $config = json_encode([
            $this->createRule(['doOn' => '"mike" == \'mike\'']),
        ]);
        $chain = new apiChain($config, false, $this->createResponse());
        $this->assertEquals(1, $chain->callsCompleted);
    }

    public function testEvalFailsByParseError() {
        $config = json_encode([
            $this->createRule(['doOn' => '23\'2']),
        ]);

        $chain = new apiChain($config, false, $this->createResponse());
        $this->assertEquals(1, $chain->callsRequested);
        $this->assertEquals(0, $chain->callsCompleted);
    }

    public function testDefaultOutput() {
        $chain = new apiChain(json_encode([]), false, $this->createResponse());
        $this->assertEquals([
            'parentData' => false,
            'callsRequested' => 0,
            'callsCompleted' => 0,
            'globals' => [],
            'responses' => [],
            'lastResponse' => null,
        ], json_decode($chain->getOutput(), true));
    }

    public function testConditionForEmptyResponse() {
        $config = json_encode([
            $this->createRule([
                'doOn' => '$body == ""',
                'url' => '/test'
            ]),
        ]);

        $handler = function ($resource) {
            $this->assertEquals('/test', $resource);
        };

        $chain = new apiChain($config, $handler, $this->createResponse(''));
        $this->assertEquals(1, $chain->callsCompleted);


        $config = json_encode([
            $this->createRule([
                'doOn' => '$body != ""',
                'url' => '/test'
            ]),
        ]);

        $handler = function ($resource) {
            $this->fail( sprintf("Resource %s requested", $resource) );
        };

        $chain = new apiChain($config, $handler, $this->createResponse(''));
        $this->assertEquals(0, $chain->callsCompleted);
    }

    public function testReturnAliases() {
        $config = json_encode([
            $this->createRule([
                'return' => ['key' => 'alias'],
            ]),
        ]);

        $handler = function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => [
                    'key' => 'val'
                ]
            ];
        };

        $chain = new apiChain($config, $handler, $this->createResponse());

        $response = $chain->responses[1]->response;
        $this->assertEquals('val', $response->body->alias);
        $this->assertFalse( isset($response->body->key) );
    }

    private function createRule(array $partial) {
        return array_merge([
            'doOn' => 'always',
            'url' => '/',
            'method' => 'get',
            'data' => [],
            'return' => true,
        ], $partial);
    }

    private function createResponse($body = null) {
        $body = ($body === null ? [] : $body);
        $bodyObject = json_decode( json_encode($body) );
        return new apiResponse([], '', 0, [], $bodyObject, true);
    }
}