<?php

namespace MailPoet\WooCommerce;

use MailPoet\Features\FeaturesController;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class Settings {

  /** @var FeaturesController */
  private $features_controller;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  function __construct(
    FeaturesController $features_controller,
    SettingsController $settings,
    WPFunctions $wp
  ) {
    $this->features_controller = $features_controller;
    $this->settings = $settings;
    $this->wp = $wp;
  }

  function disableWooCommerceSettings() {
    if ($_GET['tab'] !== 'email') {
      return;
    }
    if (!$this->features_controller->isSupported(FeaturesController::WC_TRANSACTIONAL_EMAILS_CUSTOMIZER)) {
      return;
    }
    if (!(bool)$this->settings->get('woocommerce.use_mailpoet_editor')) {
      return;
    }
    $woocommerce_template_id = $this->settings->get('woocommerce.transactional_email_id');
    ?>

    <style>
      /* Hide WooCommerce section with template styling */
      #email_template_options-description + .form-table {
        opacity: 0.2;
        pointer-events: none;
      }

      /* Position MailPoet buttons over hidden table */
      .mailpoet-woocommerce-email-overlay {
        bottom: 320px;
        left: 0;
        max-width: 100%;
        text-align: left;
        position: absolute;
        text-align: center;
        width: 640px;
        z-index: 1;
      }
    </style>

    <div class="mailpoet-woocommerce-email-overlay">
      <a class="button button-primary" href="?page=mailpoet-newsletter-editor&id=<?php echo $woocommerce_template_id; ?>">
        <?php echo $this->wp->_x('Customize with MailPoet', 'Button in WooCommerce settings page'); ?>
      </a>
      <br>
      <br>
      <a href="?page=mailpoet-settings#woocommerce">
        <?php echo $this->wp->_x('Disable MailPoet customizer', 'Link from WooCommerce plugin to MailPoet'); ?>
      </a>
    </div>

    <?php
  }
}
