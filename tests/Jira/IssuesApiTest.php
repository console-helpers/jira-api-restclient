<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class IssuesApiTest extends AbstractApiTestCase
{

	public function testGetIssueWithoutExpand()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_issue.json');

		$issue_key = 'POR-1';

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issue/' . $issue_key,
			array(),
			$response
		);

		$this->assertApiResponse($response, $this->api->getIssue($issue_key));
	}

	public function testGetIssueWithExpand()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_issue.json');

		$issue_key = 'POR-1';

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issue/' . $issue_key,
			array('expand' => 'changelog'),
			$response
		);

		$this->assertApiResponse($response, $this->api->getIssue($issue_key, 'changelog'));
	}

	public function testEditIssue()
	{
		$issue_key = 'POR-1';
		$params = array(
			'update' => array(
				'summary' => array(
					array('set' => 'Bug in business logic'),
				),
			),
		);
		$this->expectClientCall(
			Api::REQUEST_PUT,
			'/rest/api/2/issue/' . $issue_key,
			$params,
			false // False is returned because there is no content (204).
		);

		$this->assertFalse($this->api->editIssue($issue_key, $params));
	}

	public function testCreateIssueWithoutOtherFields()
	{
		$params = array(
			'fields' => array(
				'project' => array(
					'key' => 'POR-1',
				),
				'summary' => 'New issue summary',
				'issuetype' => array(
					'id' => '10034',
				),
			),
		);

		$response = file_get_contents(__DIR__ . '/resources/api_create_issue.json');

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue',
			$params,
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->createIssue('POR-1', 'New issue summary', '10034')
		);
	}

	public function testCreateIssueWithOtherFields()
	{
		$params = array(
			'fields' => array(
				'project' => array(
					'key' => 'POR-1',
				),
				'summary' => 'New issue summary',
				'issuetype' => array(
					'name' => 'Bug', // Replaced.
				),
				'description' => 'New issue description', // Added.
			),
		);

		$response = file_get_contents(__DIR__ . '/resources/api_create_issue.json');

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue',
			$params,
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->createIssue(
				'POR-1',
				'New issue summary',
				'10034',
				array('description' => 'New issue description', 'issuetype' => array('name' => 'Bug'))
			)
		);
	}

	public function testGetTransitionsWithoutExtraParams()
	{
		$issue_key = 'POR-1';

		$response = file_get_contents(__DIR__ . '/resources/api_get_transitions.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issue/' . $issue_key . '/transitions',
			array(),
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->getTransitions('POR-1')
		);
	}

	public function testGetTransitionsWithExtraParams()
	{
		$issue_key = 'POR-1';

		$response = file_get_contents(__DIR__ . '/resources/api_get_transitions.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issue/' . $issue_key . '/transitions',
			array('sortByOpsBarAndStatus' => true),
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->getTransitions('POR-1', array('sortByOpsBarAndStatus' => true))
		);
	}

	public function testTransition()
	{
		$params = array(
			'transition' => array(
				'id' => 123,
			),
		);

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/POR-1/transitions',
			$params,
			false // False is returned because there is no content (204).
		);

		$this->assertFalse(
			$this->api->transition('POR-1', $params)
		);
	}

	/**
	 * @depends testGetTransitionsWithoutExtraParams
	 * @depends testTransition
	 */
	public function testCloseIssue()
	{
		$issue_key = 'POR-1';

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issue/' . $issue_key . '/transitions',
			array(),
			file_get_contents(__DIR__ . '/resources/api_get_transitions.json')
		);

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/POR-1/transitions',
			array(
				'transition' => array('id' => 171), // The "171" is transition ID for "Close Issue" transition.
			),
			false // False is returned because there is no content (204).
		);

		$this->api->closeIssue($issue_key);
	}

	/**
	 * @depends testGetTransitionsWithoutExtraParams
	 * @depends testTransition
	 */
	public function testCloseIssueFailure()
	{
		$issue_key = 'POR-1';

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/issue/' . $issue_key . '/transitions',
			array(),
			json_encode(array(
				'transitions' => array(
					array('name' => 'Schedule Issue'),
				),
			))
		);

		$this->assertSame(array(), $this->api->closeIssue($issue_key));
	}

}
