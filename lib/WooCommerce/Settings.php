<?php

namespace MailPoet\WooCommerce;

use MailPoet\Config\Renderer;
use MailPoet\Features\FeaturesController;
use MailPoet\Settings\SettingsController;

class Settings {

  /** @var FeaturesController */
  private $features_controller;

  /** @var Renderer */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  function __construct(
    FeaturesController $features_controller,
    Renderer $renderer,
    SettingsController $settings
  ) {
    $this->features_controller = $features_controller;
    $this->renderer = $renderer;
    $this->settings = $settings;
  }

  function disableWooCommerceSettings() {
    if ($_GET['tab'] !== 'email' || $_GET['section']) {
      return;
    }
    if (!$this->features_controller->isSupported(FeaturesController::WC_TRANSACTIONAL_EMAILS_CUSTOMIZER)) {
      return;
    }
    if (!(bool)$this->settings->get('woocommerce.use_mailpoet_editor')) {
      return;
    }

    echo $this->renderer->render('woocommerce/settings_overlay.html', [
      'woocommerce_template_id' => $this->settings->get('woocommerce.transactional_email_id'),
    ]);
  }
}
