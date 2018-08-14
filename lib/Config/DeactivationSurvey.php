<?php

namespace MailPoet\Config;

use MailPoet\WP\Notice;

class DeactivationSurvey {

  /** @var Renderer */
  private $renderer;

  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  public function init() {
    add_action('admin_print_scripts', array($this, 'js'), 20);
    add_action('admin_print_scripts', array($this, 'css'));
    add_action('admin_footer', array($this, 'modal'));
  }

  private function shouldShow() {
    if(!function_exists('get_current_screen')) {
      return false;
    }
    $screen = get_current_screen();
    if(!is_object($screen)) {
      return false;
    }
    return (in_array(get_current_screen()->id, array('plugins', 'plugins-network'), true));
  }

  public function js() {
    if(!$this->shouldShow()) {
      return;
    }
    $this->render('deactivationSurvey/js.html');

  }

  public function css() {
    if(!$this->shouldShow()) {
      return;
    }
    $this->render('deactivationSurvey/css.html');
  }

  public function modal() {
    if(!$this->shouldShow()) {
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
