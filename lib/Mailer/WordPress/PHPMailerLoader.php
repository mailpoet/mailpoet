<?php

namespace MailPoet\Mailer\WordPress;

/**
 * This class and its usage should be removed
 * in favor of the new loading method when support
 * for WordPress below 5.5 is dropped.
 */

class PHPMailerLoader {
  /**
   * Conditionally load PHPMailer the old or the new way
   * to not get a deprecated file notice.
   */
  public static function load() {
    if (class_exists('PHPMailer')) {
      return false;
    }
    if (is_readable(ABSPATH . WPINC . '/PHPMailer/PHPMailer.php')) {
      // WordPress 5.5+
      require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
      require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
      class_alias(\PHPMailer\PHPMailer\PHPMailer::class, 'PHPMailer');
      class_alias(\PHPMailer\PHPMailer\Exception::class, 'phpmailerException');
    } else {
      // WordPress < 5.5
      require_once ABSPATH . WPINC . '/class-phpmailer.php';
    }
  }
}
