<?php

namespace MailPoet\Twig;

use MailPoet\Settings\SettingsController;

if(!defined('ABSPATH')) exit;

class Polls extends \Twig_Extension {

  /** @var SettingsController */
  private $settings;

  public function __construct() {
    $this->settings = new SettingsController();
  }

  public function getFunctions() {
    return array(
      new \Twig_SimpleFunction(
        'get_polls_data',
        array($this, 'getPollsData'),
        array('is_safe' => array('all'))
      ),
      new \Twig_SimpleFunction(
        'get_polls_visiblity',
        array($this, 'getPollsVisibility'),
        array('is_safe' => array('all'))
      ),
    );
  }

  function getPollsData() {
    return [
      'mta_method' => $this->settings->get('mta.method'),
    ];
  }

  function getPollsVisibility() {
    return [
      'show_poll_success_delivery_preview' => $this->settings->get('show_poll_success_delivery_preview'),
    ];
  }
}
