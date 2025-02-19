<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class ProjectsApiTest extends AbstractApiTestCase
{

	public function testGetProjects()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_projects.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project',
			array(),
			$response
		);

		$this->assertApiResponse($response, $this->api->getProjects());
	}

	public function testGetProject()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_project.json');
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/TST',
			array(),
			$response
		);

		$this->assertApiResponse($response, $this->api->getProject('TST'), false);
	}

	public function testGetProjectComponents()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_project_components.json');
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/TST/components',
			array(),
			$response
		);

		$this->assertApiResponse($response, $this->api->getProjectComponents('TST'), false);
	}

	public function testGetProjectIssueTypes()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_project_issue_types.json');
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/TST/statuses',
			array(),
			$response
		);

		$this->assertApiResponse($response, $this->api->getProjectIssueTypes('TST'), false);
	}

}
