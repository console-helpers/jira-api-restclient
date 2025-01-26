<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class ProjectRolesApiTest extends AbstractApiTestCase
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

		$this->assertApiResponse($response, $this->api->getRoles($project_id));
	}

	public function testGetProjectRoleDetails()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_project_role.json');
		$project_id = '10500';
		$role_id = '10200';

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/' . $project_id . '/role/' . $role_id,
			array(),
			$response
		);

		$this->assertApiResponse($response, $this->api->getRoleDetails($project_id, $role_id));
	}

}
