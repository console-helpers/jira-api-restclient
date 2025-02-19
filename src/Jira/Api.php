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


use chobie\Jira\Api\Authentication\AuthenticationInterface;
use chobie\Jira\Api\Client\ClientInterface;
use chobie\Jira\Api\Client\CurlClient;
use chobie\Jira\Api\Exception;
use chobie\Jira\Api\Result;

class Api
{

	const REQUEST_GET = 'GET';
	const REQUEST_POST = 'POST';
	const REQUEST_PUT = 'PUT';
	const REQUEST_DELETE = 'DELETE';

	const AUTOMAP_FIELDS = 0x01;
	const DATE_TIME_FORMAT = 'Y-m-d\TG:m:s.vO';

	/**
	 * Endpoint URL.
	 *
	 * @var string
	 */
	protected $endpoint;

	/**
	 * Client.
	 *
	 * @var ClientInterface
	 */
	protected $client;

	/**
	 * Authentication.
	 *
	 * @var AuthenticationInterface
	 */
	protected $authentication;

	/**
	 * Options.
	 *
	 * @var integer
	 */
	protected $options = self::AUTOMAP_FIELDS;

	/**
	 * Client-side cache of fields. List of fields when loaded, null when nothing is fetched yet.
	 *
	 * @var array|null
	 */
	protected $fields;

	/**
	 * Client-side cache of priorities. List of priorities when loaded, null when nothing is fetched yet.
	 *
	 * @var array|null
	 */
	protected $priorities;

	/**
	 * Client-side cache of statuses. List of statuses when loaded, null when nothing is fetched yet.
	 *
	 * @var array|null
	 */
	protected $statuses;

	/**
	 * Client-side cache of resolutions. List of resolutions when loaded, null when nothing is fetched yet.
	 *
	 * @var array|null
	 */
	protected $resolutions;

	/**
	 * Create a JIRA API client.
	 *
	 * @param string                  $endpoint       Endpoint URL.
	 * @param AuthenticationInterface $authentication Authentication.
	 * @param ClientInterface         $client         Client.
	 */
	public function __construct(
		$endpoint,
		AuthenticationInterface $authentication,
		ClientInterface $client = null
	) {
		$this->setEndPoint($endpoint);
		$this->authentication = $authentication;

		if ( $client === null ) {
			$client = new CurlClient();
		}

		$this->client = $client;
	}

	/**
	 * Sets options.
	 *
	 * @param integer $options Options.
	 *
	 * @return void
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * Get Endpoint URL.
	 *
	 * @return string
	 */
	public function getEndpoint()
	{
		return $this->endpoint;
	}

	/**
	 * Set Endpoint URL.
	 *
	 * @param string $url Endpoint URL.
	 *
	 * @return void
	 */
	public function setEndpoint($url)
	{
		// Remove trailing slash in the url.
		$url = rtrim($url, '/');

		if ( $url !== $this->endpoint ) {
			$this->endpoint = $url;
			$this->clearLocalCaches();
		}
	}

	/**
	 * Helper method to clear the local caches. Is called when switching endpoints
	 *
	 * @return void
	 */
	protected function clearLocalCaches()
	{
		$this->fields = null;
		$this->priorities = null;
		$this->statuses = null;
		$this->resolutions = null;
	}

	/**
	 * Get fields definitions.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issue-fields/#api-rest-api-2-field-get
	 */
	public function getFields()
	{
		// Fetch fields when the method is called for the first time.
		if ( $this->fields === null ) {
			$ret = array();
			$fields = $this->api(self::REQUEST_GET, '/rest/api/2/field', array(), true);

			foreach ( $fields as $field_data ) {
				$ret[$field_data['id']] = $field_data;
			}

			$this->fields = $ret;
		}

		return $this->fields;
	}

	/**
	 * Get specified issue.
	 *
	 * @param string $issue_key Issue key should be "YOURPROJ-221".
	 * @param string $expand    Expand.
	 *
	 * @return Result
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issues/#api-rest-api-2-issue-issueidorkey-get
	 */
	public function getIssue($issue_key, $expand = '')
	{
		$params = array();

		if ( $expand !== '' ) {
			$params['expand'] = $expand;
		}

		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/issue/%s', $issue_key), $params);
	}

	/**
	 * Edits the issue.
	 *
	 * @param string $issue_key Issue key.
	 * @param array  $params    Params.
	 *
	 * @return Result|false
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issues/#api-rest-api-2-issue-issueidorkey-put
	 */
	public function editIssue($issue_key, array $params)
	{
		return $this->api(self::REQUEST_PUT, sprintf('/rest/api/2/issue/%s', $issue_key), $params);
	}

	/**
	 * Gets attachments meta information.
	 *
	 * @return array
	 * @since  2.0.0
	 */
	public function getAttachmentsMetaInformation()
	{
		return $this->api(self::REQUEST_GET, '/rest/api/2/attachment/meta');
	}

	/**
	 * Gets attachment.
	 *
	 * @param string $attachment_id Attachment ID.
	 *
	 * @return array|false
	 */
	public function getAttachment($attachment_id)
	{
		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/attachment/%s', $attachment_id), array(), true);
	}

	/**
	 * Returns all projects.
	 *
	 * @return Result|false
	 */
	public function getProjects()
	{
		return $this->api(self::REQUEST_GET, '/rest/api/2/project');
	}

	/**
	 * Returns one project.
	 *
	 * @param string $project_key Project key.
	 *
	 * @return array|false
	 */
	public function getProject($project_key)
	{
		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/project/%s', $project_key), array(), true);
	}

	/**
	 * Returns all roles of a project.
	 *
	 * @param string $project_key Project key.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-project-roles/#api-rest-api-2-role-get
	 */
	public function getRoles($project_key)
	{
		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/project/%s/role', $project_key), array(), true);
	}

	/**
	 * Returns role details.
	 *
	 * @param string  $project_key Project key.
	 * @param integer $role_id     Role ID.
	 *
	 * @return array|false
	 */
	public function getRoleDetails($project_key, $role_id)
	{
		return $this->api(
			self::REQUEST_GET,
			sprintf('/rest/api/2/project/%s/role/%s', $project_key, $role_id),
			array(),
			true
		);
	}

	/**
	 * Returns the meta data for creating issues.
	 * This includes the available projects, issue types
	 * and fields, including field types and whether or not those fields are required.
	 * Projects will not be returned if the user does not have permission to create issues in that project.
	 * Fields will only be returned if "projects.issuetypes.fields" is added as expand parameter.
	 *
	 * @param array $project_ids      Combined with the projectKeys param, lists the projects with which to filter the
	 *                                results. If absent, all projects are returned. Specifying a project that does not
	 *                                exist (or that you cannot create issues in) is not an error, but it will not be
	 *                                in the results.
	 * @param array $project_keys     Combined with the projectIds param, lists the projects with which to filter the
	 *                                results. If null, all projects are returned. Specifying a project that does not
	 *                                exist (or that you cannot create issues in) is not an error, but it will not be
	 *                                in the results.
	 * @param array $issue_type_ids   Combined with issuetypeNames, lists the issue types with which to filter the
	 *                                results. If null, all issue types are returned. Specifying an issue type that
	 *                                does not exist is not an error.
	 * @param array $issue_type_names Combined with issuetypeIds, lists the issue types with which to filter the
	 *                                results. If null, all issue types are returned. This parameter can be specified
	 *                                multiple times, but is NOT interpreted as a comma-separated list. Specifying
	 *                                an issue type that does not exist is not an error.
	 * @param array $expand           Optional list of entities to expand in the response.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issues/#api-rest-api-2-issue-createmeta-get
	 */
	public function getCreateMeta(
		array $project_ids = null,
		array $project_keys = null,
		array $issue_type_ids = null,
		array $issue_type_names = null,
		array $expand = null
	) {
		// Create comma-separated query parameters for the supplied filters.
		$data = array();

		if ( $project_ids !== null ) {
			$data['projectIds'] = implode(',', $project_ids);
		}

		if ( $project_keys !== null ) {
			$data['projectKeys'] = implode(',', $project_keys);
		}

		if ( $issue_type_ids !== null ) {
			$data['issuetypeIds'] = implode(',', $issue_type_ids);
		}

		if ( $issue_type_names !== null ) {
			$data['issuetypeNames'] = implode(',', $issue_type_names);
		}

		if ( $expand !== null ) {
			$data['expand'] = implode(',', $expand);
		}

		return $this->api(self::REQUEST_GET, '/rest/api/2/issue/createmeta', $data, true);
	}

	/**
	 * Add a comment to a ticket.
	 *
	 * @param string       $issue_key Issue key should be "YOURPROJ-221".
	 * @param array|string $params    Params.
	 *
	 * @return Result|false
	 */
	public function addComment($issue_key, $params)
	{
		if ( is_string($params) ) {
			// If $params is scalar string value -> wrapping it properly.
			$params = array(
				'body' => $params,
			);
		}

		return $this->api(self::REQUEST_POST, sprintf('/rest/api/2/issue/%s/comment', $issue_key), $params);
	}

	/**
	 * Adds a worklog for an issue.
	 *
	 * @param string         $issue_key  Issue key should be "YOURPROJ-22".
	 * @param string|integer $time_spent Either string in "2w 4d 6h 45m" format or second count as a number.
	 * @param array          $params     Params.
	 *
	 * @return array
	 * @since  2.0.0
	 */
	public function addWorklog($issue_key, $time_spent, array $params = array())
	{
		if ( is_int($time_spent) ) {
			$params['timeSpentSeconds'] = $time_spent;
		}
		else {
			$params['timeSpent'] = $time_spent;
		}

		return $this->api(
			self::REQUEST_POST,
			sprintf('/rest/api/2/issue/%s/worklog', $issue_key),
			$params,
			true
		);
	}

	/**
	 * Get all worklogs for an issue.
	 *
	 * @param string $issue_key Issue key should be "YOURPROJ-22".
	 * @param array  $params    Params.
	 *
	 * @return Result|false
	 * @since  2.0.0
	 */
	public function getWorklogs($issue_key, array $params = array())
	{
		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/issue/%s/worklog', $issue_key), $params);
	}

	/**
	 * Deletes an issue worklog.
	 *
	 * @param string  $issue_key  Issue key should be "YOURPROJ-22".
	 * @param integer $worklog_id Work Log ID.
	 * @param array   $params     Params.
	 *
	 * @return array
	 * @since  2.0.0
	 */
	public function deleteWorklog($issue_key, $worklog_id, array $params = array())
	{
		return $this->api(
			self::REQUEST_DELETE,
			sprintf('/rest/api/2/issue/%s/worklog/%s', $issue_key, $worklog_id),
			$params,
			true
		);
	}

	/**
	 * Get available transitions for a ticket.
	 *
	 * @param string $issue_key Issue key should be "YOURPROJ-22".
	 * @param array  $params    Params.
	 *
	 * @return Result|false
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issues/#api-rest-api-2-issue-issueidorkey-transitions-get
	 */
	public function getTransitions($issue_key, array $params = array())
	{
		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/issue/%s/transitions', $issue_key), $params);
	}

	/**
	 * Transition a ticket.
	 *
	 * @param string $issue_key Issue key should be "YOURPROJ-22".
	 * @param array  $params    Params.
	 *
	 * @return Result|false
	 */
	public function transition($issue_key, array $params)
	{
		return $this->api(self::REQUEST_POST, sprintf('/rest/api/2/issue/%s/transitions', $issue_key), $params);
	}

	/**
	 * Get available issue types.
	 *
	 * @return IssueType[]
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issue-types/#api-rest-api-2-issuetype-get
	 */
	public function getIssueTypes()
	{
		$ret = array();
		$issue_types = $this->api(self::REQUEST_GET, '/rest/api/2/issuetype', array(), true);

		foreach ( $issue_types as $issue_type_data ) {
			$ret[] = new IssueType($issue_type_data);
		}

		return $ret;
	}

	/**
	 * Get versions of a project.
	 *
	 * @param string $project_key Project key.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-project-versions/#api-rest-api-2-project-projectidorkey-versions-get
	 */
	public function getVersions($project_key)
	{
		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/project/%s/versions', $project_key), array(), true);
	}

	/**
	 * Helper method to find a specific version based on the name of the version.
	 *
	 * @param string $project_key Project Key.
	 * @param string $name        The version name to match on.
	 *
	 * @return array|null Version data on match or null when there is no match.
	 * @since  2.0.0
	 */
	public function findVersionByName($project_key, $name)
	{
		// Fetch all versions of this project.
		$versions = $this->getVersions($project_key);

		// Don't iterate versions, when API call ended with an error.
		if ( array_key_exists('errorMessages', $versions) || array_key_exists('errors', $versions) ) {
			return null;
		}

		// Filter results on the name.
		$matching_versions = array_filter($versions, function (array $version) use ($name) {
			return $version['name'] == $name;
		});

		// Early out for no results.
		if ( empty($matching_versions) ) {
			return null;
		}

		// Multiple results should not happen since the name is unique.
		return reset($matching_versions);
	}

	/**
	 * Get available priorities.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issue-priorities/#api-rest-api-2-priority-get
	 * @since  2.0.0
	 */
	public function getPriorities()
	{
		// Fetch priorities when the method is called for the first time.
		if ( $this->priorities === null ) {
			$ret = array();
			$priorities = $this->api(self::REQUEST_GET, '/rest/api/2/priority', array(), true);

			foreach ( $priorities as $priority_data ) {
				$ret[$priority_data['id']] = $priority_data;
			}

			$this->priorities = $ret;
		}

		return $this->priorities;
	}

	/**
	 * Get available statuses.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-status/#api-rest-api-2-statuses-get
	 */
	public function getStatuses()
	{
		// Fetch statuses when the method is called for the first time.
		if ( $this->statuses === null ) {
			$ret = array();
			$statuses = $this->api(self::REQUEST_GET, '/rest/api/2/status', array(), true);

			foreach ( $statuses as $status_data ) {
				$ret[$status_data['id']] = $status_data;
			}

			$this->statuses = $ret;
		}

		return $this->statuses;
	}

	/**
	 * Creates an issue.
	 *
	 * @param string $project_key  Project key.
	 * @param string $summary      Summary.
	 * @param string $issue_type   Issue type.
	 * @param array  $other_fields Other fields.
	 *
	 * @return Result|false
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issues/#api-rest-api-2-issue-post
	 */
	public function createIssue($project_key, $summary, $issue_type, array $other_fields = array())
	{
		$default_fields = array(
			'project' => array(
				'key' => $project_key,
			),
			'summary' => $summary,
			'issuetype' => array(
				'id' => $issue_type,
			),
		);

		$fields = array_merge($default_fields, $other_fields);

		return $this->api(self::REQUEST_POST, '/rest/api/2/issue', array('fields' => $fields));
	}

	/**
	 * Query issues.
	 *
	 * @param string  $jql         JQL.
	 * @param integer $start_at    Start at.
	 * @param integer $max_results Max results.
	 * @param string  $fields      Fields.
	 *
	 * @return Result
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issue-search/#api-rest-api-2-search-get
	 */
	public function search($jql, $start_at = 0, $max_results = 20, $fields = '*navigable')
	{
		$result = $this->api(
			self::REQUEST_GET,
			'/rest/api/2/search',
			array(
				'jql' => $jql,
				'startAt' => $start_at,
				'maxResults' => $max_results,
				'fields' => $fields,
			)
		);

		return $result;
	}

	/**
	 * Creates new version.
	 *
	 * @param string $project_key Project key.
	 * @param string $version     Version.
	 * @param array  $params      Params.
	 *
	 * @return Result
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-project-versions/#api-rest-api-2-version-post
	 */
	public function createVersion($project_key, $version, array $params = array())
	{
		$params = array_merge(
			array(
				'name' => $version,
				'project' => $project_key,
				'archived' => false,
			),
			$params
		);

		return $this->api(self::REQUEST_POST, '/rest/api/2/version', $params);
	}

	/**
	 * Updates version.
	 *
	 * @param integer $version_id Version ID.
	 * @param array   $params     Key->Value list to update the version with.
	 *
	 * @return Result
	 * @since  2.0.0
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-project-versions/#api-rest-api-2-version-id-put
	 */
	public function updateVersion($version_id, array $params = array())
	{
		return $this->api(self::REQUEST_PUT, sprintf('/rest/api/2/version/%d', $version_id), $params);
	}

	/**
	 * Shorthand to mark a version as Released.
	 *
	 * @param integer     $version_id   Version ID.
	 * @param string|null $release_date Date in Y-m-d format (defaults to today).
	 * @param array       $params       Optionally extra parameters.
	 *
	 * @return Result
	 * @since  2.0.0
	 */
	public function releaseVersion($version_id, $release_date = null, array $params = array())
	{
		if ( !$release_date ) {
			$release_date = date('Y-m-d');
		}

		$params = array_merge(
			array(
				'releaseDate' => $release_date,
				'released' => true,
			),
			$params
		);

		return $this->updateVersion($version_id, $params);
	}

	/**
	 * Create attachment.
	 *
	 * @param string $issue_key Issue key.
	 * @param string $filename  Filename.
	 * @param string $name      Name.
	 *
	 * @return Result|false
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issue-attachments/#api-rest-api-2-issue-issueidorkey-attachments-post
	 */
	public function createAttachment($issue_key, $filename, $name = null)
	{
		$params = array(
			'file' => '@' . $filename,
			'name' => $name, // The NULL value is handled in the "ClientInterface" implementing class.
		);

		return $this->api(
			self::REQUEST_POST,
			sprintf('/rest/api/2/issue/%s/attachments', $issue_key),
			$params,
			false,
			true
		);
	}

	/**
	 * Creates a remote link.
	 *
	 * @param string $issue_key    Issue key.
	 * @param array  $object       Object.
	 * @param string $relationship Relationship.
	 * @param string $global_id    Global ID.
	 * @param array  $application  Application.
	 *
	 * @return array
	 * @since  2.0.0
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issue-remote-links/#api-rest-api-2-issue-issueidorkey-remotelink-post
	 */
	public function createRemoteLink(
		$issue_key,
		array $object,
		$relationship = null,
		$global_id = null,
		array $application = null
	) {
		$params = array_filter(array(
			'globalId' => $global_id,
			'relationship' => $relationship,
			'object' => $object,
			'application' => $application,
		));

		return $this->api(self::REQUEST_POST, sprintf('/rest/api/2/issue/%s/remotelink', $issue_key), $params, true);
	}

	/**
	 * Send request to specified host.
	 *
	 * @param string       $method          Request method.
	 * @param string       $url             URL.
	 * @param array|string $data            Data.
	 * @param boolean      $return_as_array Return results as associative array.
	 * @param boolean      $is_file         Is file-related request.
	 * @param boolean      $debug           Debug this request.
	 *
	 * @return array|Result|false
	 */
	public function api(
		$method,
		$url,
		$data = array(),
		$return_as_array = false,
		$is_file = false,
		$debug = false
	) {
		$result = $this->client->sendRequest(
			$method,
			$url,
			$data,
			$this->getEndpoint(),
			$this->authentication,
			$is_file,
			$debug
		);

		if ( strlen($result) ) {
			$json = json_decode($result, true);

			if ( $this->options & self::AUTOMAP_FIELDS ) {
				if ( isset($json['issues']) ) {
					if ( $this->fields === null ) {
						$this->getFields();
					}

					foreach ( $json['issues'] as $offset => $issue ) {
						$json['issues'][$offset] = $this->automapFields($issue);
					}
				}
			}

			if ( $return_as_array ) {
				return $json;
			}

			return new Result($json);
		}

		return false;
	}

	/**
	 * Downloads attachment.
	 *
	 * @param string $url URL in "https://your-jira-project.net/rest/api/2/attachment/content/{id}" format.
	 *
	 * @return array|string
	 * @throws Exception When a download URL is coming from a different Jira instance.
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issue-attachments/#api-rest-api-2-attachment-content-id-get
	 */
	public function downloadAttachment($url)
	{
		$relative_url = preg_replace('/^' . preg_quote($this->getEndpoint(), '/') . '/', '', $url);

		if ( $relative_url === $url ) {
			throw new Exception('The download url is coming from the different Jira instance.');
		}

		return $this->client->sendRequest(
			self::REQUEST_GET,
			$relative_url,
			array(),
			$this->getEndpoint(),
			$this->authentication,
			true,
			false // Needed for test suite to work (probably Prophecy glitch).
		);
	}

	/**
	 * Automaps issue fields.
	 *
	 * @param array $issue Issue.
	 *
	 * @return array
	 */
	protected function automapFields(array $issue)
	{
		if ( isset($issue['fields']) ) {
			$x = array();

			foreach ( $issue['fields'] as $kk => $vv ) {
				if ( isset($this->fields[$kk]) ) {
					$x[$this->fields[$kk]['name']] = $vv;
				}
				else {
					$x[$kk] = $vv;
				}
			}

			$issue['fields'] = $x;
		}

		return $issue;
	}

	/**
	 * Set issue watchers.
	 *
	 * @param string $issue_key Issue key.
	 * @param array  $watchers  Watchers.
	 *
	 * @return (Result|false)[]
	 */
	public function setWatchers($issue_key, array $watchers)
	{
		$result = array();

		foreach ( $watchers as $watcher ) {
			$result[] = $this->api(self::REQUEST_POST, sprintf('/rest/api/2/issue/%s/watchers', $issue_key), $watcher);
		}

		return $result;
	}

	/**
	 * Closes issue.
	 *
	 * @param string $issue_key Issue key.
	 *
	 * @return Result|array
	 * @TODO:  Should have parameters? (e.g comment)
	 */
	public function closeIssue($issue_key)
	{
		// Get available transitions.
		$tmp_transitions_result = $this->getTransitions($issue_key)->getResult();
		$transitions = $tmp_transitions_result['transitions'];

		// Look for "Close Issue" transition in issue transitions.
		foreach ( $transitions as $transition_data ) {
			if ( $transition_data['name'] !== 'Close Issue' ) {
				continue;
			}

			// Close issue if required id was found.
			return $this->transition(
				$issue_key,
				array(
					'transition' => array('id' => $transition_data['id']),
				)
			);
		}

		return array();
	}

	/**
	 * Returns project components.
	 *
	 * @param string $project_key Project key.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-project-components/#api-rest-api-2-project-projectidorkey-components-get
	 * @since  2.0.0
	 */
	public function getProjectComponents($project_key)
	{
		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/project/%s/components', $project_key), array(), true);
	}

	/**
	 * Get all issue types with valid status values for a project.
	 *
	 * @param string $project_key Project key.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-projects/#api-rest-api-2-project-projectidorkey-statuses-get
	 * @since  2.0.0
	 */
	public function getProjectIssueTypes($project_key)
	{
		return $this->api(self::REQUEST_GET, sprintf('/rest/api/2/project/%s/statuses', $project_key), array(), true);
	}

	/**
	 * Returns a list of all resolutions.
	 *
	 * @return array
	 * @link   https://developer.atlassian.com/cloud/jira/platform/rest/v2/api-group-issue-resolutions/#api-rest-api-2-resolution-get
	 * @since  2.0.0
	 */
	public function getResolutions()
	{
		// Fetch resolutions when the method is called for the first time.
		if ( $this->resolutions === null ) {
			$ret = array();
			$resolutions = $this->api(self::REQUEST_GET, '/rest/api/2/resolution', array(), true);

			foreach ( $resolutions as $resolution_data ) {
				$ret[$resolution_data['id']] = $resolution_data;
			}

			$this->resolutions = $ret;
		}

		return $this->resolutions;
	}

}
