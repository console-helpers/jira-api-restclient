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

use chobie\Jira\Api\Authentication\AuthenticationInterface;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Api\Client\ClientInterface;
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
     * send request to the api server
     *
     * @param $method
     * @param $url
     * @param array $data
     * @param $endpoint
     * @param $credential
     * @return array|string
     * @throws Exception
     */
    public function sendRequest($method, $url, $data = array(), $endpoint, AuthenticationInterface $credential, $isFile = false, $debug = false)
    {
        if (!($credential instanceof Basic)) {
            throw new \Exception(sprintf("CurlClient does not support %s authentication.", get_class($credential)));
        }

        $curl = curl_init();

        if ($method == "GET") {
            $url .= "?" . http_build_query($data);
        }

        curl_setopt($curl, CURLOPT_URL, $endpoint . $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERPWD, sprintf("%s:%s", $credential->getId(), $credential->getPassword()));
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_VERBOSE, $debug);
        if ($isFile) {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
            }
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Atlassian-Token: nocheck'));
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json;charset=UTF-8"));
        }
        if ($method == "POST") {
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($isFile) {
                $file = $data['file'];
                $file = ltrim($file, '@');
                $fileData = explode('/', $file);
                $postName = array_pop($fileData);
                $contentType = file_exists($file) ? mime_content_type($file) : null;
                $data['file'] = $this->getCurValueForFile($file, $contentType, $postName);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method == "PUT") {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $data = curl_exec($curl);

        $errorNumber = curl_errno($curl);
        if ($errorNumber > 0) {
            throw new Exception(
                sprintf('Jira request failed: code = %s, "%s"', $errorNumber, curl_error($curl))
            );
        }

        // if empty result and status != "204 No Content"
        if (curl_getinfo($curl, CURLINFO_HTTP_CODE) == 401) {
            throw new UnauthorizedException("Unauthorized");
        }
        if ($data === '' && curl_getinfo($curl, CURLINFO_HTTP_CODE) != 204) {
            throw new Exception("JIRA Rest server returns unexpected result.");
        }

        if (is_null($data)) {
            throw new Exception("JIRA Rest server returns unexpected result.");
        }

        return $data;
    }

    /**
     * Creates a valid file for curl request.
     *
     * @param string      $fileName
     * @param string|null $contentType
     * @param string|null $postName
     *
     * @return \CURLFile|string
     */
    private function getCurValueForFile($fileName, $contentType = null, $postName = null)
    {
        // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) {
            return curl_file_create($fileName, $contentType, $postName);
        }

        // Use the old style if using an older version of PHP
        $value = "@{filename};filename=" . $postName;
        if ($contentType) {
            $value .= ';type=' . $contentType;
        }

        return $value;
    }
}
