<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Analytics\Analytics;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * Sends a Tracks event for calls of MailPoet abilities on sites that opted in to analytics:
 * successful calls, errors and exceptions of the ability's callback (only for abilities registered
 * by AbilitiesRegistrar) and, on WordPress 7.1+, input that fails validation. Calls without
 * permission and output that fails validation are not recorded. Only the ability name and the
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

  /** @var array<string, true> */
  private $invalidInputRecorded = [];

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
    // WordPress 7.1+. Input that fails validation never reaches the ability's callback.
    $this->wp->addFilter('wp_ability_validate_input', [$this, 'recordInvalidInput'], PHP_INT_MAX, 3);
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

  /**
   * @param true|\WP_Error $isValid
   * @param mixed $input
   * @param mixed $abilityName
   * @return true|\WP_Error
   */
  public function recordInvalidInput($isValid, $input, $abilityName) {
    if (
      !$isValid instanceof \WP_Error
      || !is_string($abilityName)
      || strpos($abilityName, self::ABILITY_PREFIX) !== 0
      // The REST API validates the input more than once per call, so one error per ability is recorded until the next flush.
      || isset($this->invalidInputRecorded[$abilityName])
    ) {
      return $isValid;
    }
    // The REST API validates the input before it checks permissions. Callers who may not run the ability aren't recorded,
    // like for valid input. The permission callbacks of MailPoet abilities only check capabilities, not the input.
    $ability = $this->wp->getAbility($abilityName);
    if (!$ability || $ability->check_permissions($input) !== true) {
      return $isValid;
    }
    $this->invalidInputRecorded[$abilityName] = true;
    $this->record($abilityName, $this->getErrorCode($isValid));
    return $isValid;
  }

  public function flush(): void {
    $events = $this->events;
    $this->events = [];
    $this->invalidInputRecorded = [];
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
    // A WP-CLI process can serve many calls (the MCP adapter's STDIO server) and may never reach shutdown.
    if (defined('WP_CLI') && WP_CLI) {
      try {
        $this->flush();
      } catch (\Throwable $e) {
        // Sent while the ability call is still running, which usage data must never break.
      }
      return;
    }
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
