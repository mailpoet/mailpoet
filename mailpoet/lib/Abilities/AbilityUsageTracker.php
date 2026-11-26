<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Analytics\Analytics;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * Sends a Tracks event for calls of MailPoet abilities on sites that opted in to analytics:
 * successful calls, and errors and exceptions of the ability's callback. Calls without permission
 * and input or output that fails validation are not recorded. Only the ability name and the
 * outcome are sent, never the input or the output.
 * The props follow WooCommerce's `ability_called` event.
 */
class AbilityUsageTracker {
  private const EVENT_NAME = 'mailpoet_ability_called';
  private const TRACKS_URL = 'https://public-api.wordpress.com/rest/v1.1/tracks/record';
  private const ABILITY_PREFIX = 'mailpoet/';
  private const NONE = 'none';
  private const MAX_EVENTS_PER_REQUEST = 25;

  /** @var Analytics */
  private $analytics;

  /** @var WPFunctions */
  private $wp;

  /** @var array<int, array{public_id: string, ability_name: string, outcome: string, error_code: string}> */
  private $events = [];

  /** @var bool */
  private $flushScheduled = false;

  public function __construct(
    Analytics $analytics,
    WPFunctions $wp
  ) {
    $this->analytics = $analytics;
    $this->wp = $wp;
  }

  public function init(): void {
    // Fires after the output was validated, so only for calls that succeeded.
    $this->wp->addAction('wp_after_execute_ability', [$this, 'recordSuccess']);
  }

  /**
   * Records errors and exceptions of an ability's own callback. Successful calls are recorded
   * by the wp_after_execute_ability action instead.
   */
  public function wrapExecuteCallback(string $abilityName, callable $callback): callable {
    return function (...$args) use ($abilityName, $callback) {
      try {
        $result = $callback(...$args);
      } catch (\Throwable $e) {
        $this->record($abilityName, 'exception');
        throw $e;
      }
      if ($result instanceof \WP_Error) {
        $this->record($abilityName, $this->getErrorCode($result));
      }
      return $result;
    };
  }

  /** @param mixed $abilityName */
  public function recordSuccess($abilityName): void {
    if (is_string($abilityName)) {
      $this->record($abilityName, null);
    }
  }

  public function flush(): void {
    $events = $this->events;
    $this->events = [];
    $this->flushScheduled = false;
    foreach ($events as $event) {
      $this->send($event);
    }
  }

  private function record(string $abilityName, ?string $errorCode): void {
    if (strpos($abilityName, self::ABILITY_PREFIX) !== 0 || count($this->events) >= self::MAX_EVENTS_PER_REQUEST) {
      return;
    }
    try {
      if (!$this->analytics->isEnabled()) {
        return;
      }
      $publicId = $this->analytics->getPublicId();
    } catch (\Throwable $e) {
      // Usage data must never break an ability call.
      return;
    }
    $this->events[] = [
      'public_id' => $publicId,
      // Same format as WooCommerce, e.g. mailpoet__get-sending-status.
      'ability_name' => str_replace('/', '__', $abilityName),
      'outcome' => $errorCode === null ? 'success' : 'error',
      'error_code' => $errorCode ?? self::NONE,
    ];
    if (!$this->flushScheduled) {
      $this->flushScheduled = true;
      $this->wp->addAction('shutdown', [$this, 'flush']);
    }
  }

  private function getErrorCode(\WP_Error $error): string {
    $code = (string)$error->get_error_code();
    // Error codes are identifiers like ability_invalid_input: 1-64 letters, digits, "_", ":" or "-".
    // Anything else (a message used as a code, an empty code) is sent as "other", as in WooCommerce.
    // \z instead of $, which would also accept a trailing newline.
    return preg_match('/^[A-Za-z0-9_:-]{1,64}\z/', $code) === 1 ? $code : 'other';
  }

  /** @param array{public_id: string, ability_name: string, outcome: string, error_code: string} $event */
  private function send(array $event): void {
    $body = json_encode([
      'commonProps' => $event,
      'events' => [[
        '_ut' => 'anon',
        '_ui' => $event['public_id'],
        '_en' => self::EVENT_NAME,
      ]],
    ]);
    if ($body === false) {
      return;
    }
    $this->wp->wpRemotePost(self::TRACKS_URL, [
      'blocking' => false,
      'timeout' => 1,
      'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'User-Agent' => 'MailPoet Plugin',
      ],
      'body' => $body,
    ]);
  }
}
