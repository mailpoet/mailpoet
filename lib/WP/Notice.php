<?php
namespace MailPoet\WP;

class Notice {

  const TYPE_ERROR = 'error';
  const TYPE_WARNING = 'warning';
  const TYPE_SUCCESS = 'success';
  const TYPE_INFO = 'info';

  private $type;
  private $message;

  function __construct($type, $message) {
    $this->type = $type;
    $this->message = $message;
  }

  static function displayError($message) {
    $message = sprintf(
      "<b>%s </b> %s",
      __('MailPoet Error:', 'mailpoet'),
      $message
    );
    self::createNotice(self::TYPE_ERROR, $message);
  }

  static function displayWarning($message) {
    self::createNotice(self::TYPE_WARNING, $message);
  }

  static function displaySuccess($message) {
    self::createNotice(self::TYPE_SUCCESS, $message);
  }

  static function displayInfo($message) {
    self::createNotice(self::TYPE_INFO, $message);
  }

  protected static function createNotice($type, $message) {
    $notice = new Notice($type, $message);
    add_action('admin_notices', array($notice, 'displayWPNotice'));
  }

  function displayWPNotice() {
    $class = sprintf('notice notice-%s mailpoet_notice_server', $this->type);
    $message = nl2br($this->message);

    printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
  }
}
