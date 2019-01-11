<?php
namespace apiChain;

class apiResponse {

	public $href;
	public $method;
	public $status;
	public $response;
	
	
	function __construct($resource, $method, $status, $headers, $body, $return) {
		$this->href = $resource;
		$this->method = $method;
		$this->status = $status;
		$this->response = new \stdClass;
		$this->response->headers = $headers;
		$this->response->body = $body;
		if ($return != true || is_array($return)) {
			$body = array();
			foreach($return as $v) {
				$tmpValue = $this->retrieveData('body.'.$v);
				$this->assignArrayByPath($body, $v, $tmpValue);
			}
			//@todo iterate for casting once arrays are available (album[0], album[1], etc);
			$this->response->body = json_decode(json_encode($body));
		}
	}
	
	// courtesty http://stackoverflow.com/users/31671/alex
	function assignArrayByPath(&$arr, $path, $value, $separator='.') {
		$keys = $this->parsePath($path, $separator);

		foreach ($keys as $key) {
			$arr = &$arr[$key];
		}

		$arr = $value;
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

        return $this->array_flatten($result);
    }

    /**
     * Recursively flatten nested array
     * @example [[1,2,3], [4,5]] => [1,2,3,4,5]
     * @param array $arr
     * @return array
     */
    private function array_flatten($arr) {
        if (!is_array($arr)) {
            return [];
        }
        $result = [];
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $result = array_merge($result, $this->array_flatten($val));
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }

	
	function retrieveData($property) {
        $property = str_replace(array('{','}'), '', $property);
		$parts = explode('.', $property);
		$resp = $this->response;

		foreach ($parts as $part) {
			$match = array();
			if (preg_match('/\[(\'|")?[a-z0-9_\s](\'|")?\]+/i', $part, $match)) {
				$part = str_replace($match, '', $part);
			}
			
            if (!isset($resp->$part)) {
                return null;
            }
			$resp = $resp->$part;

			if ($match) {
				foreach ($match as $m) {
					$m = preg_replace('/[\[\'"\]]/', '', $m);
					if (isset($resp)) {
						foreach ($resp as $k => $v) {
							if ($k == $m) {
								$resp = $v;
							}
						}
					}
				}
			}
		}

		return $resp;

	}
}