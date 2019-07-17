<?php

namespace MailPoet\Config;

use MailPoet\WP\Functions as WPFunctions;

class DeactivationSurvey {

  /** @var Renderer */
  private $renderer;

  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  public function init() {
    WPFunctions::get()->addAction('admin_print_scripts', [$this, 'js'], 20);
    WPFunctions::get()->addAction('admin_print_scripts', [$this, 'css']);
    WPFunctions::get()->addAction('admin_footer', [$this, 'modal']);
  }

  private function shouldShow() {
    if (!function_exists('get_current_screen')) {
      return false;
    }
    $screen = WPFunctions::get()->getCurrentScreen();
    if (!is_object($screen)) {
      return false;
    }
    return (in_array(get_current_screen()->id, ['plugins', 'plugins-network'], true));
  }

  public function js() {
    if (!$this->shouldShow()) {
      return;
    }
    $this->render('deactivationSurvey/js.html');

  }

  public function css() {
    if (!$this->shouldShow()) {
      return;
    }
    $this->render('deactivationSurvey/css.html');
  }

  public function modal() {
    if (!$this->shouldShow()) {
      return;
    }
    $this->render('deactivationSurvey/index.html');
  }

  private function render($template) {
    try {
      echo $this->renderer->render($template);
    } catch (\Exception $e) {
      // if the website fails to render we have other places to catch and display the error
    }
  }

}
