<?php


namespace Tests\chobie\Jira;


use chobie\Jira\Api;
use chobie\Jira\Api\Exception;

final class IssueAttachmentsApiTest extends AbstractApiTestCase
{

	public function testCreateAttachmentWithAutomaticAttachmentName()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_create_attachment.json');

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/JRE-123/attachments',
			array(
				'file' => '@' . __DIR__ . '/resources/api_field.json',
				'name' => null,
			),
			$response,
			true
		);

		$this->assertApiResponse(
			$response,
			$this->api->createAttachment('JRE-123', __DIR__ . '/resources/api_field.json')
		);
	}

	public function testCreateAttachmentWithManualAttachmentName()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_create_attachment.json');

		$this->expectClientCall(
			Api::REQUEST_POST,
			'/rest/api/2/issue/JRE-123/attachments',
			array(
				'file' => '@' . __DIR__ . '/resources/api_field.json',
				'name' => 'manual.txt',
			),
			$response,
			true
		);

		$this->assertApiResponse(
			$response,
			$this->api->createAttachment('JRE-123', __DIR__ . '/resources/api_field.json', 'manual.txt')
		);
	}

	public function testDownloadAttachmentSuccessful()
	{
		$expected = 'file content';

		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/attachment/content/12345',
			array(),
			$expected,
			true
		);

		$actual = $this->api->downloadAttachment(self::ENDPOINT . '/rest/api/2/attachment/content/12345');

		if ( $actual !== null ) {
			$this->assertEquals($expected, $actual);
		}
	}

	public function testDownloadAttachmentWithException()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('The download url is coming from the different Jira instance.');

		$this->api->downloadAttachment('https://other.jira-instance.com/rest/api/2/attachment/content/12345');
	}

	public function testGetAttachment()
	{
		$response = file_get_contents(__DIR__ . '/resources/api_get_attachment.json');
		$this->expectClientCall(
			Api::REQUEST_GET,
			'/rest/api/2/attachment/18700',
			array(),
			$response
		);

		$this->assertEquals(
			json_decode($response, true),
			$this->api->getAttachment('18700')
		);
	}

}
