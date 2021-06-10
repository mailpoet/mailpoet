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

  private function getNewsletter(): NewsletterEntity {
    $newsletter = $this->newsletterRepository->findOneById($this->settings->get(TransactionalEmails::SETTING_EMAIL_ID));
    if (!$newsletter instanceof NewsletterEntity) {
      throw new InvalidStateException('WooCommerce email template is missing!');
    }
    return $newsletter;
  }

  public function overrideStylesForWooEmails() {
    $this->wp->addAction('option_woocommerce_email_background_color', function($value) {
      $newsletter = $this->getNewsletter();
      return $newsletter->getGlobalStyle('body', 'backgroundColor') ?? $value;
    });
    $this->wp->addAction('option_woocommerce_email_base_color', function($value) {
      $newsletter = $this->getNewsletter();
      return $newsletter->getGlobalStyle('woocommerce', 'brandingColor') ?? $value;
    });
    $this->wp->addAction('option_woocommerce_email_body_background_color', function($value) {
      $newsletter = $this->getNewsletter();
      return $newsletter->getGlobalStyle('wrapper', 'backgroundColor') ?? $value;
    });
    $this->wp->addAction('option_woocommerce_email_text_color', function($value) {
      $newsletter = $this->getNewsletter();
      return $newsletter->getGlobalStyle('text', 'fontColor') ?? $value;
    });
  }
}
