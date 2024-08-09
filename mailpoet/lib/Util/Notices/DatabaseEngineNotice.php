<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WP\Notice;

class DatabaseEngineNotice {
  const OPTION_NAME = 'database-engine-notice';
  const DISMISS_NOTICE_TIMEOUT_SECONDS = 15_552_000; // 6 months

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function init($shouldDisplay): ?Notice {
    if (!$shouldDisplay || $this->wp->getTransient(self::OPTION_NAME)) {
      return null;
    }

    return $this->display();
  }

  private function display(): ?Notice {
    return null;
  }

  public function disable() {
    $this->wp->setTransient(self::OPTION_NAME, true, self::DISMISS_NOTICE_TIMEOUT_SECONDS);
  }
}
