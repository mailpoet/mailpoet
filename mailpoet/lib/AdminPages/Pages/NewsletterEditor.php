<?php

namespace MailPoet\AdminPages\Pages;

use MailPoet\AdminPages\PageRenderer;
use MailPoet\Config\Menu;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Form\Util\CustomFonts;
use MailPoet\Newsletter\Shortcodes\ShortcodesHelper;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\UserFlagsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WooCommerce\TransactionalEmailHooks;
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

  /** @var ShortcodesHelper */
  private $shortcodesHelper;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var TransactionalEmailHooks */
  private $wooEmailHooks;

  /** @var CustomFonts  */
  private $customFonts;

  public function __construct(
    PageRenderer $pageRenderer,
    SettingsController $settings,
    UserFlagsController $userFlags,
    WooCommerceHelper $woocommerceHelper,
    WPFunctions $wp,
    TransactionalEmails $wcTransactionalEmails,
    ShortcodesHelper $shortcodesHelper,
    SubscribersRepository $subscribersRepository,
    TransactionalEmailHooks $wooEmailHooks,
    CustomFonts $customFonts
  ) {
    $this->pageRenderer = $pageRenderer;
    $this->settings = $settings;
    $this->userFlags = $userFlags;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wp = $wp;
    $this->wcTransactionalEmails = $wcTransactionalEmails;
    $this->shortcodesHelper = $shortcodesHelper;
    $this->subscribersRepository = $subscribersRepository;
    $this->wooEmailHooks = $wooEmailHooks;
    $this->customFonts = $customFonts;
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
        echo '<script>window.location = "' . esc_js($location) . '";</script>';
      } else {
        header('Location: ' . $location, true, 302);
      }
      exit;
    }

    $subscriber = $this->subscribersRepository->getCurrentWPUser();
    $subscriberData = $subscriber ? $this->formatSubscriber($subscriber) : [];
    $woocommerceData = [];
    if ($this->woocommerceHelper->isWooCommerceActive()) {
      // Activate hooks for Woo emails styles so that we always load styles set in Woo email customizer
      if ($newsletterId === (int)$this->settings->get(TransactionalEmails::SETTING_EMAIL_ID)) {
        $this->wooEmailHooks->overrideStylesForWooEmails();
      }
      $wcEmailSettings = $this->wcTransactionalEmails->getWCEmailSettings();
      $woocommerceData = [
        'email_headings' => $this->wcTransactionalEmails->getEmailHeadings(),
        'customizer_enabled' => (bool)$this->settings->get('woocommerce.use_mailpoet_editor'),
      ];
      $woocommerceData = array_merge($wcEmailSettings, $woocommerceData);
    }

    $data = [
      'customFontsEnabled' => $this->customFonts->displayCustomFonts(),
      'shortcodes' => $this->shortcodesHelper->getShortcodes(),
      'settings' => $this->settings->getAll(),
      'editor_tutorial_seen' => $this->userFlags->get('editor_tutorial_seen'),
      'current_wp_user' => array_merge($subscriberData, $this->wp->wpGetCurrentUser()->to_array()),
      'sub_menu' => Menu::MAIN_PAGE_SLUG,
      'woocommerce' => $woocommerceData,
      'is_wc_transactional_email' => $newsletterId === $woocommerceTemplateId,
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
      'created_at' => ($createdAt = $subscriber->getCreatedAt()) ? $createdAt->format(self::DATE_FORMAT) : null,
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
