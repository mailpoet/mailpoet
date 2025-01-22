<?php declare(strict_types = 1);

namespace MailPoet\AdminPages\Pages;

use MailPoet\Config\Env;
use MailPoet\WP\Functions as WPFunctions;

class EmailDataViews {
  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function render() {
    $assetsParams = require Env::$assetsPath . '/dist/js/email-editor/email_editor.asset.php';
    $this->wp->wpEnqueueScript(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/js/email-editor/email_editor.js',
      $assetsParams['dependencies'],
      $assetsParams['version'],
      true
    );
    $this->wp->wpEnqueueStyle(
      'mailpoet_email_editor',
      Env::$assetsUrl . '/dist/css/mailpoet-dataviews.css'
    );
    echo '<div id="mailpoet-emails-data-views"></div>';
  }
}
