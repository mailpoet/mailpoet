<?php declare(strict_types = 1);

namespace MailPoet\Cron\Workers;

use MailPoet\Analytics\Analytics;
use MailPoet\Entities\ScheduledTaskEntity;

class Tracks extends SimpleWorker {

  /** @var Analytics */
  private $analytics;

  const TASK_TYPE = 'tracks';

  public function __construct(
    Analytics $analytics
  ) {
    parent::__construct();
    $this->analytics = $analytics;
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    return $this->maybeReportAnalyticsToTracks();
  }

  public function maybeReportAnalyticsToTracks(): bool {
    if (!$this->analytics->shouldSendToTracks()) {
      return true;
    }
    return $this->reportAnalyticsToTracks();
  }

  public function reportAnalyticsToTracks(): bool {
    $publicId = $this->analytics->getPublicId();

    if (strlen($publicId) < 1) {
      return true;
    }

    $data = $this->analytics->getAnalyticsData();

    $success = $this->sendToTracksAPI($publicId, $data);

    if ($success) {
      $this->analytics->recordTracksDataSent();
    }

    return $success;
  }

  private function convertKeysToSnakeCase(array $data): array {
    $converted = [];

    foreach ($data as $key => $value) {
      $snakeKey = $this->normalizeKeyToSnakeCase($key);
      $converted[$snakeKey] = $value;
    }

    return $converted;
  }

  private function normalizeKeyToSnakeCase(string $key): string {
    // Step 1: Convert to lowercase
    $key = strtolower($key);

    // Step 2: Remove quotes and apostrophes
    $key = str_replace(['\'', '"', '`'], '', $key);

    // Step 3: Replace dashes directly with underscores
    $key = str_replace(['-', '–', '.'], '_', $key);

    // Step 4: Replace other separators with spaces
    $key = preg_replace('/[_:>()<>\[\]{}|\\\\\/]+/', ' ', $key);

    // Step 5: Remove redundant spaces
    $key = preg_replace('/\s+/', ' ', trim((string)$key));

    // Step 6: Replace spaces with underscores
    $key = str_replace(' ', '_', (string)$key);

    return $key;
  }

  private function sendToTracksAPI(string $publicId, array $data): bool {
    $url = 'https://public-api.wordpress.com/rest/v1.1/tracks/record';

    // Convert Analytics data keys to snake_case for consistency
    $convertedData = $this->convertKeysToSnakeCase($data);

    $payload = [
      'commonProps' => array_merge([
        'public_id' => $publicId,
      ], $convertedData),
      'events' => [
        '_ut' => 'anon',
        '_ui' => $publicId,
        '_en' => 'mailpoet_user_profile',
      ],
    ];

    $jsonPayload = json_encode($payload);
    if ($jsonPayload === false) {
      return false;
    }

    $args = [
      'method' => 'POST',
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'User-Agent' => 'MailPoet Plugin',
      ],
      'body' => $jsonPayload,
      'timeout' => 30,
    ];

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
      return false;
    }

    $statusCode = wp_remote_retrieve_response_code($response);
    return $statusCode >= 200 && $statusCode < 300;
  }

  public function getNextRunDate() {
    return $this->analytics->getNextSendDateForTracks()->addMinutes(rand(0, 59));
  }
}
