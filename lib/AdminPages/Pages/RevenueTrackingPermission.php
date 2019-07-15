<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Features\FeaturesController;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class RevenueTrackingPermission {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var WPFunctions */
  private $wp;

  /** @var FeaturesController */
  private $features_controller;

  function __construct(
    PageRenderer $page_renderer,
    WPFunctions $wp,
    FeaturesController $features_controller
  ) {
    $this->page_renderer = $page_renderer;
    $this->wp = $wp;
    $this->features_controller = $features_controller;
  }

  function render() {
    if (!$this->features_controller->isSupported(FeaturesController::FEATURE_DISPLAY_WOOCOMMERCE_REVENUES)) {
      return;
    }
    if ((bool)(defined('DOING_AJAX') && DOING_AJAX)) return;
    $data = [
      'finish_wizard_url' => $this->wp->adminUrl('admin.php?page=' . Menu::MAIN_PAGE_SLUG),
    ];
    $this->page_renderer->displayPage('revenue_tracking_permission.html', $data);
  }
}
