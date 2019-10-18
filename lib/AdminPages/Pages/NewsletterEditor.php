<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterEditor {
  /** @var PageRenderer */
  private $page_renderer;

  /** @var SettingsController */
  private $settings;

  /** @var UserFlagsController */
  private $user_flags;

  /** @var WooCommerceHelper */
  private $woocommerce_helper;

  /** @var WPFunctions */
  private $wp;

  /** @var TransactionalEmails */
  private $wc_transactional_emails;

  function __construct(
    PageRenderer $page_renderer,
    SettingsController $settings,
    UserFlagsController $user_flags,
    WooCommerceHelper $woocommerce_helper,
    WPFunctions $wp,
    TransactionalEmails $wc_transactional_emails
  ) {
    $this->page_renderer = $page_renderer;
    $this->settings = $settings;
    $this->user_flags = $user_flags;
    $this->woocommerce_helper = $woocommerce_helper;
    $this->wp = $wp;
    $this->wc_transactional_emails = $wc_transactional_emails;
  }

  function render() {
    $newsletter_id = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $woocommerce_template_id = (int)$this->settings->get('woocommerce.transactional_email_id', null);
    if (
      $woocommerce_template_id
      && $newsletter_id === $woocommerce_template_id
      && (
        !$this->woocommerce_helper->isWooCommerceActive()
        || !(bool)$this->settings->get('woocommerce.use_mailpoet_editor', false)
      )
    ) {
      header('Location: admin.php?page=mailpoet-settings&enable-customizer-notice#woocommerce', true, 302);
      exit;
    }

    $subscriber = Subscriber::getCurrentWPUser();
    $subscriber_data = $subscriber ? $subscriber->asArray() : [];
    $data = [
      'shortcodes' => ShortcodesHelper::getShortcodes(),
      'settings' => $this->settings->getAll(),
      'editor_tutorial_seen' => $this->user_flags->get('editor_tutorial_seen'),
      'current_wp_user' => array_merge($subscriber_data, $this->wp->wpGetCurrentUser()->to_array()),
      'sub_menu' => Menu::MAIN_PAGE_SLUG,
      'mss_active' => Bridge::isMPSendingServiceEnabled(),
      'woocommerce' => [
        'email_headings' => $this->wc_transactional_emails->getEmailHeadings(),
        'email_base_color' => $this->wp->getOption('woocommerce_email_base_color', '#000000'),
        'email_text_color' => $this->wp->getOption('woocommerce_email_text_color', '#000000'),
      ],
    ];
    $this->wp->wpEnqueueMedia();
    $this->wp->wpEnqueueStyle('editor', $this->wp->includesUrl('css/editor.css'));

    $this->page_renderer->displayPage('newsletter/editor.html', $data);
  }
}
