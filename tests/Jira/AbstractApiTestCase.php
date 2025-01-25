<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\AuthenticationInterface;
use chobie\Jira\Api\Client\ClientInterface;
use chobie\Jira\Api\Result;
use Prophecy\Prophecy\MethodProphecy;
use Prophecy\Prophecy\ObjectProphecy;

abstract class AbstractApiTestCase extends AbstractTestCase
{

	const ENDPOINT = 'http://jira.company.com';

	/**
	 * Api.
	 *
	 * @var Api
	 */
	protected $api;

	/**
	 * Credential.
	 *
	 * @var AuthenticationInterface
	 */
	protected $credential;

	/**
	 * Client.
	 *
	 * @var ObjectProphecy
	 */
	protected $client;

	/**
	 * @before
	 */
	protected function setUpTest()
	{
		$this->credential = $this->prophesize(AuthenticationInterface::class)->reveal();
		$this->client = $this->prophesize(ClientInterface::class);

		$this->api = new Api(self::ENDPOINT, $this->credential, $this->client->reveal());
		$this->api->setOptions(0); // Disable automapping.
	}

	/**
	 * Checks, that response is correct.
	 *
	 * @param string       $expected_raw_response Expected raw response.
	 * @param Result|false $actual_response       Actual response.
	 *
	 * @return void
	 */
	protected function assertApiResponse($expected_raw_response, $actual_response)
	{
		$expected = new Result(json_decode($expected_raw_response, true));

		// You'll get "false", when unexpected API call was made.
		if ( $actual_response !== false ) {
			$this->assertEquals($expected, $actual_response);
		}
	}

	/**
	 * Expects a particular client call.
	 *
	 * @param string       $method       Request method.
	 * @param string       $url          URL.
	 * @param array|string $data         Request data.
	 * @param string       $return_value Return value.
	 * @param boolean      $is_file      This is a file upload request.
	 * @param boolean      $debug        Debug this request.
	 *
	 * @return MethodProphecy
	 */
	protected function expectClientCall(
		$method,
		$url,
		$data = array(),
		$return_value,
		$is_file = false,
		$debug = false
	) {
		return $this->client
			->sendRequest($method, $url, $data, self::ENDPOINT, $this->credential, $is_file, $debug)
			->willReturn($return_value)
			->shouldBeCalled();
	}

}
