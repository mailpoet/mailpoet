<?php declare(strict_types = 1);

namespace MailPoet\Util\Notices;

use MailPoet\Doctrine\WPDB\Connection;
use MailPoet\WP\Notice;

class WordPressPlaygroundNotice {
  const OPTION_NAME = 'wordpress-playground-notice';

  public function init($shouldDisplay): ?Notice {
    if (!$shouldDisplay || !Connection::isSQLite()) {
      return null;
    }
    return $this->display();
  }

  private function display(): Notice {
    return Notice::displayWarning(
      sprintf(
        '<p><strong>%s</strong></p><p>%s</p>',
        __('MailPoet Preview', 'mailpoet'),
        __('This is a preview of the MailPoet plugin. Please note that some functionality may be limited.', 'mailpoet')
      ),
      null,
      self::OPTION_NAME,
      false
    );
  }
}
