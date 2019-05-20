<?php
namespace MailPoet\WP;

use MailPoet\WP\Functions as WPFunctions;

class Notice {

  const TYPE_ERROR = 'error';
  const TYPE_WARNING = 'warning';
  const TYPE_SUCCESS = 'success';
  const TYPE_INFO = 'info';

  private $type;
  private $message;
  private $classes;
  private $data_notice_name;
  private $render_in_paragraph;

  function __construct($type, $message, $classes = '', $data_notice_name = '', $render_in_paragraph = true) {
    $this->type = $type;
    $this->message = $message;
    $this->classes = $classes;
    $this->data_notice_name = $data_notice_name;
    $this->render_in_paragraph = $render_in_paragraph;
  }

  static function displayError($message, $classes = '', $data_notice_name = '', $render_in_paragraph = true, $show_error_title = true) {
    if ($show_error_title) {
      $message = sprintf(
        "<b>%s </b> %s",
        WPFunctions::get()->__('MailPoet Error:', 'mailpoet'),
        $message
      );
    }
    self::createNotice(self::TYPE_ERROR, $message, $classes, $data_notice_name, $render_in_paragraph);
  }

  static function displayWarning($message, $classes = '', $data_notice_name = '', $render_in_paragraph = true) {
    self::createNotice(self::TYPE_WARNING, $message, $classes, $data_notice_name, $render_in_paragraph);
  }

  static function displaySuccess($message, $classes = '', $data_notice_name = '', $render_in_paragraph = true) {
    self::createNotice(self::TYPE_SUCCESS, $message, $classes, $data_notice_name, $render_in_paragraph);
  }

  static function displayInfo($message, $classes = '', $data_notice_name = '', $render_in_paragraph = true) {
    self::createNotice(self::TYPE_INFO, $message, $classes, $data_notice_name, $render_in_paragraph);
  }

  protected static function createNotice($type, $message, $classes, $data_notice_name, $render_in_paragraph) {
    $notice = new Notice($type, $message, $classes, $data_notice_name, $render_in_paragraph);
    WPFunctions::get()->addAction('admin_notices', [$notice, 'displayWPNotice']);
  }

  function displayWPNotice() {
    $class = sprintf('notice notice-%s mailpoet_notice_server %s', $this->type, $this->classes);
    $message = nl2br($this->message);
    $data_notice_name = !empty($this->data_notice_name) ? sprintf('data-notice="%s"', $this->data_notice_name) : '';

    if ($this->render_in_paragraph) {
      printf('<div class="%1$s" %3$s><p>%2$s</p></div>', $class, $message, $data_notice_name);
    } else {
      printf('<div class="%1$s" %3$s>%2$s</div>', $class, $message, $data_notice_name);
    }
  }
}
