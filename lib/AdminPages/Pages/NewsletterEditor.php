<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Config\ServicesChecker;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmails;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterEditor {
  private const DATE_FORMAT = 'Y-m-d H:i:s';

  /** @var PageRenderer */
  private $pageRenderer;

  /** @var SettingsController */
  private $settings;

  /** @var UserFlagsController */
  private $userFlags;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var TransactionalEmails */
  private $wcTransactionalEmails;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var ShortcodesHelper */
  private $shortcodesHelper;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settings,
    UserFlagsController $userFlags,
    WooCommerceHelper $woocommerceHelper,
    WPFunctions $wp,
    TransactionalEmails $wcTransactionalEmails,
    ShortcodesHelper $shortcodesHelper,
    ServicesChecker $servicesChecker,
    SubscribersRepository $subscribersRepository
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settings = $settings;
    $this->userFlags = $userFlags;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wp = $wp;
    $this->wcTransactionalEmails = $wcTransactionalEmails;
    $this->servicesChecker = $servicesChecker;
    $this->shortcodesHelper = $shortcodesHelper;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function render() {
    $newsletterId = (isset($_GET['id']) ? (int)$_GET['id'] : 0);
    $woocommerceTemplateId = (int)$this->settings->get(TransactionalEmails::SETTING_EMAIL_ID, null);
    if (
      $woocommerceTemplateId
      && $newsletterId === $woocommerceTemplateId
      && !$this->woocommerceHelper->isWooCommerceActive()
    ) {
      $location = 'admin.php?page=mailpoet-settings&enable-customizer-notice#woocommerce';
      if (headers_sent()) {
        echo '<script>window.location = "' . $location . '";</script>';
      } else {
        header('Location: ' . $location, true, 302);
      }
      exit;
    }

    $subscriber = $this->subscribersRepository->getCurrentWPUser();
    $subscriberData = $subscriber ? $this->formatSubscriber($subscriber) : [];
    $woocommerceData = [];
    if ($this->woocommerceHelper->isWooCommerceActive()) {
      $wcEmailSettings = $this->wcTransactionalEmails->getWCEmailSettings();
      $woocommerceData = [
        'email_headings' => $this->wcTransactionalEmails->getEmailHeadings(),
        'customizer_enabled' => (bool)$this->settings->get('woocommerce.use_mailpoet_editor'),
      ];
      $woocommerceData = array_merge($wcEmailSettings, $woocommerceData);
    }

    $data = [
      'shortcodes' => $this->shortcodesHelper->getShortcodes(),
      'settings' => $this->settings->getAll(),
      'editor_tutorial_seen' => $this->userFlags->get('editor_tutorial_seen'),
      'current_wp_user' => array_merge($subscriberData, $this->wp->wpGetCurrentUser()->to_array()),
      'sub_menu' => Menu::MAIN_PAGE_SLUG,
      'mss_active' => Bridge::isMPSendingServiceEnabled(),
      'woocommerce' => $woocommerceData,
      'is_wc_transactional_email' => $newsletterId === $woocommerceTemplateId,
      'site_name' => $this->wp->wpSpecialcharsDecode($this->wp->getOption('blogname'), ENT_QUOTES),
      'site_address' => $this->wp->wpParseUrl($this->wp->homeUrl(), PHP_URL_HOST),
      'mss_key_pending_approval' => $this->servicesChecker->isMailPoetAPIKeyPendingApproval(),
    ];
    $this->wp->wpEnqueueMedia();
    $this->wp->wpEnqueueStyle('editor', $this->wp->includesUrl('css/editor.css'));

    $this->pageRenderer->displayPage('newsletter/editor.html', $data);
  }

  private function formatSubscriber(SubscriberEntity $subscriber): array {
    return [
      'id' => $subscriber->getId(),
      'wp_user_id' => $subscriber->getWpUserId(),
      'is_woocommerce_user' => (string)$subscriber->getIsWoocommerceUser(), // BC compatibility
      'first_name' => $subscriber->getFirstName(),
      'last_name' => $subscriber->getLastName(),
      'email' => $subscriber->getEmail(),
      'status' => $subscriber->getStatus(),
      'subscribed_ip' => $subscriber->getSubscribedIp(),
      'confirmed_ip' => $subscriber->getConfirmedIp(),
      'confirmed_at' => ($confirmedAt = $subscriber->getConfirmedAt()) ? $confirmedAt->format(self::DATE_FORMAT) : null,
      'last_subscribed_at' => ($lastSubscribedAt = $subscriber->getLastSubscribedAt()) ? $lastSubscribedAt->format(self::DATE_FORMAT) : null,
      'created_at' => $subscriber->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $subscriber->getUpdatedAt()->format(self::DATE_FORMAT),
      'deleted_at' => ($deletedAt = $subscriber->getDeletedAt()) ? $deletedAt->format(self::DATE_FORMAT) : null,
      'unconfirmed_data' => $subscriber->getUnconfirmedData(),
      'source' => $subscriber->getSource(),
      'count_confirmation' => $subscriber->getConfirmationsCount(),
      'unsubscribe_token' => $subscriber->getUnsubscribeToken(),
      'link_token' => $subscriber->getLinkToken(),
    ];
  }
}
