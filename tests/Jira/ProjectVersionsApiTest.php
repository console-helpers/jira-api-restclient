<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class ProjectVersionsApiTest extends AbstractApiTestCase
{

	public function testGetVersions()
	{
		$project_key = 'PROJ';
		$response = file_get_contents(__DIR__ . '/resources/api_get_versions.json');

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/' . $project_key . '/versions',
			array(),
			$response
		);

		$this->assertEquals(
			json_decode($response, true),
			$this->api->getVersions($project_key)
		);
	}

	public function testCreateVersionWithoutCustomParams()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_create_version.json');

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/version',
			array(
				'name' => '1.2.3',
				'project' => 'TST',
				'archived' => false,
			),
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->createVersion('TST', '1.2.3')
		);
	}

	public function testCreateVersionWithCustomParams()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_create_version.json');

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/version',
			array(
				'name' => '1.2.3',
				'project' => 'TST',
				'archived' => true,
				'description' => 'test',
			),
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->createVersion('TST', '1.2.3', array('archived' => true, 'description' => 'test'))
		);
	}

	public function testUpdateVersion()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_create_version.json');

		$params = array(
			'overdue' => true,
			'description' => 'new description',
		);

		$this->expectClientCall(
			Api::REQUEST_PUT,
			'/rest/api/2/version/111000',
			$params,
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->updateVersion(111000, $params)
		);
	}

	public function testReleaseVersionAutomaticReleaseDate()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_create_version.json');

		$params = array(
			'released' => true,
			'releaseDate' => date('Y-m-d'),
		);

		$this->expectClientCall(
			Api::REQUEST_PUT,
			'/rest/api/2/version/111000',
			$params,
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->releaseVersion(111000)
		);
	}

	public function testReleaseVersionParameterMerging()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_create_version.json');

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
			$response
		);

		$this->assertApiResponse(
			$response,
			$this->api->releaseVersion(111000, $release_date, array('test' => 'extra'))
		);
	}

	public function testFindVersionByName()
	{
		$versions = array(
			array('id' => '14205', 'name' => '3.62.0'),
			array('id' => '14206', 'name' => '3.36.0'),
			array('id' => '14207', 'name' => '3.66.0'),
		);

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/POR/versions',
			array(),
			json_encode($versions)
		);

		$this->assertEquals(
			array('id' => '14206', 'name' => '3.36.0'),
			$this->api->findVersionByName('POR', '3.36.0'),
			'Version found'
		);

		$this->assertNull(
			$this->api->findVersionByName('POR', 'i_do_not_exist'),
			'Version not found'
		);
	}

	public function testFindVersionByNameError()
	{
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/project/POR/versions',
			array(),
			'{"errorMessages":["No project could be found with key \'POR\'."],"errors":{}}'
		);

		$this->assertNull(
			$this->api->findVersionByName('POR', 'any-version'),
			'Project not found'
		);
	}

}
