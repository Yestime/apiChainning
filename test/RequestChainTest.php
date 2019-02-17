<?php

use PHPUnit\Framework\TestCase;
use apiChain\RequestChain;

class RequestChainTest extends TestCase {
    /** @var RequestChain */
    private $chain;
    const RESOURCE_DIR = __DIR__ . '/resources';

    public function setUp() {
        $this->chain = new RequestChain([
            'doOn' => 'always',
            'url' => '/',
            'method' => 'get',
            'data' => [],
            'return' => true,
        ]);

        if ( !is_dir(self::RESOURCE_DIR) ) {
            mkdir(self::RESOURCE_DIR);
        }
    }

    public function tearDown() {
        $removeDir = function ($dir) {
            foreach (scandir($dir) as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    unlink($dir . DIRECTORY_SEPARATOR . $entry);
                }
            }

            rmdir($dir);
        };

        if ( is_dir(self::RESOURCE_DIR) ) {
            $removeDir(self::RESOURCE_DIR);
        }
    }

    public function testSaveHandlerOnSaveToFileOnProcessResponse() {
        $this->chain->addRule([
            'url' => '1',
            'return' => ['key1' => 'alias1', 'key2' => 'alias2'],
        ]);

        $this->chain->addRule([
            'url' => '2',
            'return' => ['test' => 'testAlias', 'nonexistent' => 'nonexistentAlias'],
        ]);
        $this->chain->setHandler(function () {
            return [
                'status' => 200,
                'headers' => [],
                'body' => [
                    'key1' => 'val1',
                    'key2' => 'val2',
                    'test' => 'val'
                ]
            ];
        });


        $this->chain->onResponse(function (\apiChain\apiResponse $response) {
            file_put_contents(self::RESOURCE_DIR . '/' . $response->getUrl(), $response->asJSON());
        });

        $this->chain->run();

        $content1 = file_get_contents(self::RESOURCE_DIR . '/1');
        $this->assertEquals(['alias1' => 'val1', 'alias2' => 'val2'], json_decode($content1, true));

        $content2 = file_get_contents(self::RESOURCE_DIR . '/2');
        $this->assertEquals(['testAlias' => 'val', 'nonexistentAlias' => null], json_decode($content2, true));
    }
}