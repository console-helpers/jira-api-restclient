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

class CurlClient implements ClientInterface
{

	/**
	 * create a traditional php client
	 */
	public function __construct()
	{
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
				'CurlClient does not support %s authentication.',
				get_class($credential)
			));
		}

		$curl = curl_init();

		if ( $method == 'GET' ) {
			if ( !is_array($data) ) {
				throw new \InvalidArgumentException('Data must be an array.');
			}

			$url .= '?' . http_build_query($data);
		}

		curl_setopt($curl, CURLOPT_URL, $endpoint . $url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

		if ( !($credential instanceof Anonymous) ) {
			curl_setopt($curl, CURLOPT_USERPWD, sprintf('%s:%s', $credential->getId(), $credential->getPassword()));
		}

		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_VERBOSE, $debug);

		if ( $is_file ) {
			if ( defined('CURLOPT_SAFE_UPLOAD') && PHP_VERSION_ID < 70000 ) {
				curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
			}

			curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Atlassian-Token: nocheck'));
		}
		else {
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=UTF-8'));
		}

		if ( $method == 'POST' ) {
			curl_setopt($curl, CURLOPT_POST, 1);

			if ( $is_file ) {
				$data['file'] = $this->getCurlValue($data['file'], $data['name']);
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			}
			else {
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
			}
		}
		elseif ( $method == 'PUT' || $method == 'DELETE' ) {
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		}

		$response = curl_exec($curl);

		$error_number = curl_errno($curl);

		if ( $error_number > 0 ) {
			throw new Exception(
				sprintf('Jira request failed: code = %s, "%s"', $error_number, curl_error($curl))
			);
		}

		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		// If empty result and status != "204 No Content".
		if ( $http_code == 401 ) {
			throw new UnauthorizedException('Unauthorized');
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
	 * If necessary, replace curl file @ string with a CURLFile object (for PHP 5.5 and up)
	 *
	 * @param string $file_string The string in @-format as it is used on PHP 5.4 and older.
	 * @param string $name        Name of attachment (optional).
	 *
	 * @return \CURLFile|string
	 */
	protected function getCurlValue($file_string, $name = null)
	{
		if ( $name === null ) {
			$name = basename($file_string);
		}

		if ( !function_exists('curl_file_create') ) {
			return $file_string . ';filename=' . $name;
		}

		return curl_file_create(substr($file_string, 1), '', $name);
	}

}
