<?php

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase {

    public function testSettingPropertiesIfReturnIsTrue() {
        $response = new \apiChain\apiResponse('/path', 'get',
            200, ['header' => 'value'], [], true);

        $this->assertEquals('/path', $response->href);
        $this->assertEquals('get', $response->method);
        $this->assertEquals(200, $response->status);
        $this->assertEquals(['header' => 'value'], $response->response->getHeaders());
        $this->assertEquals([], $response->response->body);
    }

    public function testSettingBodyBasedOnReturn() {
        $tests = [
            [[], ['key'], function ($body) {
                $this->assertEquals([], $body->key);
            }],

            [['key' => 'val'], ['nonexistent'], function ($body) {
                $this->assertEquals([], $body->nonexistent);
            }],

            [['key' => 'val'], ['key'], function ($body) {
                $this->assertEquals('val', $body->key);
            }],

            [['key' => 'val'], ['{key}'], function ($body) {
                $this->assertEquals('val', $body->{'{key}'});
            }],

            [['path' => ['to' => 'val']], ['path.to'], function ($body) {
                $this->assertEquals('val', $body->path->to);
            }],

            [['arr' => ['val']], ['arr[0]'], function ($body) {
                $this->assertEquals('val', $body->arr[0]);
            }],

            [['path' => ['arr' => ['val1', 'val2']]], ['path.arr[0]', 'path.arr[1]'], function ($body) {
                $this->assertEquals('val1', $body->path->arr[0]);
                $this->assertEquals('val2', $body->path->arr[1]);
            }],

            [['path' => ['arr' => ['val1', 'val2']]], ['path.arr[1]'], function ($body) {
                $this->assertEquals('val2', $body->path->arr[1]);
            }],

            [[
                'test' => 'val',
                'arr' =>
                    [
                        ['nested' => 'val1'],
                        ['nested' => 'val2']
                    ]
            ], ['test', 'arr[0].nested', 'arr[1].nested'], function ($body) {
                $this->assertEquals('val', $body->test);
                $this->assertEquals('val1', $body->arr[0]->nested);
                $this->assertEquals('val2', $body->arr[1]->nested);
            }],


            [['path' => ['arr' => ['val1', 'val2']]], ['path.arr[0]' => 'alias1', 'path.arr[1]' => 'alias2'], function ($body) {
                $this->assertEquals('val1', $body->alias1);
                $this->assertEquals('val2', $body->alias2);
            }],


            [['people' => [['name' => 'Joe'], ['name' => 'Bob']]], ['$.people.*.name' => 'people'], function ($body) {
                $this->assertEquals(['Joe', 'Bob'], $body->people);
            }],

        ];

        array_walk($tests, function ($item) {
            list ($body, $return, $assertCallback) = $item;

            $response = $this->createResponse($body, $return);
            $assertCallback($response->response->getBody());
        });
    }

    public function testInvalidJSONPathReturnsNull() {
        $response = $this->createResponse([], ['$$' => 'test']);
        $this->assertNull($response->response->getBody()->test);
    }

    private function createResponse($body, $return) {
        return new \apiChain\apiResponse('', '', 0, [], $this->arrayToObject($body), $return);
    }

    private function arrayToObject(array $arr) {
        return json_decode(json_encode($arr));
    }
}