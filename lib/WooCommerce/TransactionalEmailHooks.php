<?php

namespace MailPoet\WooCommerce;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\InvalidStateException;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\NewslettersRepository;
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

  /** @var NewslettersRepository */
  private $newsletterRepository;

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    Renderer $renderer,
    NewslettersRepository $newsletterRepository
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->renderer = $renderer;
    $this->newsletterRepository = $newsletterRepository;
  }

  public function useTemplateForWoocommerceEmails() {
    $this->wp->addAction('woocommerce_email', function($wcEmails) {
      /** @var callable */
      $emailHeaderCallback = [$wcEmails, 'email_header'];
      /** @var callable */
      $emailFooterCallback = [$wcEmails, 'email_footer'];
      $this->wp->removeAction('woocommerce_email_header', $emailHeaderCallback);
      $this->wp->removeAction('woocommerce_email_footer', $emailFooterCallback);
      $this->wp->addAction('woocommerce_email_header', function($emailHeading) {
        $newsletterEntity = $this->getNewsletter();
        // Temporary load old model until we refactor renderer
        $newsletterModel = Newsletter::findOne($newsletterEntity->getId());
        if (!$newsletterModel instanceof Newsletter) {
          throw new InvalidStateException('WooCommerce email template is missing!');
        }
        $this->renderer->render($newsletterModel, $emailHeading);
        echo $this->renderer->getHTMLBeforeContent($emailHeading);
      });
      $this->wp->addAction('woocommerce_email_footer', function() {
        echo $this->renderer->getHTMLAfterContent();
      });
      $this->wp->addAction('woocommerce_email_styles', [$this->renderer, 'prefixCss']);
    });
  }

  public function enableEmailSettingsSyncToWooCommerce() {
    $this->wp->addFilter('mailpoet_api_newsletters_save_after', [$this, 'syncEmailSettingsToWooCommerce']);
  }

  private function getNewsletter(): NewsletterEntity {
    $newsletter = $this->newsletterRepository->findOneById($this->settings->get(TransactionalEmails::SETTING_EMAIL_ID));
    if (!$newsletter instanceof NewsletterEntity) {
      throw new InvalidStateException('WooCommerce email template is missing!');
    }
    return $newsletter;
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
