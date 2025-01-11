<?php

namespace Tests\chobie\Jira;


use chobie\Jira\Api;
use chobie\Jira\Api\Result;
use chobie\Jira\IssueType;

/**
 * Class ApiTest
 *
 * @package Tests\chobie\Jira
 */
class ApiTest extends AbstractApiTest
{

	/**
	 * @dataProvider setEndpointDataProvider
	 */
	public function testSetEndpoint($given_endpoint, $used_endpoint)
	{
		$api = new Api($given_endpoint, $this->credential, $this->client->reveal());
		$this->assertEquals($used_endpoint, $api->getEndpoint());
	}

	public static function setEndpointDataProvider()
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
				'startAt' => 5,
				'maxResults' => 2,
				'fields' => 'description',
			),
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->search('test', 5, 2, 'description')
		);
	}

	public function testSetWatchers()
	{
		$errored_response = '{"errorMessages":[],"errors":{}}';

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/JRE-123/watchers',
			'account-id-one',
			'' // For successful operation an empty string is returned.
		);
		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/JRE-123/watchers',
			'account-id-two',
			$errored_response // For a failed operation an error list is returned.
		);

		// Can't use "assertSame" due to objected, but "assertEquals" would consider "false" and "" the same.
		$this->assertEquals(
			array(
				false,
				new Result(json_decode($errored_response, true)),
			),
			$this->api->setWatchers('JRE-123', array('account-id-one', 'account-id-two'))
		);
	}

	/**
	 * @dataProvider createRemoteLinkDataProvider
	 */
	public function testCreateRemoteLink(array $method_params, array $expected_api_params)
	{
		$response = file_get_contents(__DIR__ . '/resources/api_create_remote_link.json');

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/JRE-123/remotelink',
			$expected_api_params,
			$response
		);

		$expected = json_decode($response, true);
		$actual = call_user_func_array(array($this->api, 'createRemoteLink'), $method_params);

		if ( $actual !== false ) {
			$this->assertEquals($expected, $actual);
		}
	}

	public static function createRemoteLinkDataProvider()
	{
		return array(
			'object only' => array(
				array(
					'JRE-123',
					array(
						'title' => 'TSTSUP-111',
						'url' => 'http://www.mycompany.com/support?id=1',
					),
				),
				array(
					'object' => array(
						'title' => 'TSTSUP-111',
						'url' => 'http://www.mycompany.com/support?id=1',
					),
				),
			),
			'object+relationship' => array(
				array(
					'JRE-123',
					array(
						'title' => 'TSTSUP-111',
						'url' => 'http://www.mycompany.com/support?id=1',
					),
					'blocked by',
				),
				array(
					'relationship' => 'blocked by',
					'object' => array(
						'title' => 'TSTSUP-111',
						'url' => 'http://www.mycompany.com/support?id=1',
					),
				),
			),
			'object+global_id' => array(
				array(
					'JRE-123',
					array(
						'title' => 'TSTSUP-111',
						'url' => 'http://www.mycompany.com/support?id=1',
					),
					null,
					'global-id',
				),
				array(
					'globalId' => 'global-id',
					'object' => array(
						'title' => 'TSTSUP-111',
						'url' => 'http://www.mycompany.com/support?id=1',
					),
				),
			),
			'object+application' => array(
				array(
					'JRE-123',
					array(
						'title' => 'TSTSUP-111',
						'url' => 'http://www.mycompany.com/support?id=1',
					),
					null,
					null,
					array(
						'name' => 'My Acme Tracker',
						'type' => 'com.acme.tracker',
					),
				),
				array(
					'object' => array(
						'title' => 'TSTSUP-111',
						'url' => 'http://www.mycompany.com/support?id=1',
					),
					'application' => array(
						'name' => 'My Acme Tracker',
						'type' => 'com.acme.tracker',
					),
				),
			),
		);
	}

	public function testFalseOnEmptyResponse()
	{
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/something',
			array(),
			''
		);

		$this->assertFalse($this->api->api(api::REQUEST_GET, '/rest/api/2/something'));
	}

	public function testResponseIsJsonDecodedIntoArray()
	{
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/something',
			array(),
			'{"key":"value"}'
		);

		$this->assertEquals(
			array('key' => 'value'),
			$this->api->api(api::REQUEST_GET, '/rest/api/2/something', array(), true)
		);
	}

	public function testResponseIsJsonDecodedIntoResultObject()
	{
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/something',
			array(),
			'{"key":"value"}'
		);

		$this->assertEquals(
			new Result(array('key' => 'value')),
			$this->api->api(api::REQUEST_GET, '/rest/api/2/something')
		);
	}

	/**
	 * @dataProvider responseAutomappingDataProvider
	 */
	public function testResponseAutomapping($options, $jira_response, array $expected_response)
	{
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/something',
			array(),
			$jira_response
		);

		// Field auto-expanding would trigger this call.
		if ( $options === Api::AUTOMAP_FIELDS ) {
			$decoded_field_response = array(
				array(
					'id' => 'title',
					'name' => 'Заголовок',
				),
				array(
					'id' => 'description',
					'name' => 'Описание',
				),
			);
			$this->expectClientCall(
				Api::REQUEST_GET,
				'/rest/api/2/field',
				array(),
				json_encode($decoded_field_response)
			);
		}

		$this->api->setOptions($options);

		$this->assertEquals(
			$expected_response,
			$this->api->api(api::REQUEST_GET, '/rest/api/2/something', array(), true)
		);
	}

	public static function responseAutomappingDataProvider()
	{
		$decoded_issues_response = array(
			'issues' => array(
				array(
					'fields' => array(
						'title' => 'sample title 1',
						'description' => 'sample description 1',
						'issuetype' => array(
							'self' => 'https://test.atlassian.net/rest/api/2/issuetype/10034',
						),
					),
				),
				array(
					'fields' => array(
						'title' => 'sample title 2',
						'description' => 'sample description 2',
						'issuetype' => array(
							'self' => 'https://test.atlassian.net/rest/api/2/issuetype/10035',
						),
					),
				),
			),
		);

		return array(
			'auto-map' => array(
				Api::AUTOMAP_FIELDS,
				json_encode($decoded_issues_response),
				array(
					'issues' => array(
						array(
							'fields' => array(
								'Заголовок' => 'sample title 1',
								'Описание' => 'sample description 1',
								'issuetype' => array(
									'self' => 'https://test.atlassian.net/rest/api/2/issuetype/10034',
								),
							),
						),
						array(
							'fields' => array(
								'Заголовок' => 'sample title 2',
								'Описание' => 'sample description 2',
								'issuetype' => array(
									'self' => 'https://test.atlassian.net/rest/api/2/issuetype/10035',
								),
							),
						),
					),
				),
			),
			'don\'t auto-map' => array(
				0,
				json_encode($decoded_issues_response),
				$decoded_issues_response,
			),
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

}
