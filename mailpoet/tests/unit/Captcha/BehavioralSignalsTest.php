<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use Codeception\Stub;
use MailPoet\WP\Functions as WPFunctions;

class BehavioralSignalsTest extends \MailPoetUnitTest {
  private function makeTestee(?callable $looksHumanFilter = null): BehavioralSignals {
    $wp = Stub::make(
      WPFunctions::class,
      [
        'applyFilters' => function ($filter, $value, ...$args) use ($looksHumanFilter) {
          if ($filter === 'mailpoet_behavioral_signals_looks_human' && $looksHumanFilter !== null) {
            return $looksHumanFilter($value, ...$args);
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

  public function testLooksHumanFilterCanOverrideToTrue() {
    // Allows test environments and integration code to short-circuit the
    // baseline check (e.g. WPLoader scenarios that subscribe without signals).
    $testee = $this->makeTestee(function () {
      return true;
    });
    verify($testee->looksHuman([]))->true();
  }

  public function testLooksHumanFilterReceivesSignalsAndData() {
    $data = ['behavioral_signals' => [
      'time_ms' => 3000,
      'mm_count' => 25,
      'focus_count' => 1,
      'touch' => false,
    ]];
    $invocations = 0;
    $testee = $this->makeTestee(function ($value, $signals, $contextData) use ($data, &$invocations) {
      $invocations++;
      verify($value)->true();
      verify($signals)->equals($data['behavioral_signals']);
      verify($contextData)->equals($data);
      return $value;
    });
    $testee->looksHuman($data);
    verify($invocations)->equals(1);
  }

  public function testLooksHumanFilterCanOverrideToFalse() {
    $testee = $this->makeTestee(function () {
      return false;
    });
    $data = ['behavioral_signals' => [
      'time_ms' => 5000,
      'mm_count' => 50,
      'kd_count' => 10,
      'focus_count' => 2,
      'touch' => false,
    ]];
    verify($testee->looksHuman($data))->false();
  }
}
