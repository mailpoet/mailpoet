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
    $formRenderStamp = Stub::make(
      FormRenderStamp::class,
      [
        'elapsedSeconds' => function ($stamp) {
          $elapsed = ['old' => 10, 'fresh' => 1];
          return is_string($stamp) ? ($elapsed[$stamp] ?? null) : null;
        },
      ],
      $this
    );
    return new BehavioralSignals($wp, $formRenderStamp);
  }

  /**
   * @param array<string,mixed> $signals
   * @return array<string,mixed>
   */
  private function payload(array $signals, ?string $stamp = 'old'): array {
    $data = ['behavioral_signals' => $signals];
    if ($stamp !== null) {
      $data['form_stamp'] = $stamp;
    }
    return $data;
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
    $data = $this->payload([
      'time_ms' => 500,
      'mm_count' => 50,
      'kd_count' => 10,
      'focus_count' => 2,
      'touch' => false,
    ]);
    verify($testee->looksHuman($data))->false();
  }

  public function testReturnsFalseWhenNoFieldFocus() {
    $testee = $this->makeTestee();
    $data = $this->payload([
      'time_ms' => 5000,
      'mm_count' => 50,
      'kd_count' => 10,
      'focus_count' => 0,
      'touch' => false,
    ]);
    verify($testee->looksHuman($data))->false();
  }

  public function testDesktopPassesWithMouseMovement() {
    $testee = $this->makeTestee();
    $data = $this->payload([
      'time_ms' => 3000,
      'mm_count' => 25,
      'kd_count' => 0,
      'focus_count' => 1,
      'touch' => false,
    ]);
    verify($testee->looksHuman($data))->true();
  }

  public function testDesktopPassesWithKeydownEvenIfNoMouseMovement() {
    // Password-manager autofill scenario: focus fires but no keystrokes from the user;
    // a typing user would still pass via kd_count.
    $testee = $this->makeTestee();
    $data = $this->payload([
      'time_ms' => 3000,
      'mm_count' => 0,
      'kd_count' => 15,
      'focus_count' => 2,
      'touch' => false,
    ]);
    verify($testee->looksHuman($data))->true();
  }

  public function testDesktopFailsWithoutMouseOrKeydown() {
    $testee = $this->makeTestee();
    $data = $this->payload([
      'time_ms' => 5000,
      'mm_count' => 0,
      'kd_count' => 0,
      'focus_count' => 1,
      'touch' => false,
    ]);
    verify($testee->looksHuman($data))->false();
  }

  public function testTouchPassesWithScrollOnly() {
    // Mobile user who scrolled but didn't type or move a mouse — still human.
    $testee = $this->makeTestee();
    $data = $this->payload([
      'time_ms' => 4000,
      'mm_count' => 0,
      'kd_count' => 0,
      'scroll_count' => 3,
      'focus_count' => 1,
      'touch' => true,
    ]);
    verify($testee->looksHuman($data))->true();
  }

  public function testTouchPassesWithKeydown() {
    $testee = $this->makeTestee();
    $data = $this->payload([
      'time_ms' => 4000,
      'mm_count' => 0,
      'kd_count' => 5,
      'scroll_count' => 0,
      'focus_count' => 1,
      'touch' => true,
    ]);
    verify($testee->looksHuman($data))->true();
  }

  public function testTouchFailsWithoutInteraction() {
    $testee = $this->makeTestee();
    $data = $this->payload([
      'time_ms' => 5000,
      'mm_count' => 100,
      'kd_count' => 0,
      'scroll_count' => 0,
      'focus_count' => 1,
      'touch' => true,
    ]);
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
    $data = $this->payload([
      'time_ms' => 3000,
      'mm_count' => 25,
      'focus_count' => 1,
      'touch' => false,
    ]);
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
    $data = $this->payload([
      'time_ms' => 5000,
      'mm_count' => 50,
      'kd_count' => 10,
      'focus_count' => 2,
      'touch' => false,
    ]);
    verify($testee->looksHuman($data))->false();
  }

  public function testReturnsFalseWhenStampMissing() {
    $testee = $this->makeTestee();
    $data = $this->payload(['time_ms' => 3000, 'mm_count' => 25, 'focus_count' => 1], null);
    verify($testee->looksHuman($data))->false();
  }

  public function testReturnsFalseWhenStampIsNotValid() {
    $testee = $this->makeTestee();
    $data = $this->payload(['time_ms' => 3000, 'mm_count' => 25, 'focus_count' => 1], 'tampered');
    verify($testee->looksHuman($data))->false();
  }

  public function testReturnsFalseWhenFormWasRenderedLessThanTwoSecondsAgo() {
    $testee = $this->makeTestee();
    $data = $this->payload(['time_ms' => 1000, 'mm_count' => 25, 'focus_count' => 1], 'fresh');
    verify($testee->looksHuman($data))->false();
    // A client claiming more time than the stamp allows is not trusted either.
    $data = $this->payload(['time_ms' => 3000, 'mm_count' => 25, 'focus_count' => 1], 'fresh');
    verify($testee->looksHuman($data))->false();
  }

  public function testClaimedTimeMayExceedElapsedTimeByTheSlackOnly() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 15000, 'mm_count' => 25, 'focus_count' => 1];
    verify($testee->looksHuman($this->payload($signals)))->true();
    $signals['time_ms'] = 15001;
    verify($testee->looksHuman($this->payload($signals)))->false();
  }

  public function testStampIsNotRequiredWhenWaived() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 3000, 'mm_count' => 25, 'focus_count' => 1];
    verify($testee->looksHuman($this->payload($signals, null), false))->true();
    verify($testee->looksHuman($this->payload($signals, 'tampered'), false))->true();
    verify($testee->looksHuman($this->payload(array_merge($signals, ['time_ms' => 900000]), 'fresh'), false))->true();
  }

  public function testWaivedStampStillRequiresPlausibleSignals() {
    $testee = $this->makeTestee();
    verify($testee->looksHuman($this->payload(['time_ms' => 500, 'mm_count' => 25, 'focus_count' => 1], null), false))->false();
    verify($testee->looksHuman($this->payload(['time_ms' => 3000, 'mm_count' => 0, 'kd_count' => 0, 'focus_count' => 1], null), false))->false();
    verify($testee->looksHuman($this->payload(['time_ms' => 3000, 'kd_count' => 5000, 'focus_count' => 1], null), false))->false();
  }

  public function testReturnsFalseForMalformedCounters() {
    $testee = $this->makeTestee();
    $valid = ['time_ms' => 3000, 'mm_count' => 25, 'kd_count' => 5, 'scroll_count' => 2, 'focus_count' => 1];
    verify($testee->looksHuman($this->payload($valid)))->true();
    foreach (['time_ms', 'mm_count', 'kd_count', 'scroll_count', 'focus_count'] as $key) {
      foreach (['1e1', -5, '-5', 1.5, '1.5', true, [], ' 5', '', PHP_INT_MAX, 100000001] as $malformed) {
        $signals = array_merge($valid, [$key => $malformed]);
        verify($testee->looksHuman($this->payload($signals, null), false))
          ->false(sprintf('%s = %s', $key, var_export($malformed, true)));
      }
    }
  }

  public function testAcceptsCountersSentAsDigitStrings() {
    $testee = $this->makeTestee();
    $data = $this->payload(['time_ms' => '3000', 'mm_count' => '25', 'kd_count' => '0', 'focus_count' => '1']);
    verify($testee->looksHuman($data))->true();
  }

  public function testReturnsFalseWhenKeystrokesAreFasterThanTwentyMillisecondsEach() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 3000, 'mm_count' => 0, 'kd_count' => 150, 'focus_count' => 1];
    verify($testee->looksHuman($this->payload($signals)))->true();
    $signals['kd_count'] = 151;
    verify($testee->looksHuman($this->payload($signals)))->false();
    $signals['kd_count'] = 5000;
    verify($testee->looksHuman($this->payload($signals)))->false();
  }

  public function testReturnsFalseWhenMouseMovesAreFasterThanTwoMillisecondsEach() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 3000, 'mm_count' => 1500, 'kd_count' => 5, 'focus_count' => 1];
    verify($testee->looksHuman($this->payload($signals)))->true();
    $signals['mm_count'] = 1501;
    verify($testee->looksHuman($this->payload($signals)))->false();
  }

  public function testReturnsFalseWhenFocusCountIsAbovePlausibleMaximum() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 3000, 'mm_count' => 25, 'focus_count' => 50];
    verify($testee->looksHuman($this->payload($signals)))->true();
    $signals['focus_count'] = 51;
    verify($testee->looksHuman($this->payload($signals)))->false();
  }

  public function testScrollingIsNotRateLimited() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 3000, 'mm_count' => 0, 'kd_count' => 0, 'scroll_count' => 450, 'focus_count' => 1, 'touch' => true];
    verify($testee->looksHuman($this->payload($signals)))->true();
  }

  public function testKeyboardOnlyVisitorPasses() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 9000, 'mm_count' => 0, 'kd_count' => 12, 'focus_count' => 1, 'touch' => false];
    verify($testee->looksHuman($this->payload($signals)))->true();
  }

  public function testScreenReaderVisitorWithFewKeystrokesIsAskedForCaptcha() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 9000, 'mm_count' => 0, 'kd_count' => 2, 'focus_count' => 2, 'touch' => false];
    verify($testee->looksHuman($this->payload($signals)))->false();
  }

  public function testSlowTyperPasses() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 10000, 'mm_count' => 0, 'kd_count' => 5, 'focus_count' => 1, 'touch' => false];
    verify($testee->looksHuman($this->payload($signals)))->true();
  }

  public function testPasteOnlyVisitorPassesWithMouseMovementAndIsAskedForCaptchaWithout() {
    $testee = $this->makeTestee();
    $signals = ['time_ms' => 9000, 'mm_count' => 3, 'kd_count' => 0, 'focus_count' => 1, 'touch' => false];
    verify($testee->looksHuman($this->payload($signals)))->true();
    $signals['mm_count'] = 0;
    verify($testee->looksHuman($this->payload($signals)))->false();
  }
}
