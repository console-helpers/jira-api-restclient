<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class ProjectsApiTest extends AbstractApiTest
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

}
