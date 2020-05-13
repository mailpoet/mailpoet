<?php

namespace MailPoet\WooCommerce;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\NewslettersRepository;
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

  /** @var Helper */
  private $woocommerceHelper;

  /** @var Renderer */
  private $renderer;

  /** @var array */
  private $emailHeadings;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  public function __construct(WPFunctions $wp, SettingsController $settings, Template $template, Renderer $renderer, Helper $woocommerceHelper, NewslettersRepository $newslettersRepository) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->template = $template;
    $this->renderer = $renderer;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->newslettersRepository = $newslettersRepository;
    $this->emailHeadings = [
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
    $savedEmailId = (bool)$this->settings->get(self::SETTING_EMAIL_ID, false);
    if (!$savedEmailId) {
      $email = $this->createNewsletter();
      $this->settings->set(self::SETTING_EMAIL_ID, $email->getId());
    }
  }

  public function getEmailHeadings() {
    $values = [];
    foreach ($this->emailHeadings as $name => $heading) {
      $settings = $this->wp->getOption($heading['option_name']);
      if (!$settings) {
        $values[$name] = $this->replacePlaceholders($heading['default']);
      } else {
        $value = !empty($settings['heading']) ? $settings['heading'] : $heading['default'];
        $values[$name] = $this->replacePlaceholders($value);
      }
    }
    return $values;
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
        $this->renderer->render($this->getNewsletter());
        echo $this->renderer->getHTMLBeforeContent($emailHeading);
      });
      $this->wp->addAction('woocommerce_email_footer', function() {
        echo $this->renderer->getHTMLAfterContent();
      });
      $this->wp->addAction('woocommerce_email_styles', [$this->renderer, 'prefixCss']);
    });
  }

  private function createNewsletter() {
    $wcEmailSettings = $this->getWCEmailSettings();
    $newsletter = new NewsletterEntity;
    $newsletter->setType(NewsletterEntity::TYPE_WC_TRANSACTIONAL_EMAIL);
    $newsletter->setSubject('WooCommerce Transactional Email');
    $newsletter->setBody($this->template->create($wcEmailSettings));
    $this->newslettersRepository->persist($newsletter);
    $this->newslettersRepository->flush();
    return $newsletter;
  }

  private function getNewsletter() {
    return Newsletter::findOne($this->settings->get(self::SETTING_EMAIL_ID));
  }

  private function replacePlaceholders($text) {
    $title = $this->wp->wpSpecialcharsDecode($this->wp->getOption('blogname'), ENT_QUOTES);
    $address = $this->wp->wpParseUrl($this->wp->homeUrl(), PHP_URL_HOST);
    $orderDate = date('Y-m-d');
    return str_replace(
      ['{site_title}','{site_address}', '{order_date}', '{order_number}'],
      [$title, $address, $orderDate, '0001'],
      $text
    );
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

  public function getWCEmailSettings() {
    $wcEmailSettings = [
      'woocommerce_email_background_color' => '#f7f7f7',
      'woocommerce_email_base_color' => '#333333',
      'woocommerce_email_body_background_color' => '#ffffff',
      'woocommerce_email_footer_text' => $this->wp->_x('Footer text', 'Default footer text for a WooCommerce transactional email', 'mailpoet'),
      'woocommerce_email_header_image' => Env::$assetsUrl . '/img/newsletter_editor/wc-default-logo.png',
      'woocommerce_email_text_color' => '#111111',
    ];
    $result = [];
    foreach ($wcEmailSettings as $name => $default) {
      $value = $this->wp->getOption($name);
      $key = preg_replace('/^woocommerce_email_/', '', $name);
      $result[$key] = $value ?: $default;
    }
    $result['base_text_color'] = $this->woocommerceHelper->wcLightOrDark($result['base_color'], '#202020', '#ffffff');
    if ($this->woocommerceHelper->wcHexIsLight($result['body_background_color'])) {
      $result['link_color'] = $this->woocommerceHelper->wcHexIsLight($result['base_color']) ? $result['base_text_color'] : $result['base_color'];
    } else {
      $result['link_color'] = $this->woocommerceHelper->wcHexIsLight($result['base_color']) ? $result['base_color'] : $result['base_text_color'];
    }
    $result['footer_text'] = $this->replacePlaceholders($result['footer_text']);
    // The footer text is placed inside a paragraph in a text block so we keep only tags we allow in the text block in the newsletter editor
    $result['footer_text'] = strip_tags($result['footer_text'], '<em><strong><br><a><span><s><del>');
    return $result;
  }
}
