<?php
namespace MailPoet\WP;

class Notice {

  protected function __construct($type, $message) {
    $this->type = $type;
    $this->message = $message;
  }

  static function displayError($message) {
    $message = sprintf(
      "<b>%s </b> %s",
      __('MailPoet Error:'),
      $message
    );
    self::createNotice('error', $message);
  }

  static function displayWarning($message) {
    self::createNotice('warning', $message);
  }

  static function displaySuccess($message) {
    self::createNotice('success', $message);
  }

  static function displayInfo($message) {
    self::createNotice('info', $message);
  }

  protected static function createNotice($type, $message) {
    $notice = new Notice($type, $message);
    add_action('admin_notices', array($notice, 'displayWPNotice'));
  }

  function displayWPNotice() {
    $class = sprintf('notice notice-%s', $this->type);
    $message = nl2br($this->message);

    printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
  }
}
