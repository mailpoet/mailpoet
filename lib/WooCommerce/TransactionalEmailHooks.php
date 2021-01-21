<?php

namespace MailPoet\WooCommerce;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\TransactionalEmails\Renderer;
use MailPoet\WP\Functions as WPFunctions;

class TransactionalEmailHooks {
  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var Renderer */
  private $renderer;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    Renderer $renderer
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->renderer = $renderer;
  }

  public function useTemplateForWoocommerceEmails() {
    $this->wp->addAction('woocommerce_init', function() {
      /** @var callable */
      $emailHeaderCallback = [\WC()->mailer(), 'email_header'];
      /** @var callable */
      $emailFooterCallback = [\WC()->mailer(), 'email_footer'];
      $this->wp->removeAction('woocommerce_email_header', $emailHeaderCallback);
      $this->wp->removeAction('woocommerce_email_footer', $emailFooterCallback);
      $this->wp->addAction('woocommerce_email_header', function($emailHeading) {
        $this->renderer->render($this->getNewsletter(), $emailHeading);
        echo $this->renderer->getHTMLBeforeContent($emailHeading);
      });
      $this->wp->addAction('woocommerce_email_footer', function() {
        echo $this->renderer->getHTMLAfterContent();
      });
      $this->wp->addAction('woocommerce_email_styles', [$this->renderer, 'prefixCss']);
    });
  }

  private function getNewsletter() {
    return Newsletter::findOne($this->settings->get(TransactionalEmails::SETTING_EMAIL_ID));
  }

  public function enableEmailSettingsSyncToWooCommerce() {
    $this->wp->addFilter('mailpoet_api_newsletters_save_after', [$this, 'syncEmailSettingsToWooCommerce']);
  }

  public function syncEmailSettingsToWooCommerce(array $newsletterData) {
    if ($newsletterData['type'] !== NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL) {
      return $newsletterData;
    }

    $styles = $newsletterData['body']['globalStyles'];
    $optionsToSync = [
      'woocommerce_email_background_color' => $styles['body']['backgroundColor'],
      'woocommerce_email_base_color' => $styles['woocommerce']['brandingColor'],
      'woocommerce_email_body_background_color' => $styles['wrapper']['backgroundColor'],
      'woocommerce_email_text_color' => $styles['text']['fontColor'],
    ];
    foreach ($optionsToSync as $wcName => $value) {
      $this->wp->updateOption($wcName, $value);
    }
    return $newsletterData;
  }
}
