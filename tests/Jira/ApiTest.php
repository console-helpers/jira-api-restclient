<?php

namespace Tests\chobie\Jira;


use chobie\Jira\Api;
use chobie\Jira\Api\Exception;
use chobie\Jira\Api\Result;
use chobie\Jira\IssueType;

/**
 * Class ApiTest
 *
 * @package Tests\chobie\Jira
 */
class ApiTest extends AbstractApiTestCase
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

	/**
	 * @dataProvider searchFirstPageDataProvider
	 */
	public function testSearchFirstPage($next_page_token)
	{
		$response = file_get_contents(__DIR__ . '/resources/api_search.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/search/jql',
			array(
				'jql' => 'test',
				'maxResults' => 2,
				'fields' => 'description',
			),
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->search('test', $next_page_token, 2, 'description')
		);
	}

	public static function searchFirstPageDataProvider()
	{
		return array(
			'new way' => array(null),
			'old way' => array(0),
		);
	}

	public function testSearchNextPage()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_search.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/search/jql',
			array(
				'jql' => 'test',
				'nextPageToken' => 'EgQIlMIC',
				'maxResults' => 2,
				'fields' => 'description',
			),
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->search('test', 'EgQIlMIC', 2, 'description'),
			true
		);
	}

	public function testSearchError()
	{
		$this->expectException(Exception::class);

		$error_msg = 'The "$next_page_token" value must be either null (for the first page) ';
		$error_msg .= 'or come from the "nextPageToken" key of the previous search response.';
		$this->expectExceptionMessage($error_msg);

		$this->api->search('test', 5, 2, 'description');
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
		)->shouldBeCalledOnce();

		// Perform the 1st call (uncached).
		$response_decoded = json_decode($response, true);
		$expected = array(
			'1' => $response_decoded[0],
			'10000' => $response_decoded[1],
		);
		$this->assertEquals($expected, $this->api->getResolutions());

		// Perform the 2nd call (cached).
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
		)->shouldBeCalledOnce();

		// Perform the 1st call (uncached).
		$response_decoded = json_decode($response, true);
		$expected = array(
			'issuetype' => $response_decoded[0],
			'timespent' => $response_decoded[1],
		);
		$this->assertEquals($expected, $this->api->getFields());

		// Perform the 2nd call (cached).
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
		)->shouldBeCalledOnce();

		// Perform the 1st call (uncached).
		$response_decoded = json_decode($response, true);
		$expected = array(
			'1' => $response_decoded[0],
			'3' => $response_decoded[1],
		);
		$this->assertEquals($expected, $this->api->getStatuses());

		// Perform the 2nd call (cached).
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
		)->shouldBeCalledOnce();

		// Perform the 1st call (uncached).
		$response_decoded = json_decode($response, true);
		$expected = array(
			'1' => $response_decoded[0],
			'5' => $response_decoded[1],
		);
		$this->assertEquals($expected, $this->api->getPriorities());

		// Perform the 2nd call (cached).
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

	public function testGetAttachmentsMetaInformation()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_attachments_meta.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/attachment/meta',
			array(),
			$response
		);

		$this->assertApiResponse($response, $this->api->getAttachmentsMetaInformation());
	}

	public function testQueryParametersHandlingForGetRequestMethod()
	{
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/something',
			array(
				'q_p1' => 'q_p1_v',
				'q_p2' => 'q_p2_v',
				'rb_p1' => 'rb_p1_v',
				'rb_p2' => 'rb_p2_v',
				'rb_p3' => 'rb_p3_v',
			),
			'{}'
		);

		$this->api->api(
			Api::REQUEST_GET,
			'/rest/api/2/something',
			array(
				'_query' => array('q_p1' => 'q_p1_v', 'q_p2' => 'q_p2_v'),
				'rb_p1' => 'rb_p1_v',
				'rb_p2' => 'rb_p2_v',
				'rb_p3' => 'rb_p3_v',
			),
			true
		);
	}

	/**
	 * @dataProvider queryParametersHandlingForOtherRequestMethodsDataProvider
	 *
	 * @param string $request_method Request method.
	 */
	public function testQueryParametersHandlingForOtherRequestMethods($request_method)
	{
		$this->expectClientCall(
			$request_method,
			'/rest/api/2/something?q_p1=q_p1_v&q_p2=q_p2_v',
			array(
				'rb_p1' => 'rb_p1_v',
				'rb_p2' => 'rb_p2_v',
				'rb_p3' => 'rb_p3_v',
			),
			'{}'
		);

		$this->api->api(
			$request_method,
			'/rest/api/2/something',
			array(
				'_query' => array('q_p1' => 'q_p1_v', 'q_p2' => 'q_p2_v'),
				'rb_p1' => 'rb_p1_v',
				'rb_p2' => 'rb_p2_v',
				'rb_p3' => 'rb_p3_v',
			),
			true
		);
	}

	public static function queryParametersHandlingForOtherRequestMethodsDataProvider()
	{
		return array(
			'delete' => array(Api::REQUEST_DELETE),
			'post' => array(Api::REQUEST_POST),
			'put' => array(Api::REQUEST_PUT),
		);
	}

	/**
	 * @dataProvider getCreateMetaDataProvider
	 */
	public function testGetCreateMeta(
		array $project_ids = null,
		array $project_keys = null,
		array $issue_type_ids = null,
		array $issue_type_names = null,
		array $expand = null,
		array $params = array()
	) {
		$response = file_get_contents(__DIR__ . '/resources/api_get_create_meta.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issue/createmeta',
			$params,
			$response
		);

		// Perform the API call.
		$actual = $this->api->getCreateMeta($project_ids, $project_keys, $issue_type_ids, $issue_type_names, $expand);

		$this->assertApiResponse($response, $actual, false);
	}

	public static function getCreateMetaDataProvider()
	{
		return array(
			'project_ids' => array(
				array(123, 456),
				null,
				null,
				null,
				null,
				array('projectIds' => '123,456'),
			),
			'project_names' => array(
				null,
				array('abc', 'def'),
				null,
				null,
				null,
				array('projectKeys' => 'abc,def'),
			),
			'project_ids+project_names' => array(
				array(123, 456),
				array('abc', 'def'),
				null,
				null,
				null,
				array('projectIds' => '123,456', 'projectKeys' => 'abc,def'),
			),

			'issue_type_ids' => array(
				null,
				null,
				array(123, 456),
				null,
				null,
				array('issuetypeIds' => '123,456'),
			),
			'issue_type_names' => array(
				null,
				null,
				null,
				array('abc', 'def'),
				null,
				array('issuetypeNames' => 'abc,def'),
			),
			'issue_type_ids+issue_type_names' => array(
				null,
				null,
				array(123, 456),
				array('abc', 'def'),
				null,
				array('issuetypeIds' => '123,456', 'issuetypeNames' => 'abc,def'),
			),
			'expand' => array(
				null,
				null,
				null,
				null,
				array('aa', 'bb'),
				array('expand' => 'aa,bb'),
			),
		);
	}

}
