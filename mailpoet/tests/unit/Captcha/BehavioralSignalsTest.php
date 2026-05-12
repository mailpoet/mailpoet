<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use Codeception\Stub;
use MailPoet\WP\Functions as WPFunctions;

class BehavioralSignalsTest extends \MailPoetUnitTest {
  private function makeTestee(?array $customThresholds = null): BehavioralSignals {
    $wp = Stub::make(
      WPFunctions::class,
      [
        'applyFilters' => function ($filter, $value) use ($customThresholds) {
          if ($filter === 'mailpoet_behavioral_signals_thresholds' && $customThresholds !== null) {
            return $customThresholds;
          }
          return $value;
        },
      ],
      $this
    );
    return new BehavioralSignals($wp);
  }

  public function testReturnsFalseWhenSignalsMissing() {
    $testee = $this->makeTestee();
    verify($testee->looksHuman([]))->false();
  }

  public function testReturnsFalseWhenSignalsAreNotAnArray() {
    $testee = $this->makeTestee();
    verify($testee->looksHuman(['behavioral_signals' => 'malformed']))->false();
  }

  public function testReturnsFalseWhenTimeBelowThreshold() {
    $testee = $this->makeTestee();
    $data = ['behavioral_signals' => [
      'time_ms' => 500,
      'mm_count' => 50,
      'kd_count' => 10,
      'focus_count' => 2,
      'touch' => false,
    ]];
    verify($testee->looksHuman($data))->false();
  }

  public function testReturnsFalseWhenNoFieldFocus() {
    $testee = $this->makeTestee();
    $data = ['behavioral_signals' => [
      'time_ms' => 5000,
      'mm_count' => 50,
      'kd_count' => 10,
      'focus_count' => 0,
      'touch' => false,
    ]];
    verify($testee->looksHuman($data))->false();
  }

  public function testDesktopPassesWithMouseMovement() {
    $testee = $this->makeTestee();
    $data = ['behavioral_signals' => [
      'time_ms' => 3000,
      'mm_count' => 25,
      'kd_count' => 0,
      'focus_count' => 1,
      'touch' => false,
    ]];
    verify($testee->looksHuman($data))->true();
  }

  public function testDesktopPassesWithKeydownEvenIfNoMouseMovement() {
    // Password-manager autofill scenario: focus fires but no keystrokes from the user;
    // a typing user would still pass via kd_count.
    $testee = $this->makeTestee();
    $data = ['behavioral_signals' => [
      'time_ms' => 3000,
      'mm_count' => 0,
      'kd_count' => 15,
      'focus_count' => 2,
      'touch' => false,
    ]];
    verify($testee->looksHuman($data))->true();
  }

  public function testDesktopFailsWithoutMouseOrKeydown() {
    $testee = $this->makeTestee();
    $data = ['behavioral_signals' => [
      'time_ms' => 5000,
      'mm_count' => 0,
      'kd_count' => 0,
      'focus_count' => 1,
      'touch' => false,
    ]];
    verify($testee->looksHuman($data))->false();
  }

  public function testTouchPassesWithScrollOnly() {
    // Mobile user who scrolled but didn't type or move a mouse — still human.
    $testee = $this->makeTestee();
    $data = ['behavioral_signals' => [
      'time_ms' => 4000,
      'mm_count' => 0,
      'kd_count' => 0,
      'scroll_count' => 3,
      'focus_count' => 1,
      'touch' => true,
    ]];
    verify($testee->looksHuman($data))->true();
  }

  public function testTouchPassesWithKeydown() {
    $testee = $this->makeTestee();
    $data = ['behavioral_signals' => [
      'time_ms' => 4000,
      'mm_count' => 0,
      'kd_count' => 5,
      'scroll_count' => 0,
      'focus_count' => 1,
      'touch' => true,
    ]];
    verify($testee->looksHuman($data))->true();
  }

  public function testTouchFailsWithoutInteraction() {
    $testee = $this->makeTestee();
    $data = ['behavioral_signals' => [
      'time_ms' => 5000,
      'mm_count' => 100,
      'kd_count' => 0,
      'scroll_count' => 0,
      'focus_count' => 1,
      'touch' => true,
    ]];
    // Touch path ignores mm_count; needs scroll or kd.
    verify($testee->looksHuman($data))->false();
  }

  public function testThresholdsAreFilterable() {
    $testee = $this->makeTestee([
      'min_time_ms' => 100,
      'min_interactions' => 1,
      'min_field_focus' => 1,
    ]);
    $data = ['behavioral_signals' => [
      'time_ms' => 150,
      'mm_count' => 1,
      'kd_count' => 0,
      'focus_count' => 1,
      'touch' => false,
    ]];
    verify($testee->looksHuman($data))->true();
  }

  public function testInvalidFilterValueFallsBackToDefaults() {
    $testee = $this->makeTestee(['nonsense' => 'data']);
    $data = ['behavioral_signals' => [
      'time_ms' => 3000,
      'mm_count' => 25,
      'kd_count' => 0,
      'focus_count' => 1,
      'touch' => false,
    ]];
    verify($testee->looksHuman($data))->true();
  }
}
