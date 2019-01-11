<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests basic case from index.php
 * Class AcceptanceTest
 */
final class AcceptanceTest extends TestCase {

    public function testBaseCase() {
        $chain = <<<'JSON'
[
  {
    "doOn": "always",
    "href": "/users",
    "method": "get",
    "data": {},
    "return": ["test", "albums[0].userName", "albums[1].userName"]
  },
  {
    "doOn": "$body.test == 'mike'",
    "href": "/albums",
    "method": "get",
    "data": {"user" : "$body.albums[0].userName"},
    "return": true
  },
  {
    "doOn": "2*",
    "href": "/usermike",
    "method": "get",
    "data": {},
    "return": ["user.name"]
  }
]
JSON;

        $chain = new apiChain\apiChain($chain, [$this, 'myCalls']);

        $this->assertEquals(3, $chain->callsCompleted);
        $this->assertEquals(3, $chain->callsRequested);

        $this->assertFalse($chain->responses[0]);

        /** @var \apiChain\apiResponse $usersResponse */
        $usersResponse = $chain->responses[1];
        $this->assertEquals('/users', $usersResponse->href);
        $this->assertEquals('get', $usersResponse->method);

        $body = $usersResponse->response->body;
        $this->assertEquals('mike', $body->test);
        $this->assertEquals('bob', $body->albums[0]->userName);
        $this->assertEquals('joe', $body->albums[1]->userName);


        /** @var \apiChain\apiResponse $albumsResponse */
        $albumsResponse = $chain->responses[2];
        $this->assertEquals('/albums', $albumsResponse->href);
        $this->assertEquals('get', $albumsResponse->method);
        $this->assertEquals('bob', $albumsResponse->response->body->user);
    }

    public function myCalls($resource, $method, $headers, $data) {

        if ($resource == '/albums') {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_decode(sprintf('{
  "user": "%s",
  "albums": [
    {
      "album_type": "album",
      "details": {
        "name": "Cool Album",
        "artist": "cool Artist"
      }
    },
    {
      "album_type": "album",
      "details": {
        "name": "Cool Album 2",
        "artist": "cool Artist 2"
      }
    }
  ]
}', $data->user))];

        } elseif ($resource == '/users') {
            return [
                'status' => 200,
                'headers' => [],
                'body' => json_decode('{
  "test": "mike",
  "albums": [
    {
      "userName": "bob",
      "details": {
        "name": "Cool Album",
        "artist": "cool Artist"
      }
    },
    {
      "userName": "joe",
      "details": {
        "name": "Cool Album 2",
        "artist": "cool Artist 2"
      }
    }
  ]
}')];

        } elseif ($resource == '/usermike') {

            return [
                'status' => 200,
                'headers' => [],
                'body' => json_decode('{
  "user": {
    "album_type": "album",
    "name": {
      "first": "Mike",
      "last": "Stowe"
    }
  }
}')];
        }

        return [];
    }
}