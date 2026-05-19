<?php
/*
 * The MIT License
 *
 * Copyright (c) 2014 Shuhei Tanuma
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace chobie\Jira;


class Issue
{

	/**
	 * Expand.
	 *
	 * @var array
	 */
	protected $expand;

	/**
	 * ID.
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Self.
	 *
	 * @var string
	 */
	protected $self;

	/**
	 * Key.
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Fields.
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * Expand information.
	 *
	 * @var array
	 */
	protected $expandedInformation;

	/**
	 * Creates issue instance.
	 *
	 * @param array $issue Issue.
	 */
	public function __construct(array $issue = array())
	{
		if ( isset($issue['expand']) ) {
			$this->expand = explode(',', $issue['expand']);
			unset($issue['expand']);
		}

		if ( isset($issue['id']) ) {
			$this->id = $issue['id'];
			unset($issue['id']);
		}

		if ( isset($issue['self']) ) {
			$this->self = $issue['self'];
			unset($issue['self']);
		}

		if ( isset($issue['key']) ) {
			$this->key = $issue['key'];
			unset($issue['key']);
		}

		if ( isset($issue['fields']) ) {
			$this->fields = $issue['fields'];
			unset($issue['fields']);
		}

		$this->expandedInformation = $issue;
	}

	/**
	 * Gets issue key (YOURPROJ-123).
	 *
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * Gets jira's internal issue id.
	 *
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Get this issue api url.
	 *
	 * @return string
	 */
	public function getSelf()
	{
		return $this->self;
	}

	/**
	 * Get current fields.
	 *
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Get issue summary.
	 *
	 * @return mixed
	 */
	public function getSummary()
	{
		return $this->get('summary');
	}

	/**
	 * Get issue type.
	 *
	 * @return mixed
	 */
	public function getIssueType()
	{
		return $this->get('issuetype');
	}

	/**
	 * Get issue reporter.
	 *
	 * @return mixed
	 */
	public function getReporter()
	{
		return $this->get('reporter');
	}

	/**
	 * Get issue created time.
	 *
	 * @return mixed
	 */
	public function getCreated()
	{
		return $this->get('created');
	}

	/**
	 * Get the current assignee.
	 *
	 * @return mixed
	 */
	public function getAssignee()
	{
		return $this->get('assignee');
	}

	/**
	 * Get issue updated time.
	 *
	 * @return mixed
	 */
	public function getUpdated()
	{
		return $this->get('updated');
	}

	/**
	 * Get priority.
	 *
	 * @return mixed
	 */
	public function getPriority()
	{
		return $this->get('priority');
	}

	/**
	 * Get description.
	 *
	 * @return mixed
	 */
	public function getDescription()
	{
		return $this->get('description');
	}

	/**
	 * Get issue status.
	 *
	 * @return mixed
	 */
	public function getStatus()
	{
		return $this->get('status');
	}

	/**
	 * Get labels.
	 *
	 * @return mixed
	 */
	public function getLabels()
	{
		return $this->get('labels');
	}

	/**
	 * Get project info.
	 *
	 * @return mixed
	 */
	public function getProject()
	{
		return $this->get('project');
	}

	/**
	 * Get fix versions.
	 *
	 * @return mixed
	 */
	public function getFixVersions()
	{
		return $this->get('fixVersions');
	}

	/**
	 * Get resolutions.
	 *
	 * @return mixed
	 */
	public function getResolution()
	{
		return $this->get('resolution');
	}

	/**
	 * Get resolution date.
	 *
	 * @return mixed
	 * @todo   Is the field exists? Maybe there should be 'Planned End'?
	 */
	public function getResolutionDate()
	{
		return $this->get('resolutiondate');
	}

	/**
	 * Get watches.
	 *
	 * @return mixed
	 */
	public function getWatchers()
	{
		return $this->get('watches');
	}

	/**
	 * Get due date.
	 *
	 * @return mixed
	 */
	public function getDueDate()
	{
		return $this->get('duedate');
	}

	/**
	 * Get information represented in call output due to expand=... suffix.
	 *
	 * @return array
	 * @see    https://docs.atlassian.com/jira/REST/latest/
	 */
	public function getExpandedInformation()
	{
		return $this->expandedInformation;
	}

	/**
	 * Gets field by name.
	 *
	 * @param string $field_key Field key.
	 *
	 * @return array
	 */
    public function get($field_key)
	{
		if ( isset($this->fields[$field_key]) ) {
			return $this->fields[$field_key];
		}

		return null;
	}

}
