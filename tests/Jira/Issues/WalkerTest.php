<?php

namespace Tests\chobie\Jira\Issues;


use chobie\Jira\Api\Result;
use chobie\Jira\Api\UnauthorizedException;
use chobie\Jira\Issue;
use chobie\Jira\Issues\Walker;
use Exception;
use Prophecy\Prophecy\ObjectProphecy;
use Tests\chobie\Jira\AbstractTestCase;
use Yoast\PHPUnitPolyfills\Polyfills\AssertStringContains;
use chobie\Jira\Api;

class WalkerTest extends AbstractTestCase
{

	use AssertStringContains;

	/**
	 * API.
	 *
	 * @var ObjectProphecy
	 */
	protected $api;

	/**
	 * Error log file.
	 *
	 * @var string
	 */
	protected $errorLogFile;

	/**
	 * @before
	 */
	protected function setUpTest()
	{
		$this->api = $this->prophesize(Api::class);

		if ( $this->captureErrorLog() ) {
			$this->errorLogFile = tempnam(sys_get_temp_dir(), 'error_log_');
			$this->assertEmpty(file_get_contents($this->errorLogFile));

			ini_set('error_log', $this->errorLogFile);
		}
	}

	/**
	 * @after
	 */
	protected function tearDownTest()
	{
		if ( $this->captureErrorLog() ) {
			ini_restore('error_log');
			unlink($this->errorLogFile);
		}
	}

	/**
	 * Determines if contents of error log needs to be captured.
	 *
	 * @return boolean
	 */
	protected function captureErrorLog()
	{
		return strpos($this->getTestName(), 'AnyException') !== false;
	}

	public function testErrorWithoutJQL()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('you have to call Jira_Walker::push($jql, $fields) at first');

		foreach ( $this->createWalker() as $issue ) {
			echo '';
		}
	}

	public function testFoundNoIssues()
	{
		$search_response = $this->generateSearchResponse('PRJ', 0);
		$this->api->search('test jql', 0, 5, 'description')->willReturn($search_response);

		$walker = $this->createWalker(5);
		$walker->push('test jql', 'description');

		$found_issues = array();

		foreach ( $walker as $issue ) {
			$found_issues[] = $issue;
		}

		$this->assertCount(0, $found_issues);
	}

	public function testDefaultPerPageUsed()
	{
		$search_response = $this->generateSearchResponse('PRJ', 50);
		$this->api->search('test jql', 0, 50, 'description')->willReturn($search_response);

		$walker = $this->createWalker();
		$walker->push('test jql', 'description');

		$found_issues = array();

		foreach ( $walker as $issue ) {
			$found_issues[] = $issue;
		}

		$this->assertEquals(
			$search_response->getIssues(),
			$found_issues
		);
	}

	public function testFoundTwoPagesOfIssues()
	{
		// Full 1st page.
		$search_response1 = $this->generateSearchResponse('PRJ1', 5, 7);
		$this->api->search('test jql', 0, 5, 'description')->willReturn($search_response1);

		// Incomplete 2nd page.
		$search_response2 = $this->generateSearchResponse('PRJ2', 2, 7);
		$this->api->search('test jql', 5, 5, 'description')->willReturn($search_response2);

		$walker = $this->createWalker(5);
		$walker->push('test jql', 'description');

		$found_issues = array();

		foreach ( $walker as $issue ) {
			$found_issues[] = $issue;
		}

		$this->assertEquals(
			array_merge($search_response1->getIssues(), $search_response2->getIssues()),
			$found_issues
		);
	}

	public function testUnauthorizedExceptionOnFirstPage()
	{
		$this->expectException(UnauthorizedException::class);
		$this->expectExceptionMessage('Unauthorized');

		$this->api->search('test jql', 0, 5, 'description')->willThrow(new UnauthorizedException('Unauthorized'));

		$walker = $this->createWalker(5);
		$walker->push('test jql', 'description');

		foreach ( $walker as $issue ) {
			echo '';
		}
	}

	public function testAnyExceptionOnFirstPage()
	{
		$this->api->search('test jql', 0, 5, 'description')->willThrow(new Exception('Anything'));

		$walker = $this->createWalker(5);
		$walker->push('test jql', 'description');

		foreach ( $walker as $issue ) {
			echo '';
		}

		$this->assertStringContainsString('Anything', file_get_contents($this->errorLogFile));
	}

	public function testUnauthorizedExceptionOnSecondPage()
	{
		$this->expectException(UnauthorizedException::class);
		$this->expectExceptionMessage('Unauthorized');

		// Full 1st page.
		$search_response1 = $this->generateSearchResponse('PRJ1', 5, 7);
		$this->api->search('test jql', 0, 5, 'description')->willReturn($search_response1);

		// Incomplete 2nd page.
		$this->api->search('test jql', 5, 5, 'description')->willThrow(new UnauthorizedException('Unauthorized'));

		$walker = $this->createWalker(5);
		$walker->push('test jql', 'description');

		foreach ( $walker as $issue ) {
			echo '';
		}
	}

	public function testAnyExceptionOnSecondPage()
	{
		// Full 1st page.
		$search_response1 = $this->generateSearchResponse('PRJ1', 5, 7);
		$this->api->search('test jql', 0, 5, 'description')->willReturn($search_response1);

		// Incomplete 2nd page.
		$this->api->search('test jql', 5, 5, 'description')->willThrow(new Exception('Anything'));

		$walker = $this->createWalker(5);
		$walker->push('test jql', 'description');

		foreach ( $walker as $issue ) {
			echo '';
		}

		$this->assertStringContainsString('Anything', file_get_contents($this->errorLogFile));
	}

	public function testSetDelegateError()
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('passed argument is not callable');

		$walker = $this->createWalker();
		$walker->setDelegate('not a callable');
	}

	public function testIssuesPassedThroughDelegate()
	{
		$search_response = $this->generateSearchResponse('PRJ', 2);
		$this->api->search('test jql', 0, 2, 'description')->willReturn($search_response);

		$walker = $this->createWalker(2);
		$walker->push('test jql', 'description');
		$walker->setDelegate(function (Issue $issue) {
			return $issue->get('description');
		});

		$found_issues = array();

		foreach ( $walker as $issue ) {
			$found_issues[] = $issue;
		}

		$this->assertEquals(
			array('description 2', 'description 1'),
			$found_issues
		);
	}

	public function testCounting()
	{
		// Full 1st page.
		$search_response1 = $this->generateSearchResponse('PRJ1', 5, 7);
		$this->api->search('test jql', 0, 5, 'description')->willReturn($search_response1);

		// Incomplete 2nd page.
		$search_response2 = $this->generateSearchResponse('PRJ2', 2, 7);
		$this->api->search('test jql', 5, 5, 'description')->willReturn($search_response2);

		$walker = $this->createWalker(5);
		$walker->push('test jql', 'description');

		$this->assertEquals(7, count($walker));

		$found_issues = array();

		foreach ( $walker as $issue ) {
			$found_issues[] = $issue;
		}

		$this->assertEquals(
			array_merge($search_response1->getIssues(), $search_response2->getIssues()),
			$found_issues
		);
	}

	/**
	 * Generate search response.
	 *
	 * @param string       $project_key Project key.
	 * @param integer      $issue_count Issue count.
	 * @param integer|null $total       Total issues.
	 *
	 * @return Result
	 */
	protected function generateSearchResponse($project_key, $issue_count, $total = null)
	{
		$issues = array();

		if ( !is_numeric($total) ) {
			$total = $issue_count;
		}

		while ( $issue_count > 0 ) {
			$issue_id = $issue_count + 1000;
			$issues[] = array(
				'expand' => 'operations,versionedRepresentations,editmeta,changelog,transitions,renderedFields',
				'id' => $issue_id,
				'self' => 'http://jira.company.com/rest/api/2/issue/' . $issue_id,
				'key' => $project_key . '-' . $issue_id,
				'fields' => array(
					'description' => 'description ' . $issue_count,
				),
			);
			$issue_count--;
		}

		return new Result(array(
			'expand' => 'schema,names',
			'startAt' => 0,
			'maxResults' => count($issues),
			'total' => $total,
			'issues' => $issues,
		));
	}

	/**
	 * Creates walker instance.
	 *
	 * @param integer|null $per_page Per page.
	 *
	 * @return Walker
	 */
	protected function createWalker($per_page = null)
	{
		return new Walker($this->api->reveal(), $per_page);
	}

}

