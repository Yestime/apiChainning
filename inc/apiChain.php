<?php
namespace apiChain;

class ApiChainError extends \Exception {}

class apiChain {
	private $handler;
	private $chain;
	public $parentData = false;
	public $callsRequested = 0;
	public $callsCompleted = 0;
	private $headers = array();
	public $globals;
	public $responses = array();
	public $lastResponse;
	
	function __construct($chain, $handler = false, $lastResponse = false, $globals = array(), $parentData = false) {
		$this->chain = json_decode($chain);

        if ( json_last_error() ) {
            throw new ApiChainError( 'Error while parsing chain config: ' . json_last_error_msg() );
        }

        $this->parentData = $parentData;
		$this->handler = $handler;
		// getallheaders() exists only in Apache environment
		$this->headers = function_exists('getallheaders') ? getallheaders() : [];
		$this->responses[] = $lastResponse;
		$this->globals = $globals;

		$this->callsRequested = count($this->chain);

		foreach($this->chain as $link) {
			if (is_array($link)) {
				// Handle Nested Chains
				//@todo test functionality
				$this->callsRequested--;

				if (!$this->parentData) {
					$lastResponse = array_pop($this->responses);
					$this->parentData = $lastResponse;
					$this->responses[] = $lastResponse;
				} else {
					$lastResponse = $this->parentData;
				}

				$this->lastResponse = $lastResponse;
				$newChain = new apiChain(json_encode($link), $handler, $lastResponse, $this->globals);
				$this->responses[] = array($newChain->getRawOutput());

			} elseif (!$this->validateLink($link)) {
				// End Chain and Return
				return $this;
			}
		}
	}
	
	private function validateLink($link) {
		$response = end($this->responses);
        $link->doOn = trim($link->doOn);

		// Replace Globals
		if (preg_match('/\${?global\.([a-z0-9_\.]+)}?/i', $link->href, $match)) {
			$link->href = str_replace($match[0], $this->globals[$match[1]], $link->href);
		} 
		
        // Replace Placeholders
        if (preg_match('/(\${?[a-z:_]+(\[[0-9]+\])?)(\.[a-z:_]+(\[[0-9]+\])?)*}?/i', $link->href, $match)) {
            $link->href = str_replace($match[0], $response->retrieveData(substr($match[0],1)), $link->href);
        }
		
		if ($link->doOn != 'always' && !empty($link->doOn)) {
			// Replace Placeholders
			if (preg_match('/(\${?[a-z:_]+(\[[0-9]+\])?)(\.[a-z:_]+(\[[0-9]+\])?)*}?/i', $link->doOn, $match)) {
				$link->doOn = str_replace($match[0], '"'.$response->retrieveData(substr($match[0],1)).'"', $link->doOn);
			}
			
			// Prevent PHP Code from being run by evil hackers
			//@todo review code to ensure no workarounds/ hacks
			if (preg_match('/[^a-z0-9\s\|&!\(\)\*\'"\\=]|([a-z\s]+\()|^[a-z\s_\-\(\)]+$/i', $link->doOn)) {
				return false;
			}
			
			// Replace Logic Conditions
			// @todo change to case insensitive
			$link->doOn = str_replace(
				array('*', '|', 'regex'),
				array('.+', ' || ', 'preg_match'),
				$link->doOn);
			
			// Identify Status Codes, Wildcards, and REGEX
			// @todo add in regex to ignore numbers not by themselves, currently based on if in qoutes or wildcard
			//$link->doOn = preg_replace('/[^\'"][0-9]{3}[^\'"]/', $response->status.' == $1', $link->doOn);
			$link->doOn = preg_replace('/(([0-9]|(\.\+)){2,3})/', 'preg_match("/$1/", '.$response->status.')', $link->doOn);
			
			// Evaluate Logical Statement
            try {
                eval('return ' . $link->doOn .';');
            } catch (\ParseError $e) {
                return false;
            }
		}
		
		// Replace Placeholders
		foreach ($link->data as $k => $v) {
			if (preg_match('/(\${?[a-z:_]+(\[[0-9]+\])?)(\.[a-z:_]+(\[[0-9]+\])?)*}?/i', $v, $match)) {
				$link->data->$k = str_replace($match[0], $response->retrieveData(substr($match[0],1)), $v);
			}
			
			// Globals
			if (preg_match('/\${?global\.([a-z0-9_\.]+)}?/i', $v, $match)) {
				$link->data->$k = str_replace($match[0], $this->globals[$match[2]], $link->data);
			}
		}
		
		$data = $this->handler($link->href, $link->method, $link->data);
		
		$this->responses[] = $newResp = new apiResponse($link->href, $link->method, $data['status'], $data['headers'], $data['body'], $link->return);
		
		if (isset($link->globals)) {
			foreach ($link->globals as $k => $v) {
				$this->globals[$k] = $newResp->retrieveData($v);
			}
		}
		
		$this->callsCompleted++;
		
		return true;
	}
	
	private function handler($resource, $method, $body) {
		if ( is_callable($this->handler) ) {
			return call_user_func($this->handler, $resource, $method, $this->headers, $body);
		}

		return [
		    'status' => 0,
            'headers' => [],
            'body' => ''
        ];
	}
	
	public function getCallPer() {
		return $this->callsCompleted / $this->callsRequested;
	}
	
	public function getOutput() {
		array_shift($this->responses);
		return json_encode($this);
	}
	
	public function getRawOutput() {
		array_shift($this->responses);
		return $this;
	}
}
