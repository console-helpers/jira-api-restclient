<?php
/*
 * The MIT License
 *
 * Copyright (c) 2014 Shuhei Tanuma
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace chobie\Jira\Api\Client;


use chobie\Jira\Api\Authentication\Anonymous;
use chobie\Jira\Api\Authentication\AuthenticationInterface;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Api\Exception;
use chobie\Jira\Api\UnauthorizedException;

class PHPClient implements ClientInterface
{

	/**
	 * HTTPS support enabled.
	 *
	 * @var boolean
	 */
	protected $httpsSupport = false;

	/**
	 * Last error message.
	 *
	 * @var string
	 */
	private $_lastErrorMessage = '';

	/**
	 * Create a traditional php client.
	 */
	public function __construct()
	{
		$wrappers = stream_get_wrappers();

		if ( in_array('https', $wrappers) ) {
			$this->httpsSupport = true;
		}

	}

	/**
	 * Returns status of HTTP support.
	 *
	 * @return boolean
	 *
	 * @codeCoverageIgnore
	 */
	protected function isHttpsSupported()
	{
		return $this->httpsSupport;
	}

	/**
	 * Sends request to the API server.
	 *
	 * @param string                  $method     Request method.
	 * @param string                  $url        URL.
	 * @param array|string            $data       Request data.
	 * @param string                  $endpoint   Endpoint.
	 * @param AuthenticationInterface $credential Credential.
	 * @param boolean                 $is_file    This is a file upload request.
	 * @param boolean                 $debug      Debug this request.
	 *
	 * @return array|string
	 * @throws \InvalidArgumentException When non-supported implementation of AuthenticationInterface is given.
	 * @throws \InvalidArgumentException When data is not an array and http method is GET.
	 * @throws Exception When request failed due communication error.
	 * @throws UnauthorizedException When request failed, because user can't be authorized properly.
	 * @throws Exception When there was empty response instead of needed data.
	 * @throws \InvalidArgumentException When "https" wrapper is not available, but http:// is requested.
	 */
	public function sendRequest(
		$method,
		$url,
		$data = array(),
		$endpoint,
		AuthenticationInterface $credential,
		$is_file = false,
		$debug = false
	) {
		if ( !($credential instanceof Basic) && !($credential instanceof Anonymous) ) {
			throw new \InvalidArgumentException(sprintf(
				'PHPClient does not support %s authentication.',
				get_class($credential)
			));
		}

		$header = array();

		if ( !($credential instanceof Anonymous) ) {
			$header[] = 'Authorization: Basic ' . $credential->getCredential();
		}

		if ( !$is_file ) {
			$header[] = 'Content-Type: application/json;charset=UTF-8';
		}

		$context = array(
			'http' => array(
				'method' => $method,
				'header' => implode("\r\n", $header),
				'ignore_errors' => true,
			),
		);

		if ( $method == 'POST' || $method == 'PUT' || $method == 'DELETE' ) {
			if ( $is_file ) {
				$filename = preg_replace('/^@/', '', $data['file']);
				$name = ($data['name'] !== null) ? $data['name'] : basename($filename);
				$boundary = '--------------------------' . microtime(true);
				$header[] = 'X-Atlassian-Token: nocheck';
				$header[] = 'Content-Type: multipart/form-data; boundary=' . $boundary;

				$__data = '--' . $boundary . "\r\n" .
					'Content-Disposition: form-data; name="file"; filename="' . $name . "\"\r\n" .
					"Content-Type: application/octet-stream\r\n\r\n" .
					file_get_contents($filename) . "\r\n";
				$__data .= '--' . $boundary . "--\r\n";
			}
			else {
				$__data = json_encode($data);
			}

			$header[] = sprintf('Content-Length: %d', strlen($__data));

			$context['http']['header'] = implode("\r\n", $header);
			$context['http']['content'] = $__data;
		}
		elseif ( $method == 'GET' ) {
			if ( !is_array($data) ) {
				throw new \InvalidArgumentException('Data must be an array.');
			}

			$url .= '?' . http_build_query($data);
		}

		// @codeCoverageIgnoreStart
		if ( strpos($endpoint, 'https://') === 0 && !$this->isHttpsSupported() ) {
			throw new \InvalidArgumentException('does not support https wrapper. please enable openssl extension');
		}
		// @codeCoverageIgnoreEnd

		list ($http_code, $response, $error_message) = $this->doSendRequest($endpoint . $url, $context);

		// Check for 401 code before "$error_message" checking, because it's considered as an error.
		if ( $http_code == 401 ) {
			throw new UnauthorizedException('Unauthorized');
		}

		if ( !empty($error_message) ) {
			throw new Exception(
				sprintf('Jira request failed: "%s"', $error_message)
			);
		}

		if ( $response === '' && !in_array($http_code, array(201, 204)) ) {
			throw new Exception('JIRA Rest server returns unexpected result.');
		}

		// @codeCoverageIgnoreStart
		if ( $response === null ) {
			throw new Exception('JIRA Rest server returns unexpected result.');
		}
		// @codeCoverageIgnoreEnd

		return $response;
	}

	/**
	 * Sends the request.
	 *
	 * @param string $url     URL.
	 * @param array  $context Context.
	 *
	 * @return array
	 */
	protected function doSendRequest($url, array $context)
	{
		$this->_lastErrorMessage = '';

		set_error_handler(array($this, 'errorHandler'));
		$response = file_get_contents($url, false, stream_context_create($context));
		restore_error_handler();

		if ( isset($http_response_header) ) {
			preg_match('#HTTP/\d+\.\d+ (\d+)#', $http_response_header[0], $matches);
			$http_code = $matches[1];
		}
		else {
			$http_code = 0;
		}

		return array($http_code, $response, $this->_lastErrorMessage);
	}

	/**
	 * Remembers last error.
	 *
	 * @param integer $errno  Error number.
	 * @param string  $errstr Error message.
	 *
	 * @return void
	 */
	public function errorHandler($errno, $errstr)
	{
		$this->_lastErrorMessage = $errstr;
	}

}
