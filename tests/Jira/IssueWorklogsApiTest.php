<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class IssueWorklogsApiTest extends AbstractApiTestCase
{

	public function testGetWorklogs()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_worklog_of_issue.json');
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issue/POR-1/worklog',
			array(),
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->getWorklogs('POR-1')
		);
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
