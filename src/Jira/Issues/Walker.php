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
namespace chobie\Jira\Issues;


use chobie\Jira\Api;

class Walker implements \IteratorAggregate
{

	/**
	 * API.
	 *
	 * @var Api
	 */
	protected $api;

	/**
	 * JQL.
	 *
	 * @var string
	 */
	protected $jql = null;

	/**
	 * Issues per page.
	 *
	 * @var integer
	 */
	protected $perPage = 50;

	/**
	 * List of fields to query.
	 *
	 * @var string|array|null
	 */
	protected $fields = null;

	/**
	 * Callback.
	 *
	 * @var callable
	 */
	protected $callback;

	/**
	 * Creates walker instance.
	 *
	 * @param Api          $api      API.
	 * @param integer|null $per_page Per page.
	 */
	public function __construct(Api $api, $per_page = null)
	{
		$this->api = $api;

		if ( is_numeric($per_page) ) {
			$this->perPage = $per_page;
		}
	}

	/**
	 * Pushes JQL.
	 *
	 * @param string            $jql    JQL.
	 * @param string|array|null $fields Fields.
	 *
	 * @return void
	 */
	public function push($jql, $fields = null)
	{
		$this->jql = $jql;
		$this->fields = $fields;
	}

	/**
	 * @inheritDoc
	 *
	 * @throws \Exception When "Walker::push" method wasn't called.
	 * @throws Api\UnauthorizedException When it happens.
	 */
	public function getIterator()
	{
		if ( $this->jql === null ) {
			throw new \Exception('you have to call Jira_Walker::push($jql, $fields) at first');
		}

		$next_page_token = null;
		$jql = $this->getQuery();

		try {
			while ( true ) {
				$result = $this->api->search($jql, $next_page_token, $this->perPage, $this->fields);

				if ( $result->getIssuesCount() === 0 ) {
					return;
				}

				foreach ( $result->getIssues() as $issue ) {
					if ( is_callable($this->callback) ) {
						$callback = $this->callback;

						yield $callback($issue);
					}
					else {
						yield $issue;
					}
				}

				$raw_result = $result->getResult();
				$next_page_token = array_key_exists('nextPageToken', $raw_result) ? $raw_result['nextPageToken'] : null;

				if ( empty($next_page_token) ) {
					break;
				}
			}
		}
		catch ( Api\UnauthorizedException $e ) {
			throw $e;
		}
		catch ( \Exception $e ) {
			error_log($e->getMessage());
		}
	}

	/**
	 * Sets callable.
	 *
	 * @param callable $callable Callable.
	 *
	 * @return void
	 * @throws \Exception When not a callable passed.
	 */
	public function setDelegate($callable)
	{
		if ( is_callable($callable) ) {
			$this->callback = $callable;
		}
		else {
			throw new \Exception('passed argument is not callable');
		}
	}

	/**
	 * Returns JQL.
	 *
	 * @return string
	 */
	protected function getQuery()
	{
		return $this->jql;
	}

}
