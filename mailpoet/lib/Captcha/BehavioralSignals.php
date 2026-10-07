<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\WP\Functions as WPFunctions;

/**
 * Evaluates client-side behavioral counters to decide whether a submission
 * looks human. Used when no CAPTCHA is configured: silent pass when signals
 * look human, escalate to the built-in CAPTCHA otherwise.
 */
class BehavioralSignals {
  const FIELD_NAME = 'behavioral_signals';

  const DEFAULT_MIN_TIME_MS = 2000;
  const DEFAULT_MIN_INTERACTIONS = 3;
  const DEFAULT_MIN_FIELD_FOCUS = 1;

  const MAX_TIME_MS = 86400000;
  const MAX_COUNT = 100000;
  const MAX_FOCUS = 50;
  const MIN_MS_PER_KEYDOWN = 20;
  const MIN_MS_PER_MOUSEMOVE = 2;
  const STAMP_SLACK_MS = 5000;

  private WPFunctions $wp;

  private FormRenderStamp $formRenderStamp;

  public function __construct(
    WPFunctions $wp,
    FormRenderStamp $formRenderStamp
  ) {
    $this->wp = $wp;
    $this->formRenderStamp = $formRenderStamp;
  }

  /**
   * The render stamp proves how long ago the form was served. It is checked
   * unless the caller has already verified a solved CAPTCHA in the same request.
   *
   * @param array<string,mixed> $data
   */
  public function looksHuman(array $data, bool $requireStamp = true): bool {
    $rawSignals = $data[self::FIELD_NAME] ?? null;
    $result = is_array($rawSignals);
    $signals = is_array($rawSignals) ? $rawSignals : [];

    $timeMs = $this->readCounter($signals, 'time_ms', self::MAX_TIME_MS);
    $fieldFocusCount = $this->readCounter($signals, 'focus_count', self::MAX_FOCUS);
    $mousemoveCount = $this->readCounter($signals, 'mm_count', self::MAX_COUNT);
    $keydownCount = $this->readCounter($signals, 'kd_count', self::MAX_COUNT);
    $scrollCount = $this->readCounter($signals, 'scroll_count', self::MAX_COUNT);
    $isTouch = !empty($signals['touch']);

    if ($timeMs === null || $fieldFocusCount === null || $mousemoveCount === null || $keydownCount === null || $scrollCount === null) {
      $result = false;
    } else {
      if ($timeMs < self::DEFAULT_MIN_TIME_MS) {
        $result = false;
      }
      if ($fieldFocusCount < self::DEFAULT_MIN_FIELD_FOCUS) {
        $result = false;
      }
      if ($keydownCount * self::MIN_MS_PER_KEYDOWN > $timeMs || $mousemoveCount * self::MIN_MS_PER_MOUSEMOVE > $timeMs) {
        $result = false;
      }
      if ($result && $requireStamp) {
        $result = $this->isPlausibleForStamp($data[FormRenderStamp::FIELD_NAME] ?? null, $timeMs);
      }
      if ($result) {
        // OR-logic across device-appropriate interaction channels handles
        // password-manager autofill (no keydown), mobile (no mousemove),
        // and pure-mouse users (no keydown).
        if ($isTouch) {
          $result = $scrollCount >= 1 || $keydownCount >= self::DEFAULT_MIN_INTERACTIONS;
        } else {
          $result = $mousemoveCount >= self::DEFAULT_MIN_INTERACTIONS
                    || $keydownCount >= self::DEFAULT_MIN_INTERACTIONS;
        }
      }
    }

    return (bool)$this->wp->applyFilters('mailpoet_behavioral_signals_looks_human', $result, $rawSignals, $data);
  }

  /**
   * @param mixed $stamp
   */
  private function isPlausibleForStamp($stamp, int $timeMs): bool {
    $elapsed = $this->formRenderStamp->elapsedSeconds($stamp);
    if ($elapsed === null) {
      return false;
    }
    $elapsedMs = $elapsed * 1000;
    return $elapsedMs >= self::DEFAULT_MIN_TIME_MS && $timeMs <= $elapsedMs + self::STAMP_SLACK_MS;
  }

  /**
   * An absent counter counts as 0. A present one must be a non-negative integer
   * (or a string of digits) up to $max; anything else is rejected with null.
   *
   * @param array<mixed> $signals
   */
  private function readCounter(array $signals, string $key, int $max): ?int {
    $value = $signals[$key] ?? null;
    if ($value === null) {
      return 0;
    }
    if (is_string($value) && ctype_digit($value) && strlen($value) <= 10) {
      $value = (int)$value;
    }
    if (!is_int($value) || $value < 0 || $value > $max) {
      return null;
    }
    return $value;
  }
}
