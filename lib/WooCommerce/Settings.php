<?php

namespace MailPoet\WooCommerce;

use MailPoet\Settings\SettingsController;

class Settings {

  /** @var SettingsController */
  private $settings;

  function __construct(SettingsController $settings) {
    $this->settings = $settings;
  }

  function disableWooCommerceSettings() {
    if ($_GET['tab'] !== 'email') {
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
        <?php echo _x('Customize with MailPoet', 'Button in WooCommerce settings page'); ?>
      </a>
      <br>
      <br>
      <a href="?page=mailpoet-settings#woocommerce">
        <?php echo _x('Disable MailPoet customizer', ''); ?>
      </a>
    </div>

    <?php
  }
}
