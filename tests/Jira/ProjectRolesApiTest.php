<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class ProjectRolesApiTest extends AbstractApiTest
{

	public function testGetProjectRoles()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_project_roles.json');
		$project_id = '10500';

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/' . $project_id . '/role',
			array(),
			$response
		);

		$actual = $this->api->getRoles($project_id);

		$expected = json_decode($response, true);
		$this->assertEquals($expected, $actual);
	}

}
