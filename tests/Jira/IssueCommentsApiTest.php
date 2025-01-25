<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;

final class IssueCommentsApiTest extends AbstractApiTestCase
{

	/**
	 * @dataProvider addCommentDataProvider
	 */
	public function testAddComment($input_param, array $api_params)
	{
		$response = file_get_contents(__DIR__ . '/resources/api_add_comment.json');

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/POR-1/comment',
			$api_params,
			$response
		);

		$this->assertApiResponse($response, $this->api->addComment('POR-1', $input_param));
	}

	public static function addCommentDataProvider()
	{
		return array(
			'data-structure' => array(
				array(
					'body' => 'testdesc',
					'visibility' => array(
						'identifier' => 'Administrators',
						'type' => 'role',
						'value' => 'Administrators',
					),
				),
				array(
					'body' => 'testdesc',
					'visibility' => array(
						'identifier' => 'Administrators',
						'type' => 'role',
						'value' => 'Administrators',
					),
				),
			),
			'comment-text-only' => array(
				'comment text',
				array(
					'body' => 'comment text',
				),
			),
		);
	}

}
