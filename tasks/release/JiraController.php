<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class JiraController {

  const CHANGELOG_FIELD_ID = 'customfield_10500';
  const RELEASENOTE_FIELD_ID = 'customfield_10504';
  const VERSION_INCREMENT_FIELD_ID = 'customfield_10509';
  const PULL_REQUESTS_ID = 'customfield_10000';

  const WONT_DO_RESOLUTION_ID = '10001';

  const PROJECT_MAILPOET = 'MAILPOET';
  const PROJECT_PREMIUM = 'PREMIUM';

  const JIRA_DOMAIN = 'mailpoet.atlassian.net';
  const JIRA_API_VERSION = '3';

  /** @var string */
  private $token;

  /** @var string */
  private $user;

  /** @var string */
  private $project;

  /** @var Client */
  private $http_client;

  public function __construct($token, $user, $project) {
    $this->token = $token;
    $this->user = $user;
    $this->project = $project;

    $url_user = urlencode($this->user);
    $url_token = urlencode($this->token);
    $jira_domain = self::JIRA_DOMAIN;
    $jira_api_version = self::JIRA_API_VERSION;
    $base_uri = "https://$url_user:$url_token@$jira_domain/rest/api/$jira_api_version/";
    $this->http_client = new Client(['base_uri' => $base_uri]);
  }

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-project-projectIdOrKey-versions-get
   */
  public function getVersion($version_name = null) {
    $response = $this->http_client->get("project/$this->project/versions");
    $versions = json_decode($response->getBody()->getContents(), true);
    if ($version_name === null) {
      return end($versions);
    }
    foreach ($versions as $version) {
      if ($version_name === $version['name']) {
        return $version;
      }
    }
    throw new \Exception('Unknown project version');
  }

  public function getLastVersion() {
    $response = $this->http_client->get("project/$this->project/version", [
      'query' => [
        'maxResults' => 1,
        'orderBy' => '-sequence',
      ],
    ]);
    $version = json_decode($response->getBody()->getContents(), true);
    if (empty($version) || empty($version['values'])) {
      throw new \Exception('No version found');
    }
    return reset($version['values']);
  }

  public function getLastReleasedVersion() {
    $response = $this->http_client->get("project/$this->project/version", [
      'query' => [
        'maxResults' => 1,
        'orderBy' => '-sequence',
        'status' => 'released',
      ],
    ]);
    $version = json_decode($response->getBody()->getContents(), true);
    if (empty($version) || empty($version['values'])) {
      throw new \Exception('No released versions found');
    }
    return reset($version['values']);
  }

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-version-post
   */
  public function createVersion($version_name) {
    $data = [
      'name' => $version_name,
      'archived' => false,
      'released' => false,
      'project' => $this->project,
      'startDate' => (new \DateTime())->format('Y-m-d'),
    ];
    $response = $this->http_client->post('version', ['json' => $data]);
    return json_decode($response->getBody()->getContents(), true);
  }

  public function releaseVersion($version_name) {
    $version = $this->getVersion($version_name);
    $response = $this->http_client->put("version/$version[id]", [
      'json' => [
        'released' => true,
        'releaseDate' => (new \DateTime())->format('Y-m-d'),
      ],
    ]);
    return json_decode($response->getBody()->getContents(), true);
  }

  public function getIssuesDataForVersion($version) {
    $changelog_id = self::CHANGELOG_FIELD_ID;
    $release_note_id = self::RELEASENOTE_FIELD_ID;
    $pull_requests_id = self::PULL_REQUESTS_ID;
    $issues_data = $this->search("fixVersion={$version['id']}", ['key', $changelog_id, $release_note_id, 'status', 'resolution', $pull_requests_id]);
    // Sort issues by importance of change (Added -> Updated -> Improved -> Changed -> Fixed -> Others)
    usort($issues_data['issues'], function($a, $b) use ($changelog_id) {
      $order = array_flip(['added', 'updat', 'impro', 'chang', 'fixed']);
      $a_prefix = strtolower(substr($a['fields'][$changelog_id], 0, 5));
      $b_prefix = strtolower(substr($b['fields'][$changelog_id], 0, 5));
      $a_rank = isset($order[$a_prefix]) ? $order[$a_prefix] : count($order);
      $b_rank = isset($order[$b_prefix]) ? $order[$b_prefix] : count($order);
      return $a_rank - $b_rank;
    });
    return $issues_data['issues'];
  }

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-search-get
   */
  public function search($jql, array $fields = null) {
    $params = ['jql' => $jql];
    if ($fields) {
      $params['fields'] = join(',', $fields);
    }
    $response = $this->http_client->get('search', ['query' => $params]);
    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-issue-issueIdOrKey-put
   */
  public function updateIssue($key, $data) {
    $this->http_client->put("issue/$key", ['json' => $data]);
  }
}
