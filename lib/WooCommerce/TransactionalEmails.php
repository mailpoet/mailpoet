<?php

namespace MailPoet\WooCommerce;

use MailPoet\Models\Newsletter;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\TransactionalEmails\Renderer;
use MailPoet\WooCommerce\TransactionalEmails\Template;
use MailPoet\WP\Functions as WPFunctions;

class TransactionalEmails {
  const SETTING_EMAIL_ID = 'woocommerce.transactional_email_id';

  /** @var WPFunctions */
  private $wp;

  /** @var SettingsController */
  private $settings;

  /** @var Template */
  private $template;

  /** @var Renderer */
  private $renderer;

  /** @var array */
  private $email_headings;

  function __construct(WPFunctions $wp, SettingsController $settings, Template $template, Renderer $renderer) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->template = $template;
    $this->renderer = $renderer;
    $this->email_headings = [
      'new_account' => [
        'option_name' => 'woocommerce_new_order_settings',
        'default' => __('New Order: #{order_number}', 'woocommerce'),
      ],
      'processing_order' => [
        'option_name' => 'woocommerce_customer_processing_order_settings',
        'default' => __('Thank you for your order', 'woocommerce'),
      ],
      'completed_order' => [
        'option_name' => 'woocommerce_customer_completed_order_settings',
        'default' => __('Thanks for shopping with us', 'woocommerce'),
      ],
      'customer_note' => [
        'option_name' => 'woocommerce_customer_note_settings',
        'default' => __('A note has been added to your order', 'woocommerce'),
      ],
    ];
  }

  public function init() {
    $saved_email_id = (bool)$this->settings->get(self::SETTING_EMAIL_ID, false);
    if (!$saved_email_id) {
      $email = $this->createNewsletter();
      $this->settings->set(self::SETTING_EMAIL_ID, $email->id);
    }
  }

  public function getEmailHeadings() {
    $values = [];
    foreach ($this->email_headings as $name => $heading) {
      $settings = $this->wp->getOption($heading['option_name']);
      if (!$settings) {
        $values[$name] = $this->replacePlaceholders($heading['default']);
      } else {
        $value = isset($settings['heading']) ? $settings['heading'] : $heading['default'];
        $values[$name] = $this->replacePlaceholders($value);
      }
    }
    return $values;
  }

  public function useTemplateForWoocommerceEmails() {
    $this->wp->addAction('woocommerce_init', function() {
      $this->wp->removeAction('woocommerce_email_header', [\WC()->mailer(), 'email_header']);
      $this->wp->removeAction('woocommerce_email_footer', [\WC()->mailer(), 'email_footer']);
      $this->wp->addAction('woocommerce_email_header', function($email_heading) {
        $this->renderer->render($this->getNewsletter());
        echo $this->renderer->getHTMLBeforeContent($email_heading);
      });
      $this->wp->addAction('woocommerce_email_footer', function() {
        echo $this->renderer->getHTMLAfterContent();
      });
      $this->wp->addAction('woocommerce_email_styles', [$this->renderer, 'prefixCss']);
    });
  }

  private function createNewsletter() {
    $wc_email_settings = $this->getWCEmailSettings();
    return Newsletter::createOrUpdate([
      'type' => Newsletter::TYPE_WC_TRANSACTIONAL_EMAIL,
      'subject' => 'WooCommerce Transactional Email',
      'preheader' => '',
      'body' => json_encode($this->template->create($wc_email_settings)),
    ]);
  }

  private function getNewsletter() {
    return Newsletter::findOne($this->settings->get(self::SETTING_EMAIL_ID));
  }

  private function replacePlaceholders($text) {
    $title = $this->wp->wpSpecialcharsDecode($this->wp->getOption('blogname'), ENT_QUOTES);
    $address = $this->wp->wpParseUrl($this->wp->homeUrl(), PHP_URL_HOST);
    $order_date = date('Y-m-d');
    return str_replace(
      ['{site_title}','{site_address}', '{order_date}', '{order_number}'],
      [$title, $address, $order_date, '0001'],
      $text
    );
  }

  private function getWCEmailSettings() {
    $wc_email_settings = [
      'woocommerce_email_background_color' => '#ffffff',
      'woocommerce_email_base_color' => '#333333',
      'woocommerce_email_body_background_color' => '#eeeeee',
      'woocommerce_email_footer_text' => $this->wp->_x('Footer text', 'Default footer text for a WooCommerce transactional email', 'mailpoet'),
      'woocommerce_email_header_image' => '',
      'woocommerce_email_text_color' => '#111111',
    ];
    $result = [];
    foreach ($wc_email_settings as $name => $default) {
      $value = $this->wp->getOption($name, $default);
      $key = preg_replace('/^woocommerce_email_/', '', $name);
      $result[$key] = $value;
    }
    $result['footer_text'] = $this->replacePlaceholders($result['footer_text']);
    return $result;
  }
}