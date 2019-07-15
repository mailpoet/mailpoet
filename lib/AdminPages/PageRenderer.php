<?php

namespace MailPoet\AdminPages;

use MailPoet\Config\Renderer;
use MailPoet\Features\FeaturesController;
use MailPoet\WP\Notice as WPNotice;

if (!defined('ABSPATH')) exit;

class PageRenderer {
  /** @var Renderer */
  private $renderer;

  /** @var FeaturesController */
  private $features_controller;

  function __construct(Renderer $renderer, FeaturesController $features_controller) {
    $this->renderer = $renderer;
    $this->features_controller = $features_controller;
  }

  /**
   * Set common data for template and display template
   * @param string $template
   * @param array $data
   */
  function displayPage($template, array $data = []) {
    $defaults = [
      'feature_flags' => $this->features_controller->getAllFlags(),
    ];
    try {
      echo $this->renderer->render($template, $data + $defaults);
    } catch (\Exception $e) {
      $notice = new WPNotice(WPNotice::TYPE_ERROR, $e->getMessage());
      $notice->displayWPNotice();
    }
  }
}
