<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Util\Notices;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class PHPVersionWarnings {

  const OPTION_NAME = 'dismissed-php-version-outdated-notice';

  const DISMISS_NOTICE_TIMEOUT_SECONDS = 2592000; // 30 days
  const SHORT_DISMISS_NOTICE_TIMEOUT_SECONDS = 604800; // 7 days

  const REQUIRED_VERSION = '8.1';
  const RECOMMENDED_VERSION = '8.5';
  const REQUIRED_VERSION_CUTOFF = '2027-02-23 00:00:00 UTC';
  const SHORT_DISMISS_BEFORE_CUTOFF = '-2 months';

  // Versions at or above REQUIRED_VERSION that still get the undated "outdated" notice.
  // null means the notice always applies, a date means it applies from then. Review this
  // list after each new PHP release.
  const OUTDATED_VERSIONS = [
    '8.1' => null,
    '8.2' => '2027-01-01 00:00:00 UTC',
  ];

  const UPGRADE_GUIDE_URL = 'https://kb.mailpoet.com/article/251-upgrading-the-websites-php-version';

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ?WPFunctions $wp = null
  ) {
    $this->wp = $wp ?? WPFunctions::get();
  }

  public function init($phpVersion, $shouldDisplay) {
    if (!$shouldDisplay) {
      return null;
    }

    $message = $this->getMessage($phpVersion);
    if ($message === null) {
      return null;
    }

    if ($this->isDismissed($phpVersion)) {
      return null;
    }

    return Notice::displayWarning($message, 'mailpoet-dismissible-notice is-dismissible', self::OPTION_NAME);
  }

  public function getMessage(string $phpVersion): ?string {
    if ($this->isBelowRequired($phpVersion)) {
      return $this->getBelowRequiredMessage($phpVersion);
    }

    $band = $this->band($phpVersion);
    if (!array_key_exists($band, self::OUTDATED_VERSIONS)) {
      return null;
    }

    $from = self::OUTDATED_VERSIONS[$band];
    if ($from !== null && $this->now() < (int)strtotime($from)) {
      return null;
    }

    // translators: %1$s is the PHP version the site is running, %2$s is the recommended PHP version
    $text = __('Your website is running an outdated version of PHP (%1$s), on which MailPoet might stop working in the future. We recommend upgrading to %2$s or greater. Read our [link]simple PHP upgrade guide.[/link]', 'mailpoet');
    $text = sprintf($text, $phpVersion, self::RECOMMENDED_VERSION);
    return $this->withLink($text);
  }

  private function getBelowRequiredMessage(string $phpVersion): string {
    $dateFormat = (string)$this->wp->getOption('date_format');
    if ($dateFormat === '') {
      $dateFormat = 'F j, Y';
    }
    $date = (string)$this->wp->wpDate($dateFormat, $this->cutoff(), new \DateTimeZone('UTC'));

    if ($this->now() < $this->cutoff()) {
      // translators: %1$s is the PHP version the site is running, %2$s is the date the requirement takes effect, already translated and in the site’s date format (e.g. February 23, 2027), %3$s is the required PHP version, %4$s is the recommended PHP version
      $text = __('Your website is running PHP %1$s. Starting %2$s, new versions of MailPoet will require PHP %3$s or newer. MailPoet will keep working on this site, but it won’t receive updates, including security fixes. Ask your hosting provider to upgrade PHP. We recommend PHP %4$s. Read our [link]simple PHP upgrade guide.[/link]', 'mailpoet');
    } else {
      // translators: %1$s is the PHP version the site is running, %2$s is the date the requirement took effect, already translated and in the site’s date format (e.g. February 23, 2027), %3$s is the required PHP version, %4$s is the recommended PHP version
      $text = __('Your website is running PHP %1$s. Since %2$s, new versions of MailPoet require PHP %3$s or newer. This site no longer receives MailPoet updates, including security fixes. Ask your hosting provider to upgrade PHP. We recommend PHP %4$s. Read our [link]simple PHP upgrade guide.[/link]', 'mailpoet');
    }

    $text = sprintf($text, $phpVersion, $date, self::REQUIRED_VERSION, self::RECOMMENDED_VERSION);
    return $this->withLink($text);
  }

  private function withLink(string $text): string {
    return Helpers::replaceLinkTags($text, self::UPGRADE_GUIDE_URL, [
      'target' => '_blank',
    ]);
  }

  public function disable() {
    $this->wp->setTransient(self::OPTION_NAME, $this->now(), self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

  private function isDismissed(string $phpVersion): bool {
    $stored = $this->wp->getTransient(self::OPTION_NAME);
    $dismissedAt = (int)$stored;
    return $dismissedAt + $this->dismissWindow($phpVersion, $dismissedAt) > $this->now();
  }

  private function dismissWindow(string $phpVersion, int $dismissedAt): int {
    if ($this->isBelowRequired($phpVersion) && ($this->isInShortPeriod($dismissedAt) || $this->isInShortPeriod($this->now()))) {
      return self::SHORT_DISMISS_NOTICE_TIMEOUT_SECONDS;
    }
    return self::DISMISS_NOTICE_TIMEOUT_SECONDS;
  }

  private function isInShortPeriod(int $timestamp): bool {
    return $timestamp >= (int)strtotime(self::SHORT_DISMISS_BEFORE_CUTOFF, $this->cutoff()) && $timestamp < $this->cutoff();
  }

  private function now(): int {
    return (int)$this->wp->currentTime('timestamp', true);
  }

  private function cutoff(): int {
    return (int)strtotime(self::REQUIRED_VERSION_CUTOFF);
  }

  private function band(string $phpVersion): string {
    return implode('.', array_slice(explode('.', $phpVersion), 0, 2));
  }

  private function isBelowRequired(string $phpVersion): bool {
    return version_compare($this->band($phpVersion), self::REQUIRED_VERSION, '<');
  }
}
