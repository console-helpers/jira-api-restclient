<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class IssuesApiTest extends AbstractApiTest
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

}
