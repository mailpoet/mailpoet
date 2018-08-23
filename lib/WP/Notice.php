<?php
namespace MailPoet\WP;

class Notice {

  const TYPE_ERROR = 'error';
  const TYPE_WARNING = 'warning';
  const TYPE_SUCCESS = 'success';
  const TYPE_INFO = 'info';

  private $type;
  private $message;

  function __construct($type, $message, $classes = '', $transient = '') {
    $this->type = $type;
    $this->message = $message;
    $this->classes = $classes;
    $this->transient = $transient;
  }

  static function displayError($message, $classes = '', $transient = '') {
    $message = sprintf(
      "<b>%s </b> %s",
      __('MailPoet Error:', 'mailpoet'),
      $message
    );
    self::createNotice(self::TYPE_ERROR, $message, $classes, $transient);
  }

  static function displayWarning($message, $classes = '', $transient = '') {
    self::createNotice(self::TYPE_WARNING, $message, $classes, $transient);
  }

  static function displaySuccess($message, $classes = '', $transient = '') {
    self::createNotice(self::TYPE_SUCCESS, $message, $classes, $transient);
  }

  static function displayInfo($message, $classes = '', $transient = '') {
    self::createNotice(self::TYPE_INFO, $message, $classes, $transient);
  }

  protected static function createNotice($type, $message, $classes, $transient) {
    $notice = new Notice($type, $message, $classes, $transient);
    add_action('admin_notices', array($notice, 'displayWPNotice'));
  }

  function displayWPNotice() {
    $class = sprintf('notice notice-%s mailpoet_notice_server %s', $this->type, $this->classes);
    $message = nl2br($this->message);
    $transient = !empty($this->transient) ? sprintf('data-notice="%s"', $this->transient) : '';

    printf('<div class="%1$s" %3$s><p>%2$s</p></div>', $class, $message, $transient);
  }
}
