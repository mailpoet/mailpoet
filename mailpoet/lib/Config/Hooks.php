<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Config;

use MailPoet\Form\DisplayFormInWPContent;
use MailPoet\Mailer\WordPress\WordpressMailerReplacer;
use MailPoet\Newsletter\Scheduler\PostNotificationScheduler;
use MailPoet\Segments\WP;
use MailPoet\Settings\SettingsController;
use MailPoet\Statistics\Track\SubscriberHandler;
use MailPoet\Subscription\Comment;
use MailPoet\Subscription\Form;
use MailPoet\Subscription\Manage;
use MailPoet\Subscription\Registration;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WPCOM\DotcomLicenseProvisioner;

class Hooks {
  /** @var Form */
  private $subscriptionForm;

  /** @var Comment */
  private $subscriptionComment;

  /** @var Manage */
  private $subscriptionManage;

  /** @var Registration */
  private $subscriptionRegistration;

  /** @var SettingsController */
  private $settings;

  /** @var WPFunctions */
  private $wp;

  /** @var PostNotificationScheduler */
  private $postNotificationScheduler;

  /** @var WordpressMailerReplacer */
  private $wordpressMailerReplacer;

  /** @var DisplayFormInWPContent */
  private $displayFormInWPContent;

  /** @var WP */
  private $wpSegment;

  /** @var SubscriberHandler */
  private $subscriberHandler;

  /** @var HooksWooCommerce */
  private $hooksWooCommerce;

  /** @var SubscriberChangesNotifier */
  private $subscriberChangesNotifier;

  /** @var DotcomLicenseProvisioner */
  private $dotcomLicenseProvisioner;

  public function __construct(
    Form $subscriptionForm,
    Comment $subscriptionComment,
    Manage $subscriptionManage,
    Registration $subscriptionRegistration,
    SettingsController $settings,
    WPFunctions $wp,
    PostNotificationScheduler $postNotificationScheduler,
    WordpressMailerReplacer $wordpressMailerReplacer,
    DisplayFormInWPContent $displayFormInWPContent,
    HooksWooCommerce $hooksWooCommerce,
    SubscriberHandler $subscriberHandler,
    SubscriberChangesNotifier $subscriberChangesNotifier,
    WP $wpSegment,
    DotcomLicenseProvisioner $dotcomLicenseProvisioner
  ) {
    $this->subscriptionForm = $subscriptionForm;
    $this->subscriptionComment = $subscriptionComment;
    $this->subscriptionManage = $subscriptionManage;
    $this->subscriptionRegistration = $subscriptionRegistration;
    $this->settings = $settings;
    $this->wp = $wp;
    $this->postNotificationScheduler = $postNotificationScheduler;
    $this->wordpressMailerReplacer = $wordpressMailerReplacer;
    $this->displayFormInWPContent = $displayFormInWPContent;
    $this->wpSegment = $wpSegment;
    $this->subscriberHandler = $subscriberHandler;
    $this->hooksWooCommerce = $hooksWooCommerce;
    $this->subscriberChangesNotifier = $subscriberChangesNotifier;
    $this->dotcomLicenseProvisioner = $dotcomLicenseProvisioner;
  }

  public function init() {
    $this->setupWPUsers();
    $this->setupWooCommerceUsers();
    $this->setupWooCommercePurchases();
    $this->setupWooCommerceSubscriberEngagement();
    $this->setupWooCommerceTracking();
    $this->setupImageSize();
    $this->setupListing();
    $this->setupSubscriptionEvents();
    $this->setupWooCommerceSubscriptionEvents();
    $this->setupPostNotifications();
    $this->setupWooCommerceSettings();
    $this->setupFooter();
    $this->setupSettingsLinkInPluginPage();
    $this->setupChangeNotifications();
    $this->setupLicenseProvisioning();
  }

  public function initEarlyHooks() {
    $this->setupMailer();
  }

  public function setupSubscriptionEvents() {

    $subscribe = $this->settings->get('subscribe', []);
    // Subscribe in comments
    if (
      isset($subscribe['on_comment']['enabled'])
      &&
      (bool)$subscribe['on_comment']['enabled']
    ) {
      if ($this->wp->isUserLoggedIn()) {
        $this->wp->addAction(
          'comment_form_field_comment',
          [$this->subscriptionComment, 'extendLoggedInForm']
        );
      } else {
        $this->wp->addAction(
          'comment_form_after_fields',
          [$this->subscriptionComment, 'extendLoggedOutForm']
        );
      }

      $this->wp->addAction(
        'comment_post',
        [$this->subscriptionComment, 'onSubmit'],
        60,
        2
      );

      $this->wp->addAction(
        'wp_set_comment_status',
        [$this->subscriptionComment, 'onStatusUpdate'],
        60,
        2
      );
    }

    // Subscribe in registration form
    if (
      isset($subscribe['on_register']['enabled'])
      &&
      (bool)$subscribe['on_register']['enabled']
    ) {
      if (is_multisite()) {
        $this->wp->addAction(
          'signup_extra_fields',
          [$this->subscriptionRegistration, 'extendForm']
        );
        $this->wp->addAction(
          'wpmu_validate_user_signup',
          [$this->subscriptionRegistration, 'onMultiSiteRegister'],
          60,
          1
        );
      } else {
        $this->wp->addAction(
          'register_form',
          [$this->subscriptionRegistration, 'extendForm']
        );
        // we need to process new users while they are registered.
        // We used `register_post` before but that is too soon
        //   because if registration fails during `registration_errors` we will keep the user as subscriber.
        // So we are hooking to `registration_error` with a low priority.
        $this->wp->addFilter(
          'registration_errors',
          [$this->subscriptionRegistration, 'onRegister'],
          60,
          3
        );
      }
      $this->wp->addAction(
        'woocommerce_register_form',
        [$this->hooksWooCommerce, 'extendForm']
      );
      $this->wp->addFilter(
        'woocommerce_registration_errors',
        [$this->hooksWooCommerce, 'onRegister'],
        60,
        3
      );
    }

    // Manage subscription
    $this->wp->addAction(
      'admin_post_mailpoet_subscription_update',
      [$this->subscriptionManage, 'onSave']
    );
    $this->wp->addAction(
      'admin_post_nopriv_mailpoet_subscription_update',
      [$this->subscriptionManage, 'onSave']
    );

    // Subscription form
    $this->wp->addAction(
      'admin_post_mailpoet_subscription_form',
      [$this->subscriptionForm, 'onSubmit']
    );
    $this->wp->addAction(
      'admin_post_nopriv_mailpoet_subscription_form',
      [$this->subscriptionForm, 'onSubmit']
    );
    $this->wp->addFilter(
      'the_content',
      [$this->displayFormInWPContent, 'contentDisplay']
    );
    $this->wp->addFilter(
      'woocommerce_product_loop_end',
      [$this->displayFormInWPContent, 'wooProductListDisplay']
    );
  }

  public function setupMailer() {
    $this->wp->addAction('plugins_loaded', [
      $this->wordpressMailerReplacer,
      'replaceWordPressMailer',
    ]);
    $this->wp->addAction('login_init', [
      $this->wordpressMailerReplacer,
      'replaceWordPressMailer',
    ]);
    $this->wp->addAction('lostpassword_post', [
      $this->wordpressMailerReplacer,
      'replaceWordPressMailer',
    ]);
  }

  public function setupWooCommerceSubscriptionEvents() {
    $woocommerce = $this->settings->get('woocommerce', []);
    // WooCommerce: subscribe on checkout
    if (!empty($woocommerce['optin_on_checkout']['enabled'])) {
      $this->wp->addAction(
        'woocommerce_checkout_before_terms_and_conditions',
        [$this->hooksWooCommerce, 'extendWooCommerceCheckoutForm']
      );
    }

    $this->wp->addAction(
      'woocommerce_checkout_update_order_meta',
      [$this->hooksWooCommerce, 'subscribeOnCheckout'],
      10, // this should execute after the WC sync call on the same hook
      2
    );

    $this->wp->addAction(
      'woocommerce_before_pay_action',
      [$this->hooksWooCommerce, 'subscribeOnOrderPay'],
      10,
      1
    );
  }

  public function setupWPUsers() {
    // WP Users synchronization
    $this->wp->addAction(
      'user_register',
      [$this->wpSegment, 'synchronizeUser'],
      6
    );
    $this->wp->addAction(
      'added_existing_user',
      [$this->wpSegment, 'synchronizeUser'],
      6
    );
    $this->wp->addAction(
      'profile_update',
      [$this->wpSegment, 'synchronizeUser'],
      6, 2
    );
    $this->wp->addAction(
      'add_user_role',
      [$this->wpSegment, 'synchronizeUser'],
      6, 1
    );
    $this->wp->addAction(
      'set_user_role',
      [$this->wpSegment, 'synchronizeUser'],
      6, 1
    );
    $this->wp->addAction(
      'delete_user',
      [$this->wpSegment, 'synchronizeUser'],
      1
    );
    // multisite
    $this->wp->addAction(
      'deleted_user',
      [$this->wpSegment, 'synchronizeUser'],
      1
    );
    $this->wp->addAction(
      'remove_user_from_blog',
      [$this->wpSegment, 'synchronizeUser'],
      1
    );

    // login
    $this->wp->addAction(
      'wp_login',
      [$this->subscriberHandler, 'identifyByLogin'],
      10,
      1
    );
  }

  public function setupWooCommerceSettings() {
    $this->wp->addAction('woocommerce_settings_start', [
      $this->hooksWooCommerce,
      'disableWooCommerceSettings',
    ]);

    $this->wp->addAction('before_woocommerce_init', [
      $this->hooksWooCommerce,
      'declareHposCompatibility',
    ]);

    $this->wp->addAction('init', [
      $this->hooksWooCommerce,
      'addMailPoetTaskToWooHomePage',
    ]);
  }

  public function setupWooCommerceUsers() {
    // WooCommerce Customers synchronization
    $this->wp->addAction(
      'woocommerce_created_customer',
      [$this->hooksWooCommerce, 'synchronizeRegisteredCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_new_customer',
      [$this->hooksWooCommerce, 'synchronizeRegisteredCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_update_customer',
      [$this->hooksWooCommerce, 'synchronizeRegisteredCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_delete_customer',
      [$this->hooksWooCommerce, 'synchronizeRegisteredCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_checkout_update_order_meta',
      [$this->hooksWooCommerce, 'synchronizeGuestCustomer'],
      7
    );
    $this->wp->addAction(
      'woocommerce_process_shop_order_meta',
      [$this->hooksWooCommerce, 'synchronizeGuestCustomer'],
      7
    );
  }

  public function setupWooCommercePurchases() {
    // use both 'processing' and 'completed' states since payment hook and 'processing' status
    // may be skipped with some payment methods (cheque) or when state transitioned manually
    $acceptedOrderStates = WPFunctions::get()->applyFilters(
      'mailpoet_purchase_order_states',
      ['processing', 'completed']
    );

    foreach ($acceptedOrderStates as $status) {
      WPFunctions::get()->addAction(
        'woocommerce_order_status_' . $status,
        [$this->hooksWooCommerce, 'trackPurchase'],
        10,
        1
      );
    }
  }

  public function setupWooCommerceSubscriberEngagement() {
    $this->wp->addAction(
      'woocommerce_new_order',
      [$this->hooksWooCommerce, 'updateSubscriberEngagement'],
      7
    );
  }

  public function setupWooCommerceTracking() {
    $this->wp->addFilter(
      'woocommerce_tracker_data',
      [$this->hooksWooCommerce, 'addTrackingData'],
      10
    );
  }

  public function setupImageSize() {
    $this->wp->addFilter(
      'image_size_names_choose',
      [$this, 'appendImageSize'],
      10, 1
    );
  }

  public function appendImageSize($sizes) {
    return array_merge($sizes, [
      'mailpoet_newsletter_max' => __('MailPoet Newsletter', 'mailpoet'),
    ]);
  }

  public function setupListing() {
    $this->wp->addFilter(
      'set-screen-option',
      [$this, 'setScreenOption'],
      10, 3
    );
  }

  public function setScreenOption($status, $option, $value) {
    if (preg_match('/^mailpoet_(.*)_per_page$/', $option)) {
      return $value;
    } else {
      return $status;
    }
  }

  public function setupPostNotifications() {
    $this->wp->addAction(
      'transition_post_status',
      [$this->postNotificationScheduler, 'transitionHook'],
      10, 3
    );
  }

  public function setupFooter() {
    if (!Menu::isOnMailPoetAdminPage()) {
      return;
    }
    $this->wp->addFilter(
      'admin_footer_text',
      [$this, 'setFooter'],
      1, 1
    );
  }

  public function setFooter(): string {

    if (Menu::isOnMailPoetAutomationPage()) {
      return '';
    }
    return '<a href="https://feedback.mailpoet.com/" rel="noopener noreferrer" target="_blank">' . esc_html__('Give feedback', 'mailpoet') . '</a>';
  }

  public function setupSettingsLinkInPluginPage() {
    $this->wp->addFilter(
      'plugin_action_links_' . Env::$pluginPath,
      [$this, 'setSettingsLinkInPluginPage']
    );
  }

  /**
   * @param array<string, string> $actionLinks
   * @return array<string, string>
   */
  public function setSettingsLinkInPluginPage(array $actionLinks): array {
    $customLinks = [
      'settings' => '<a href="' . $this->wp->adminUrl('admin.php?page=mailpoet-settings') . '" aria-label="' . $this->wp->escAttr(__('View MailPoet settings', 'mailpoet')) . '">' . $this->wp->escHtml(__('Settings', 'mailpoet')) . '</a>',
    ];

    return array_merge($customLinks, $actionLinks);
  }

  public function setupChangeNotifications(): void {
    $this->wp->addAction(
      'shutdown',
      [$this->subscriberChangesNotifier, 'notify']
    );
  }

  public function setupLicenseProvisioning(): void {
    $this->wp->addFilter(
      'wpcom_marketplace_webhook_response_mailpoet-business',
      [$this->dotcomLicenseProvisioner, 'provisionLicense'],
      10,
      3
    );
  }
}
