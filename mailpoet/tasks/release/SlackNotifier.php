<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoetTasks\Release;

use GuzzleHttp\Client;

class SlackNotifier {
  const PROJECT_MAILPOET = 'MAILPOET';
  const PROJECT_PREMIUM = 'PREMIUM';

  /** @var string */
  private $webhookUrl;

  /** @var string */
  private $project;

  /** @var Client */
  private $httpClient;

  public function __construct(
    $webhookUrl,
    $project
  ) {
    $this->webhookUrl = $webhookUrl;
    $this->project = $project;
    $this->httpClient = new Client();
  }

  public function notify($version, $changelog, $releaseId) {
    $message = $this->formatMessage($version, $changelog, $releaseId);
    $this->sendMessage($message);
  }

  private function formatMessage($version, $changelog, $releaseId) {
    $pluginType = $this->project === self::PROJECT_MAILPOET ? 'Free' : 'Premium';
    $githubPath = $this->project === self::PROJECT_MAILPOET ? 'mailpoet' : 'mailpoet-premium';

    $message = "*$pluginType plugin `$version` released :tada:!*\n";
    $message .= "\n";
    $message .= "GitHub: https://github.com/mailpoet/$githubPath/releases/tag/$version\n";
    $message .= "JIRA: https://mailpoet.atlassian.net/projects/$this->project/versions/$releaseId\n";
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

    $this->httpClient->post($this->webhookUrl, [
      'json' => [
        'text' => $message,
        'unfurl_links' => false,
      ],
    ]);
  }
}
