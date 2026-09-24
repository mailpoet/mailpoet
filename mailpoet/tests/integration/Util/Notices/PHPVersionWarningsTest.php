<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use Codeception\Stub\Expected;
use Codeception\Util\Stub;
use MailPoet\WP\Functions as WPFunctions;

class PHPVersionWarningsTest extends \MailPoetTest {
  /** @var string */
  private $originalDateFormat;

  public function _before() {
    parent::_before();
    delete_transient(PHPVersionWarnings::OPTION_NAME);
    $dateFormat = get_option('date_format');
    $this->originalDateFormat = is_string($dateFormat) ? $dateFormat : '';
  }

  public function _after() {
    parent::_after();
    delete_transient(PHPVersionWarnings::OPTION_NAME);
    update_option('date_format', $this->originalDateFormat);
  }

  private function warningsAt(int $timestamp): PHPVersionWarnings {
    $wp = Stub::make(new WPFunctions, [
      'currentTime' => function () use ($timestamp) {
        return $timestamp;
      },
    ], $this);
    return new PHPVersionWarnings($wp);
  }

  public function testItShowsDatedMessageBelowRequiredVersion() {
    update_option('date_format', 'F j, Y');
    $ts = strtotime('2026-09-24 00:00:00 UTC');
    $warnings = $this->warningsAt($ts);

    foreach (['7.4.33', '8.0.30'] as $version) {
      $message = $warnings->getMessage($version);
      verify($message)->stringContainsString('Your website is running PHP ' . $version);
      verify($message)->stringContainsString('Starting February 23, 2027');
      verify($message)->stringContainsString('require PHP 8.1 or newer');
      verify($message)->stringContainsString('won’t receive updates');
      verify($message)->stringContainsString('We recommend PHP 8.5');
      verify($message)->stringContainsString('https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version');
      verify($message)->stringNotContainsString('Since');
    }
  }

  public function testItSwitchesToSinceWordingAtCutoff() {
    update_option('date_format', 'F j, Y');
    $beforeCutoff = strtotime('2027-02-22 23:59:59 UTC');
    $message = $this->warningsAt($beforeCutoff)->getMessage('7.4.33');
    verify($message)->stringContainsString('Starting February 23, 2027');

    $atCutoff = strtotime('2027-02-23 00:00:00 UTC');
    $message = $this->warningsAt($atCutoff)->getMessage('7.4.33');
    verify($message)->stringContainsString('Since February 23, 2027');
    verify($message)->stringContainsString('no longer receives MailPoet updates');
  }

  public function testItUsesTheSiteDateFormat() {
    update_option('date_format', 'Y-m-d');
    $ts = strtotime('2026-09-24 00:00:00 UTC');
    $message = $this->warningsAt($ts)->getMessage('7.4.33');
    verify($message)->stringContainsString('Starting 2027-02-23');
  }

  public function testItFallsBackToDefaultFormatWhenDateFormatIsEmpty() {
    update_option('date_format', '');
    $ts = strtotime('2026-09-24 00:00:00 UTC');
    $message = $this->warningsAt($ts)->getMessage('7.4.33');
    verify($message)->stringContainsString('Starting February 23, 2027');
  }

  public function testItShowsUndatedMessageForPHP81() {
    $warnings = $this->warningsAt(strtotime('2026-09-24 00:00:00 UTC'));
    $message = $warnings->getMessage('8.1.0');
    verify($message)->stringContainsString('outdated version of PHP (8.1.0)');
    verify($message)->stringContainsString('upgrading to 8.5 or greater');
    verify($message)->stringNotContainsString('February');
  }

  public function testItGatesPHP82NoticeByDate() {
    foreach (['8.2.0', '8.2.20-1ubuntu1', '8.2.0RC1'] as $version) {
      $beforeGate = $this->warningsAt(strtotime('2026-12-31 23:59:59 UTC'));
      verify($beforeGate->getMessage($version))->null();

      $atGate = $this->warningsAt(strtotime('2027-01-01 00:00:00 UTC'));
      $message = $atGate->getMessage($version);
      verify($message)->stringContainsString('outdated version of PHP (' . $version . ')');
    }
  }

  public function testItShowsNoNoticeForPHP83AndNewer() {
    $warnings = $this->warningsAt(strtotime('2026-09-24 00:00:00 UTC'));
    foreach (['8.3.0', '8.3.0RC1', '8.5.1'] as $version) {
      verify($warnings->getMessage($version))->null();
      verify($warnings->init($version, true))->null();
    }
  }

  public function testInitReturnsNullWhenNotDisplayed() {
    $warnings = $this->warningsAt(strtotime('2026-09-24 00:00:00 UTC'));
    verify($warnings->init('7.4.33', false))->null();
  }

  public function testDisableStoresCurrentTimestamp() {
    $ts = strtotime('2026-09-24 00:00:00 UTC');
    $wp = Stub::make(new WPFunctions, [
      'currentTime' => function () use ($ts) {
        return $ts;
      },
      'setTransient' => Expected::once(function ($name, $value, $expiration) use ($ts) {
        verify($name)->equals(PHPVersionWarnings::OPTION_NAME);
        verify($value)->equals($ts);
        verify($expiration)->equals(PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);
        return true;
      }),
    ], $this);
    $warnings = new PHPVersionWarnings($wp);
    $warnings->disable();
  }

  public function testDismissalWindowIsThirtyDaysWellBeforeCutoffOn74() {
    $dismissedAt = strtotime('2026-09-24 00:00:00 UTC');
    set_transient(PHPVersionWarnings::OPTION_NAME, $dismissedAt, PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);

    $stillHidden = strtotime('2026-10-23 23:59:59 UTC');
    verify($this->warningsAt($stillHidden)->init('7.4.33', true))->null();

    $shownAgain = strtotime('2026-10-24 00:00:00 UTC');
    verify($this->warningsAt($shownAgain)->init('7.4.33', true))->notNull();
  }

  public function testDismissalWindowShortensNearCutoffOn74() {
    $dismissedAt = strtotime('2026-12-22 23:59:59 UTC');
    set_transient(PHPVersionWarnings::OPTION_NAME, $dismissedAt, PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);

    $shownAgain = strtotime('2026-12-30 00:00:00 UTC');
    verify($this->warningsAt($shownAgain)->init('7.4.33', true))->notNull();
  }

  public function testDismissalWindowIsSevenDaysAtCutoffThresholdOn74() {
    $dismissedAt = strtotime('2026-12-23 00:00:00 UTC');
    set_transient(PHPVersionWarnings::OPTION_NAME, $dismissedAt, PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);

    $stillHidden = strtotime('2026-12-29 23:59:59 UTC');
    verify($this->warningsAt($stillHidden)->init('7.4.33', true))->null();

    $shownAgain = strtotime('2026-12-30 00:00:00 UTC');
    verify($this->warningsAt($shownAgain)->init('7.4.33', true))->notNull();
  }

  public function testDismissalWindowRevertsToThirtyDaysAfterCutoffOn74() {
    // Dismissed while the short window is in effect, but the window is evaluated
    // against the read-time clock, not the dismissal-time clock. Once the read
    // time crosses the cutoff, the 30-day window applies again, so a dismissal
    // made just before the cutoff is not treated as expired after only 7 days.
    $dismissedAt = strtotime('2027-02-22 12:00:00 UTC');
    set_transient(PHPVersionWarnings::OPTION_NAME, $dismissedAt, PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);

    $stillHidden = strtotime('2027-03-01 12:00:00 UTC');
    verify($this->warningsAt($stillHidden)->init('7.4.33', true))->null();

    $shownAgain = strtotime('2027-03-24 12:00:01 UTC');
    verify($this->warningsAt($shownAgain)->init('7.4.33', true))->notNull();
  }

  public function testDismissalWindowIsThirtyDaysAfterCutoffOn74() {
    $dismissedAt = strtotime('2027-03-01 00:00:00 UTC');
    set_transient(PHPVersionWarnings::OPTION_NAME, $dismissedAt, PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);

    $stillHidden = strtotime('2027-03-20 00:00:00 UTC');
    verify($this->warningsAt($stillHidden)->init('7.4.33', true))->null();

    $shownAgain = strtotime('2027-03-31 00:00:01 UTC');
    verify($this->warningsAt($shownAgain)->init('7.4.33', true))->notNull();
  }

  public function testDismissalWindowStaysThirtyDaysOnUndatedNoticeNearCutoff() {
    $dismissedAt = strtotime('2026-12-23 00:00:00 UTC');
    set_transient(PHPVersionWarnings::OPTION_NAME, $dismissedAt, PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);

    $stillHidden = strtotime('2027-01-21 00:00:00 UTC');
    verify($this->warningsAt($stillHidden)->init('8.1.0', true))->null();
  }

  public function testNoticeIsShownWhenNoDismissalStored() {
    $warnings = $this->warningsAt(strtotime('2026-09-24 00:00:00 UTC'));
    verify($warnings->init('7.4.33', true))->notNull();
  }

  public function testNoticeIsShownForLegacyBooleanDismissal() {
    set_transient(PHPVersionWarnings::OPTION_NAME, true, PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);
    $warnings = $this->warningsAt(strtotime('2026-09-24 00:00:00 UTC'));
    verify($warnings->init('7.4.33', true))->notNull();
  }

  public function testDismissalRoundTripsThroughRealTransientStorage() {
    $clock = strtotime('2026-09-24 00:00:00 UTC');

    verify($this->warningsAt($clock)->init('7.4.33', true))->notNull();

    $this->warningsAt($clock)->disable();
    verify($this->warningsAt($clock)->init('7.4.33', true))->null();

    $justAfterWindow = $clock + PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS + 1;
    verify($this->warningsAt($justAfterWindow)->init('7.4.33', true))->notNull();
  }

  public function testNoticeIsHiddenForRecentNumericStringDismissal() {
    $ts = strtotime('2026-09-24 00:00:00 UTC');
    set_transient(PHPVersionWarnings::OPTION_NAME, (string)($ts - 100), PHPVersionWarnings::DISMISS_NOTICE_TIMEOUT_SECONDS);
    $warnings = $this->warningsAt($ts);
    verify($warnings->init('7.4.33', true))->null();
  }
}
