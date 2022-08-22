<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class JiraController {

  const CHANGELOG_FIELD_ID = 'customfield_10500';
  const RELEASENOTE_FIELD_ID = 'customfield_10504';
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
  private $httpClient;

  public function __construct(
    $token,
    $user,
    $project
  ) {
    $this->token = $token;
    $this->user = $user;
    $this->project = $project;

    $urlUser = urlencode($this->user);
    $urlToken = urlencode($this->token);
    $jiraDomain = self::JIRA_DOMAIN;
    $jiraApiVersion = self::JIRA_API_VERSION;
    $baseUri = "https://$urlUser:$urlToken@$jiraDomain/rest/api/$jiraApiVersion/";
    $this->httpClient = new Client(['base_uri' => $baseUri]);
  }

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-project-projectIdOrKey-versions-get
   */
  public function getVersion($versionName = null) {
    $response = $this->httpClient->get("project/$this->project/versions", [
      'query' => [
        'orderBy' => '-releaseDate',
      ],
    ]);
    $versions = json_decode($response->getBody()->getContents(), true);
    if ($versionName === null) {
      return end($versions);
    }
    foreach ($versions as $version) {
      if ($versionName === $version['name']) {
        return $version;
      }
    }
    throw new \Exception('Unknown project version');
  }

  public function getLastVersion() {
    $response = $this->httpClient->get("project/$this->project/version", [
      'query' => [
        'maxResults' => 1,
        'orderBy' => '-releaseDate',
      ],
    ]);
    $version = json_decode($response->getBody()->getContents(), true);
    if (empty($version) || empty($version['values'])) {
      throw new \Exception('No version found');
    }
    return reset($version['values']);
  }

  public function getLastReleasedVersion(?string $project = null) {
    $project = $project ?: $this->project;
    $response = $this->httpClient->get("project/$project/version", [
      'query' => [
        'maxResults' => 10,
        'orderBy' => '-releaseDate',
        'status' => 'released',
      ],
    ]);
    $version = json_decode($response->getBody()->getContents(), true);
    if (empty($version) || empty($version['values'])) {
      throw new \Exception('No released versions found');
    }
    $version['values'] = array_filter($version['values'], function ($item) {
      return VersionHelper::validateVersion($item['name']);
    });
    if (empty($version['values'])) {
      throw new \Exception('No released versions matching MP3 version format found');
    }
    return reset($version['values']);
  }

  public function getPreparedReleaseVersion(?string $project = null): array {
    $project = $project ?: $this->project;
    $response = $this->httpClient->get("project/$project/version", [
      'query' => [
        'maxResults' => 1,
        'orderBy' => '-releaseDate',
        'status' => 'unreleased',
      ],
    ]);
    $version = json_decode($response->getBody()->getContents(), true);
    if (empty($version) || empty($version['values'])) {
      throw new \Exception('No prepared version found');
    }
    return reset($version['values']);
  }

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-version-post
   */
  public function createVersion($versionName) {
    $data = [
      'name' => $versionName,
      'archived' => false,
      'released' => false,
      'project' => $this->project,
      'startDate' => (new \DateTime())->format('Y-m-d'),
    ];
    $response = $this->httpClient->post('version', ['json' => $data]);
    return json_decode($response->getBody()->getContents(), true);
  }

  public function releaseVersion($versionName) {
    $version = $this->getVersion($versionName);
    $response = $this->httpClient->put("version/$version[id]", [
      'json' => [
        'released' => true,
        'releaseDate' => (new \DateTime())->format('Y-m-d'),
      ],
    ]);
    return json_decode($response->getBody()->getContents(), true);
  }

  public function getIssuesDataForVersion($version) {
    $changelogId = self::CHANGELOG_FIELD_ID;
    $releaseNoteId = self::RELEASENOTE_FIELD_ID;
    $pullRequestsId = self::PULL_REQUESTS_ID;
    $issuesData = $this->search("fixVersion={$version['id']}", ['key', $changelogId, $releaseNoteId, 'status', 'resolution', $pullRequestsId]);
    // Sort issues by importance of change (Added -> Updated -> Improved -> Changed -> Fixed -> Others)
    usort($issuesData['issues'], function($a, $b) use ($changelogId) {
      $order = array_flip(['added', 'updat', 'impro', 'chang', 'fixed']);
      $aPrefix = !is_null($a['fields'][$changelogId]) ? strtolower(substr($a['fields'][$changelogId], 0, 5)) : '';
      $bPrefix = !is_null($b['fields'][$changelogId]) ? strtolower(substr($b['fields'][$changelogId], 0, 5)) : '';
      $aRank = isset($order[$aPrefix]) ? $order[$aPrefix] : count($order);
      $bRank = isset($order[$bPrefix]) ? $order[$bPrefix] : count($order);
      return $aRank - $bRank;
    });
    return $issuesData['issues'];
  }

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-search-get
   */
  public function search($jql, array $fields = null) {
    $params = ['jql' => $jql];
    if ($fields) {
      $params['fields'] = join(',', $fields);
    }
    $response = $this->httpClient->get('search', ['query' => $params]);
    return json_decode($response->getBody()->getContents(), true);
  }

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-issue-issueIdOrKey-put
   */
  public function updateIssue($key, $data) {
    $this->httpClient->put("issue/$key", ['json' => $data]);
  }
}
