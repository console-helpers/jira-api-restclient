<?php

namespace Tests\chobie\Jira;


use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\AuthenticationInterface;
use chobie\Jira\Api\Result;
use chobie\Jira\IssueType;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * Class ApiTest
 *
 * @package Tests\chobie\Jira
 */
class ApiTest extends TestCase
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
		$this->credential = $this->prophesize('chobie\Jira\Api\Authentication\AuthenticationInterface')->reveal();
		$this->client = $this->prophesize('chobie\Jira\Api\Client\ClientInterface');

		$this->api = new Api(self::ENDPOINT, $this->credential, $this->client->reveal());
	}

	/**
	 * @dataProvider setEndpointDataProvider
	 */
	public function testSetEndpoint($given_endpoint, $used_endpoint)
	{
		$api = new Api($given_endpoint, $this->credential, $this->client->reveal());
		$this->assertEquals($used_endpoint, $api->getEndpoint());
	}

	public function setEndpointDataProvider()
	{
		return array(
			'trailing slash removed' => array('https://test.test/', 'https://test.test'),
			'nothing removed' => array('https://test.test', 'https://test.test'),
		);
	}

	public function testSearch()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_search.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/search',
			array(
				'jql' => 'test',
				'startAt' => 0,
				'maxResults' => 2,
				'fields' => 'description',
			),
			$response
		);

		$response_decoded = json_decode($response, true);

		// Field auto-expanding would trigger this call.
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/field',
			array(),
			file_get_contents(__DIR__ . '/resources/api_field.json')
		);

		$this->assertEquals(new Result($response_decoded), $this->api->search('test', 0, 2, 'description'));
	}

	public function testUpdateVersion()
	{
		$params = array(
			'overdue' => true,
			'description' => 'new description',
		);

		$this->expectClientCall(
			Api::REQUEST_PUT,
			'/rest/api/2/version/111000',
			$params,
			''
		);

		$this->assertFalse($this->api->updateVersion(111000, $params));
	}

	public function testReleaseVersionAutomaticReleaseDate()
	{
		$params = array(
			'released' => true,
			'releaseDate' => date('Y-m-d'),
		);

		$this->expectClientCall(
			Api::REQUEST_PUT,
			'/rest/api/2/version/111000',
			$params,
			''
		);

		$this->assertFalse($this->api->releaseVersion(111000));
	}

	public function testReleaseVersionParameterMerging()
	{
		$release_date = '2010-07-06';

		$expected_params = array(
			'released' => true,
			'releaseDate' => $release_date,
			'test' => 'extra',
		);

		$this->expectClientCall(
			Api::REQUEST_PUT,
			'/rest/api/2/version/111000',
			$expected_params,
			''
		);

		$this->assertFalse($this->api->releaseVersion(111000, $release_date, array('test' => 'extra')));
	}

	public function testFindVersionByName()
	{
		$project_key = 'POR';
		$version_id = '14206';
		$version_name = '3.36.0';

		$versions = array(
			array('id' => '14205', 'name' => '3.62.0'),
			array('id' => $version_id, 'name' => $version_name),
			array('id' => '14207', 'name' => '3.66.0'),
		);

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/' . $project_key . '/versions',
			array(),
			json_encode($versions)
		);

		$this->assertEquals(
			array('id' => $version_id, 'name' => $version_name),
			$this->api->findVersionByName($project_key, $version_name),
			'Version found'
		);

		$this->assertNull(
			$this->api->findVersionByName($project_key, 'i_do_not_exist')
		);
	}

	public function testGetResolutions()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_resolution.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/resolution',
			array(),
			$response
		);

		$actual = $this->api->getResolutions();

		$response_decoded = json_decode($response, true);

		$expected = array(
			'1' => $response_decoded[0],
			'10000' => $response_decoded[1],
		);
		$this->assertEquals($expected, $actual);

		// Second time we call the method the results should be cached and not trigger an API Request.
		$this->client->sendRequest(Api::REQUEST_GET, '/rest/api/2/resolution', array(), self::ENDPOINT, $this->credential)
			->shouldNotBeCalled();
		$this->assertEquals($expected, $this->api->getResolutions(), 'Calling twice did not yield the same results');
	}

	public function testGetFields()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_field.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/field',
			array(),
			$response
		);

		$actual = $this->api->getFields();

		$response_decoded = json_decode($response, true);

		$expected = array(
			'issuetype' => $response_decoded[0],
			'timespent' => $response_decoded[1],
		);
		$this->assertEquals($expected, $actual);

		// Second time we call the method the results should be cached and not trigger an API Request.
		$this->client->sendRequest(Api::REQUEST_GET, '/rest/api/2/field', array(), self::ENDPOINT, $this->credential)
			->shouldNotBeCalled();
		$this->assertEquals($expected, $this->api->getFields(), 'Calling twice did not yield the same results');
	}

	public function testGetStatuses()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_status.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/status',
			array(),
			$response
		);

		$actual = $this->api->getStatuses();

		$response_decoded = json_decode($response, true);

		$expected = array(
			'1' => $response_decoded[0],
			'3' => $response_decoded[1],
		);
		$this->assertEquals($expected, $actual);

		// Second time we call the method the results should be cached and not trigger an API Request.
		$this->client->sendRequest(Api::REQUEST_GET, '/rest/api/2/status', array(), self::ENDPOINT, $this->credential)
			->shouldNotBeCalled();
		$this->assertEquals($expected, $this->api->getStatuses(), 'Calling twice did not yield the same results');
	}

	public function testGetPriorities()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_priority.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/priority',
			array(),
			$response
		);

		$actual = $this->api->getPriorities();

		$response_decoded = json_decode($response, true);

		$expected = array(
			'1' => $response_decoded[0],
			'5' => $response_decoded[1],
		);
		$this->assertEquals($expected, $actual);

		// Second time we call the method the results should be cached and not trigger an API Request.
		$this->client->sendRequest(Api::REQUEST_GET, '/rest/api/2/priority', array(), self::ENDPOINT, $this->credential)
			->shouldNotBeCalled();
		$this->assertEquals($expected, $this->api->getPriorities(), 'Calling twice did not yield the same results');
	}

	public function testGetIssueTypes()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_issue_types.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issuetype',
			array(),
			$response
		);

		$actual = $this->api->getIssueTypes();

		$response_decoded = json_decode($response, true);

		$expected = array(
			new IssueType($response_decoded[0]),
			new IssueType($response_decoded[1]),
		);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @param string|integer $time_spent           Time spent.
	 * @param array          $expected_rest_params Expected rest params.
	 *
	 * @return void
	 * @dataProvider addWorkLogWithoutCustomParamsDataProvider
	 */
	public function testAddWorkLogWithoutCustomParams($time_spent, array $expected_rest_params)
	{
		$response = '{}';

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/JRA-15/worklog',
			$expected_rest_params,
			$response
		);

		$actual = $this->api->addWorklog('JRA-15', $time_spent);

		$this->assertEquals(json_decode($response, true), $actual, 'The response is json-decoded.');
	}

	public static function addWorkLogWithoutCustomParamsDataProvider()
	{
		return array(
			'integer time spent' => array(12, array('timeSpentSeconds' => 12)),
			'string time spent' => array('12m', array('timeSpent' => '12m')),
		);
	}

	public function testAddWorklogWithCustomParams()
	{
		$response = '{}';

		$started = date(Api::DATE_TIME_FORMAT, 1621026000);
		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/JRA-15/worklog',
			array('timeSpent' => '12m', 'started' => $started),
			$response
		);

		$actual = $this->api->addWorklog('JRA-15', '12m', array('started' => $started));

		$this->assertEquals(json_decode($response, true), $actual, 'The response is json-decoded.');
	}

	public function testDeleteWorkLogWithoutCustomParams()
	{
		$response = '{}';

		$this->expectClientCall(
			Api::REQUEST_DELETE,
			'/rest/api/2/issue/JRA-15/worklog/11256',
			array(),
			$response
		);

		$actual = $this->api->deleteWorklog('JRA-15', 11256);

		$this->assertEquals(json_decode($response, true), $actual, 'The response is json-decoded.');
	}

	public function testDeleteWorkLogWithCustomParams()
	{
		$response = '{}';

		$this->expectClientCall(
			Api::REQUEST_DELETE,
			'/rest/api/2/issue/JRA-15/worklog/11256',
			array('custom' => 'param'),
			$response
		);

		$actual = $this->api->deleteWorklog('JRA-15', 11256, array('custom' => 'param'));

		$this->assertEquals(json_decode($response, true), $actual, 'The response is json-decoded.');
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
	 * @return void
	 */
	protected function expectClientCall(
		$method,
		$url,
		$data = array(),
		$return_value,
		$is_file = false,
		$debug = false
	) {
		$this->client
			->sendRequest($method, $url, $data, self::ENDPOINT, $this->credential, $is_file, $debug)
			->willReturn($return_value)
			->shouldBeCalled();
	}

}
