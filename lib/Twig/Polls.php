<?php

namespace MailPoet\Twig;

use MailPoet\Settings\SettingsController;
use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFunction;

if (!defined('ABSPATH')) exit;

class Polls extends AbstractExtension {

  /** @var SettingsController */
  private $settings;

  public function __construct() {
    $this->settings = new SettingsController();
  }

  public function getFunctions() {
    return array(
      new TwigFunction(
        'get_polls_data',
        array($this, 'getPollsData'),
        array('is_safe' => array('all'))
      ),
      new TwigFunction(
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
