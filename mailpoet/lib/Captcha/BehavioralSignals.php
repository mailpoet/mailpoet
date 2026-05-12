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

  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function looksHuman(array $data): bool {
    $signals = $data[self::FIELD_NAME] ?? null;
    if (!is_array($signals)) {
      return false;
    }

    $thresholds = $this->getThresholds();
    $timeMs = $this->intSignal($signals, 'time_ms');
    $fieldFocusCount = $this->intSignal($signals, 'focus_count');

    if ($timeMs < $thresholds['min_time_ms']) {
      return false;
    }
    if ($fieldFocusCount < $thresholds['min_field_focus']) {
      return false;
    }

    $mousemoveCount = $this->intSignal($signals, 'mm_count');
    $keydownCount = $this->intSignal($signals, 'kd_count');
    $scrollCount = $this->intSignal($signals, 'scroll_count');
    $isTouch = !empty($signals['touch']);

    // OR-logic across device-appropriate interaction channels handles
    // password-manager autofill (no keydown), mobile (no mousemove),
    // and pure-mouse users (no keydown).
    if ($isTouch) {
      return $scrollCount >= 1 || $keydownCount >= $thresholds['min_interactions'];
    }
    return $mousemoveCount >= $thresholds['min_interactions']
      || $keydownCount >= $thresholds['min_interactions'];
  }

  private function intSignal(array $signals, string $key): int {
    $value = $signals[$key] ?? null;
    return is_numeric($value) ? (int)$value : 0;
  }

  private function getThresholds(): array {
    $defaults = [
      'min_time_ms' => self::DEFAULT_MIN_TIME_MS,
      'min_interactions' => self::DEFAULT_MIN_INTERACTIONS,
      'min_field_focus' => self::DEFAULT_MIN_FIELD_FOCUS,
    ];
    $filtered = $this->wp->applyFilters('mailpoet_behavioral_signals_thresholds', $defaults);
    if (!is_array($filtered)) {
      return $defaults;
    }
    return array_merge($defaults, $filtered);
  }
}
