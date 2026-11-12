<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class IISServerNotice {
  const OPTION_NAME = 'dismissed-iis-server-notice';
  const DISMISS_NOTICE_TIMEOUT_SECONDS = 15_552_000; // 6 months

  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function init($shouldDisplay): ?Notice {
    if (!$shouldDisplay || !$this->isIIS() || $this->wp->getTransient(self::OPTION_NAME)) {
      return null;
    }

    $message = __('MailPoet doesn’t officially support Microsoft IIS web servers. It may work, but we can’t help with problems specific to IIS. We recommend a web server powered by Apache or NGINX. [link]Read more about the requirements.[/link]', 'mailpoet');
    $message = Helpers::replaceLinkTags($message, 'https://kb.mailpoet.com/article/152-minimum-requirements-for-mailpoet-3', [
      'target' => '_blank',
    ]);

    return Notice::displayWarning($message, 'mailpoet-dismissible-notice is-dismissible', self::OPTION_NAME);
  }

  public function disable(): void {
    $this->wp->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }

  private function isIIS(): bool {
    $serverSoftware = isset($_SERVER['SERVER_SOFTWARE']) && is_string($_SERVER['SERVER_SOFTWARE'])
      ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']))
      : '';
    return stripos($serverSoftware, 'microsoft-iis') !== false;
  }
}
