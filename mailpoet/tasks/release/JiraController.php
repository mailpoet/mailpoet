<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class JiraController {

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

  /**
   * @see https://developer.atlassian.com/cloud/jira/platform/rest/v3/#api-api-3-search-get
   */
  public function search($jql, ?array $fields = null) {
    $params = ['jql' => $jql];
    if ($fields) {
      $params['fields'] = join(',', $fields);
    }
    $response = $this->httpClient->get('search', ['query' => $params]);
    return json_decode($response->getBody()->getContents(), true);
  }
}
