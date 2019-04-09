<?php

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class SlackNotifier {
  const PROJECT_MAILPOET = 'MAILPOET';
  const PROJECT_PREMIUM = 'PREMIUM';

  /** @var string */
  private $webhook_url;

  /** @var string */
  private $project;

  /** @var Client */
  private $http_client;

  public function __construct($webhook_url, $project) {
    $this->webhook_url = $webhook_url;
    $this->project = $project;
    $this->http_client = new Client();
  }

  public function notify($version, $changelog, $release_id) {
    $message = $this->formatMessage($version, $changelog, $release_id);
    $this->sendMessage($message);
  }

  private function formatMessage($version, $changelog, $release_id) {
    $plugin_type = $this->project === self::PROJECT_MAILPOET ? 'Free' : 'Premium';
    $github_path = $this->project === self::PROJECT_MAILPOET ? 'mailpoet' : 'mailpoet-premium';

    $message = "*$plugin_type plugin `$version` released :tada:!*\n";
    $message .= "\n";
    $message .= "GitHub: https://github.com/mailpoet/$github_path/releases/tag/$version\n";
    $message .= "JIRA: https://mailpoet.atlassian.net/projects/$this->project/versions/$release_id\n";
    if ($this->project === self::PROJECT_MAILPOET) {
      $message .= "WordPress: https://wordpress.org/plugins/mailpoet/#developers\n";
    }
    $message .= "\n";
    $message .= "Changelog:\n";
    $message .= "```\n";
    $message .= "$changelog\n";
    $message .= "```\n";
    return $message;
  }

  private function sendMessage($message) {
    // https://api.slack.com/docs/message-formatting#how_to_escape_characters
    $message = preg_replace(['/&/u', '/</u', '/>/u'], ['&amp;', '&lt;', '&gt;'], $message);

    $this->http_client->post($this->webhook_url, [
      'json' => [
        'text' => $message,
        'unfurl_links' => false,
      ],
    ]);
  }
}
