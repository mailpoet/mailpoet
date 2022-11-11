<?php

namespace MailPoet\Analytics;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Config\ServicesChecker;
use MailPoet\Cron\CronTrigger;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Segments\DynamicSegments\DynamicSegmentFilterRepository;
use MailPoet\Segments\DynamicSegments\Filters\EmailAction;
use MailPoet\Segments\DynamicSegments\Filters\EmailActionClickAny;
use MailPoet\Segments\DynamicSegments\Filters\EmailOpensAbsoluteCountAction;
use MailPoet\Segments\DynamicSegments\Filters\MailPoetCustomFields;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberScore;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSegment;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberSubscribedDate;
use MailPoet\Segments\DynamicSegments\Filters\SubscriberTag;
use MailPoet\Segments\DynamicSegments\Filters\UserRole;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCategory;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceCountry;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceMembership;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceNumberOfOrders;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceSubscription;
use MailPoet\Segments\DynamicSegments\Filters\WooCommerceTotalSpent;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscriberListingRepository;
use MailPoet\Tags\TagRepository;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class Reporter {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var DynamicSegmentFilterRepository */
  private $dynamicSegmentFilterRepository;

  /** @var TagRepository */
  private $tagRepository;

  /** @var ServicesChecker */
  private $servicesChecker;

  /** @var SettingsController */
  private $settings;

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  /** @var WPFunctions */
  private $wp;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var TrackingConfig */
  private $trackingConfig;

  /** @var SubscriberListingRepository */
  private $subscriberListingRepository;

  /** @var AutomationStorage  */
  private $automationStorage;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    SegmentsRepository $segmentsRepository,
    DynamicSegmentFilterRepository $dynamicSegmentFilterRepository,
    TagRepository $tagRepository,
    ServicesChecker $servicesChecker,
    SettingsController $settings,
    WooCommerceHelper $woocommerceHelper,
    WPFunctions $wp,
    SubscribersFeature $subscribersFeature,
    TrackingConfig $trackingConfig,
    SubscriberListingRepository $subscriberListingRepository,
    AutomationStorage $automationStorage
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->segmentsRepository = $segmentsRepository;
    $this->dynamicSegmentFilterRepository = $dynamicSegmentFilterRepository;
    $this->tagRepository = $tagRepository;
    $this->servicesChecker = $servicesChecker;
    $this->settings = $settings;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->wp = $wp;
    $this->subscribersFeature = $subscribersFeature;
    $this->trackingConfig = $trackingConfig;
    $this->subscriberListingRepository = $subscriberListingRepository;
    $this->automationStorage = $automationStorage;
  }

  public function getData() {
    global $wpdb, $wp_version, $woocommerce; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $mta = $this->settings->get('mta', []);
    $newsletters = $this->newslettersRepository->getAnalytics();
    $isCronTriggerMethodWP = $this->settings->get('cron_trigger.method') === CronTrigger::METHOD_WORDPRESS;
    $bounceAddress = $this->settings->get('bounce.address');
    $segments = $this->segmentsRepository->getCountsPerType();
    $hasWc = $this->woocommerceHelper->isWooCommerceActive();
    $inactiveSubscribersMonths = (int)round((int)$this->settings->get('deactivate_subscriber_after_inactive_days') / 30);
    $inactiveSubscribersStatus = $inactiveSubscribersMonths === 0 ? 'never' : "$inactiveSubscribersMonths months";

    $result = [
      'PHP version' => PHP_VERSION,
      'MySQL version' => $wpdb->db_version(),
      'WordPress version' => $wp_version, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      'Multisite environment' => $this->wp->isMultisite() ? 'yes' : 'no',
      'RTL' => $this->wp->isRtl() ? 'yes' : 'no',
      'WP_MEMORY_LIMIT' => WP_MEMORY_LIMIT,
      'WP_MAX_MEMORY_LIMIT' => WP_MAX_MEMORY_LIMIT,
      'PHP memory_limit' => ini_get('memory_limit'),
      'PHP max_execution_time' => ini_get('max_execution_time'),
      'users_can_register' => $this->wp->getOption('users_can_register') ? 'yes' : 'no',
      'MailPoet Free version' => MAILPOET_VERSION,
      'MailPoet Premium version' => (defined('MAILPOET_PREMIUM_VERSION')) ? MAILPOET_PREMIUM_VERSION : 'N/A',
      'Total number of subscribers' => $this->subscribersFeature->getSubscribersCount(),
      'Sending Method' => isset($mta['method']) ? $mta['method'] : null,
      "Send all site's emails with" => $this->settings->get('send_transactional_emails') ? 'current sending method' : 'default WordPress sending method',
      'Date of plugin installation' => $this->settings->get('installed_at'),
      'Subscribe in comments' => (boolean)$this->settings->get('subscribe.on_comment.enabled', false),
      'Subscribe in registration form' => (boolean)$this->settings->get('subscribe.on_register.enabled', false),
      'Manage Subscription page > MailPoet page' => (boolean)Pages::isMailpoetPage(intval($this->settings->get('subscription.pages.manage'))),
      'Unsubscribe page > MailPoet page' => (boolean)Pages::isMailpoetPage(intval($this->settings->get('subscription.pages.unsubscribe'))),
      'Sign-up confirmation' => (boolean)$this->settings->get('signup_confirmation.enabled', false),
      'Sign-up confirmation: Confirmation page > MailPoet page' => (boolean)Pages::isMailpoetPage(intval($this->settings->get('subscription.pages.confirmation'))),
      'Bounce email address' => !empty($bounceAddress),
      'Newsletter task scheduler (cron)' => $isCronTriggerMethodWP ? 'visitors' : 'script',
      'Open and click tracking' => $this->trackingConfig->isEmailTrackingEnabled(),
      'Tracking level' => $this->settings->get('tracking.level', TrackingConfig::LEVEL_FULL),
      'Premium key valid' => $this->servicesChecker->isPremiumKeyValid(),
      'New subscriber notifications' => NewSubscriberNotificationMailer::isDisabled($this->settings->get(NewSubscriberNotificationMailer::SETTINGS_KEY)),
      'Number of standard newsletters sent in last 3 months' => $newsletters['sent_newsletters_3_months'],
      'Number of standard newsletters sent in last 30 days' => $newsletters['sent_newsletters_30_days'],
      'Number of active post notifications' => $newsletters['notifications_count'],
      'Number of active welcome emails' => $newsletters['welcome_newsletters_count'],
      'Total number of standard newsletters sent' => $newsletters['sent_newsletters_count'],
      'Number of segments' => isset($segments['dynamic']) ? (int)$segments['dynamic'] : 0,
      'Number of lists' => isset($segments['default']) ? (int)$segments['default'] : 0,
      'Number of subscriber tags' => $this->tagRepository->countBy([]),
      'Stop sending to inactive subscribers' => $inactiveSubscribersStatus,
      'Plugin > MailPoet Premium' => $this->wp->isPluginActive('mailpoet-premium/mailpoet-premium.php'),
      'Plugin > bounce add-on' => $this->wp->isPluginActive('mailpoet-bounce-handler/mailpoet-bounce-handler.php'),
      'Plugin > Bloom' => $this->wp->isPluginActive('bloom-for-publishers/bloom.php'),
      'Plugin > WP Holler' => $this->wp->isPluginActive('holler-box/holler-box.php'),
      'Plugin > WP-SMTP' => $this->wp->isPluginActive('wp-mail-smtp/wp_mail_smtp.php'),
      'Plugin > WooCommerce' => $hasWc,
      'Plugin > WooCommerce Subscription' => $this->wp->isPluginActive('woocommerce-subscriptions/woocommerce-subscriptions.php'),
      'Plugin > WooCommerce Follow Up Emails' => $this->wp->isPluginActive('woocommerce-follow-up-emails/woocommerce-follow-up-emails.php'),
      'Plugin > WooCommerce Email Customizer' => $this->wp->isPluginActive('woocommerce-email-customizer/woocommerce-email-customizer.php'),
      'Plugin > WooCommerce Memberships' => $this->wp->isPluginActive('woocommerce-memberships/woocommerce-memberships.php'),
      'Plugin > WooCommerce MailChimp' => $this->wp->isPluginActive('woocommerce-mailchimp/woocommerce-mailchimp.php'),
      'Plugin > MailChimp for WooCommerce' => $this->wp->isPluginActive('mailchimp-for-woocommerce/mailchimp-woocommerce.php'),
      'Plugin > The Event Calendar' => $this->wp->isPluginActive('the-events-calendar/the-events-calendar.php'),
      'Plugin > Gravity Forms' => $this->wp->isPluginActive('gravityforms/gravityforms.php'),
      'Plugin > Ninja Forms' => $this->wp->isPluginActive('ninja-forms/ninja-forms.php'),
      'Plugin > WPForms' => $this->wp->isPluginActive('wpforms-lite/wpforms.php'),
      'Plugin > Formidable Forms' => $this->wp->isPluginActive('formidable/formidable.php'),
      'Plugin > Contact Form 7' => $this->wp->isPluginActive('contact-form-7/wp-contact-form-7.php'),
      'Plugin > Easy Digital Downloads' => $this->wp->isPluginActive('easy-digital-downloads/easy-digital-downloads.php'),
      'Plugin > WooCommerce Multi-Currency' => $this->wp->isPluginActive('woocommerce-multi-currency/woocommerce-multi-currency.php'),
      'Plugin > Multi Currency for WooCommerce' => $this->wp->isPluginActive('woo-multi-currency/woo-multi-currency.php'),
      'Web host' => $this->settings->get('mta_group') == 'website' ? $this->settings->get('web_host') : null,
      // Dynamic segment filters tracking -- start. If you extend segments tracking, please extend mapping in analytics.js
      'Segment > # of machine-opens' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_EMAIL, EmailOpensAbsoluteCountAction::MACHINE_TYPE),
      'Segment > # of opens' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_EMAIL, EmailOpensAbsoluteCountAction::TYPE),
      'Segment > # of orders' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceNumberOfOrders::ACTION_NUMBER_OF_ORDERS),
      'Segment > clicked' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_EMAIL, EmailAction::ACTION_CLICKED),
      'Segment > clicked any email' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_EMAIL, EmailActionClickAny::TYPE),
      'Segment > score' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberScore::TYPE),
      'Segment > subscribed to list' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberSegment::TYPE),
      'Segment > opened' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_EMAIL, EmailAction::ACTION_OPENED),
      'Segment > machine-opened' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_EMAIL, EmailAction::ACTION_MACHINE_OPENED),
      'Segment > is active member of' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_WOOCOMMERCE_MEMBERSHIP, WooCommerceMembership::ACTION_MEMBER_OF),
      'Segment > has an active subscription' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_WOOCOMMERCE_SUBSCRIPTION, WooCommerceSubscription::ACTION_HAS_ACTIVE),
      'Segment > is in country' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceCountry::ACTION_CUSTOMER_COUNTRY),
      'Segment > MailPoet custom field' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_USER_ROLE, MailPoetCustomFields::TYPE),
      'Segment > purchased in category' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceCategory::ACTION_CATEGORY),
      'Segment > purchased product' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceCategory::ACTION_PRODUCT),
      'Segment > subscribed date' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberSubscribedDate::TYPE),
      'Segment > total spent' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_WOOCOMMERCE, WooCommerceTotalSpent::ACTION_TOTAL_SPENT),
      'Segment > WordPress user role' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_USER_ROLE, UserRole::TYPE),
      'Segment > subscriber tags' => $this->isFilterTypeActive(DynamicSegmentFilterData::TYPE_USER_ROLE, SubscriberTag::TYPE),
      // Dynamic segment filters tracking -- end. If you extend segments tracking, please extend mapping in analytics.js
      'Number of segments with multiple conditions' => $this->segmentsRepository->getSegmentCountWithMultipleFilters(),
      'Support tier' => $this->subscribersFeature->hasPremiumSupport() ? 'premium' : 'free',
      'Unauthorized email notice shown' => !empty($this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING)),
    ];

    $result = array_merge(
      $result,
      $this->subscriberProperties(),
      $this->automationProperties()
    );
    if ($hasWc) {
      $result['WooCommerce version'] = $woocommerce->version;
      $result['Number of WooCommerce subscribers'] = isset($segments['woocommerce_users']) ? (int)$segments['woocommerce_users'] : 0;
      $result['WooCommerce: opt-in on checkout is active'] = $this->settings->get('woocommerce.optin_on_checkout.enabled') ?: false;
      $result['WooCommerce: set old customers as subscribed'] = $this->settings->get('mailpoet_subscribe_old_woocommerce_customers.enabled') ?: false;
      $result['WooCommerce email customizer is active'] = $this->settings->get('woocommerce.use_mailpoet_editor') ?: false;

      $result['Number of active WooCommerce first purchase emails'] = $newsletters['first_purchase_emails_count'];
      $result['Number of active WooCommerce purchased this product emails'] = $newsletters['product_purchased_emails_count'];
      $result['Number of active purchased in this category'] = $newsletters['product_purchased_in_category_emails_count'];
      $result['Number of active abandoned cart'] = $newsletters['abandoned_cart_emails_count'];

      $result['Installed via WooCommerce onboarding wizard'] = $this->woocommerceHelper->wasMailPoetInstalledViaWooCommerceOnboardingWizard();
    }

    return $result;
  }

  private function automationProperties(): array {
    $automations = $this->automationStorage->getAutomations();
    $activeAutomations = array_filter(
      $automations,
      function(Automation $automation): bool {
        return $automation->getStatus() === Automation::STATUS_ACTIVE;
      }
    );
    $activeAutomationCount = count($activeAutomations);
    $draftAutomations = array_filter(
      $automations,
      function(Automation $automation): bool {
        return $automation->getStatus() === Automation::STATUS_DRAFT;
      }
    );
    $automationsWithWordPressUserSubscribesTrigger = array_filter(
      $activeAutomations,
      function(Automation $automation): bool {
        return $automation->getTrigger('mailpoet:wp-user-registered') !== null;
      }
    );
    $automationsWithSomeoneSubscribesTrigger = array_filter(
      $activeAutomations,
      function(Automation $automation): bool {
        return $automation->getTrigger('mailpoet:someone-subscribes') !== null;
      }
    );

    $totalSteps = 0;
    $minSteps = null;
    $maxSteps = 0;
    foreach ($activeAutomations as $automation) {
      $steps = array_filter(
        $automation->getSteps(),
        function(Step $step): bool {
          return $step->getType() === Step::TYPE_ACTION;
        }
      );
      $stepCount = count($steps);
      $minSteps = $minSteps !== null ? min($stepCount, $minSteps) : $stepCount;
      $maxSteps = max($maxSteps, $stepCount);
      $totalSteps += $stepCount;
    }
    $averageSteps = $activeAutomationCount > 0 ? $totalSteps / $activeAutomationCount : 0;

    return [
      'Automation > Number of active automations' => $activeAutomationCount,
      'Automation > Number of draft automations' => count($draftAutomations),
      'Automation > Number of "WordPress user registers" active automations' => count($automationsWithWordPressUserSubscribesTrigger),
      'Automation > Number of "Someone subscribes" active automations ' => count($automationsWithSomeoneSubscribesTrigger),
      'Automation > Number of steps in shortest active automation' => $minSteps,
      'Automation > Number of steps in longest active automation' => $maxSteps,
      'Automation > Average number of steps in active automations' => $averageSteps,
    ];
  }

  private function subscriberProperties(): array {
    $definition = new ListingDefinition();
    $groups = $this->subscriberListingRepository->getGroups($definition);
    $properties = [];
    foreach ($groups as $group) {
      $properties['Subscribers > ' . $group['name']] = (int)$group['count'];
    }

    return $properties;
  }

  public function getTrackingData() {
    $newsletters = $this->newslettersRepository->getAnalytics();
    $segments = $this->segmentsRepository->getCountsPerType();
    $mta = $this->settings->get('mta', []);
    $installedAt = new Carbon($this->settings->get('installed_at'));
    return [
      'installedAtIso' => $installedAt->format(Carbon::ISO8601),
      'newslettersSent' => $newsletters['sent_newsletters_count'],
      'welcomeEmails' => $newsletters['welcome_newsletters_count'],
      'postnotificationEmails' => $newsletters['notifications_count'],
      'woocommerceEmails' => $newsletters['automatic_emails_count'],
      'subscribers' => $this->subscribersFeature->getSubscribersCount(),
      'lists' => isset($segments['default']) ? (int)$segments['default'] : 0,
      'sendingMethod' => isset($mta['method']) ? $mta['method'] : null,
      'woocommerceIsInstalled' => $this->woocommerceHelper->isWooCommerceActive(),
    ];
  }

  private function isFilterTypeActive(string $filterType, string $action): bool {
    if ($this->dynamicSegmentFilterRepository->findOnyByFilterTypeAndAction($filterType, $action)) {
      return true;
    }
    return false;
  }
}
