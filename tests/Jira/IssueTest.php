<?php

namespace Tests\chobie\Jira;


use chobie\Jira\Issue;

class IssueTest extends AbstractTestCase
{

	public function testGetters()
	{
		$issue = new Issue(array(
			'fields' => array(
				'summary' => 'Test summary',
				'issuetype' => array('name' => 'Bug'),
				'reporter' => array('name' => 'reporter'),
				'created' => '2026-05-15T10:00:00.000+0000',
				'assignee' => array('name' => 'assignee'),
				'updated' => '2026-05-15T11:00:00.000+0000',
				'priority' => array('name' => 'High'),
				'description' => 'Test description',
				'status' => array('name' => 'Open'),
				'labels' => array('bug'),
				'project' => array('key' => 'TEST'),
				'fixVersions' => array(array('name' => '1.0.0')),
				'resolution' => array('name' => 'Fixed'),
				'resolutiondate' => '2026-05-16T12:00:00.000+0000',
				'watches' => array('watchCount' => 1),
				'duedate' => '2026-05-20',
			),
		));

		$this->assertSame('Test summary', $issue->getSummary());
		$this->assertSame(array('name' => 'Bug'), $issue->getIssueType());
		$this->assertSame(array('name' => 'reporter'), $issue->getReporter());
		$this->assertSame('2026-05-15T10:00:00.000+0000', $issue->getCreated());
		$this->assertSame(array('name' => 'assignee'), $issue->getAssignee());
		$this->assertSame('2026-05-15T11:00:00.000+0000', $issue->getUpdated());
		$this->assertSame(array('name' => 'High'), $issue->getPriority());
		$this->assertSame('Test description', $issue->getDescription());
		$this->assertSame(array('name' => 'Open'), $issue->getStatus());
		$this->assertSame(array('bug'), $issue->getLabels());
		$this->assertSame(array('key' => 'TEST'), $issue->getProject());
		$this->assertSame(array(array('name' => '1.0.0')), $issue->getFixVersions());
		$this->assertSame(array('name' => 'Fixed'), $issue->getResolution());
		$this->assertSame('2026-05-16T12:00:00.000+0000', $issue->getResolutionDate());
		$this->assertSame(array('watchCount' => 1), $issue->getWatchers());
		$this->assertSame('2026-05-20', $issue->getDueDate());
	}

	public function testGet()
	{
		$issue = new Issue(array(
			'fields' => array(
				'Summary' => 'Mapped summary',
				'summary' => 'Raw summary',
			),
		));

		$this->assertSame('Mapped summary', $issue->get('Summary'));
		$this->assertSame('Raw summary', $issue->get('summary'));
		$this->assertNull($issue->get('missing'));
	}

}
