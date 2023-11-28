<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\MultichannelMarketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use MailPoet\Config\Menu;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\CdnAssetUrl;

class MPMarketingChannel implements MarketingChannelInterface {

  /** @var CdnAssetUrl */
  private $cdnAssetUrl;

  /**
   * @var SettingsController
   */
  private $settings;

  /**
   * @var Bridge
   */
  private $bridge;

  /**
   * @var MarketingCampaignType[]
   */
  private $campaignTypes;

  const CAMPAIGN_TYPE_NEWSLETTERS = 'mailpoet-newsletters';
  const CAMPAIGN_TYPE_POST_NOTIFICATIONS = 'mailpoet-post-notifications';
  const CAMPAIGN_TYPE_AUTOMATIONS = 'mailpoet-automations';

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    SettingsController $settings,
    Bridge $bridge
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->campaignTypes = $this->generateCampaignTypes();
  }

  /**
   * Returns the unique identifier string for the marketing channel extension, also known as the plugin slug.
   *
   * @return string
   */
  public function get_slug(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return 'mailpoet';
  }

  /**
   * Returns the name of the marketing channel.
   *
   * @return string
   */
  public function get_name(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return __( 'MailPoet', 'mailpoet' );
  }

  /**
   * Returns the description of the marketing channel.
   *
   * @return string
   */
  public function get_description(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return __( 'Create and send newsletters, post notifications and welcome emails from your WordPress.', 'mailpoet' );
  }

  /**
   * Returns the path to the channel icon.
   *
   * @return string
   */
  public function get_icon_url(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return $this->cdnAssetUrl->generateCdnUrl('icon-white-123x128.png');
  }

  /**
   * Returns the setup status of the marketing channel.
   *
   * @return bool
   */
  public function is_setup_completed(): bool { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return $this->isMPSetupComplete();
  }

  /**
   * Returns the URL to the settings page, or the link to complete the setup/onboarding if the channel has not been set up yet.
   *
   * @return string
   */
  public function get_setup_url(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if ($this->isMPSetupComplete()) {
      return admin_url('admin.php?page=' . Menu::MAIN_PAGE_SLUG);
    }

    return admin_url('admin.php?page=' . Menu::WELCOME_WIZARD_PAGE_SLUG . '&mailpoet_wizard_loaded_via_woocommerce_marketing_dashboard');
  }

  /**
   * Returns the status of the marketing channel's product listings.
   *
   * @return string
   */
  public function get_product_listings_status(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    if (!$this->bridge->isMailpoetSendingServiceEnabled()) {
      return self::PRODUCT_LISTINGS_NOT_APPLICABLE;
    }

    // Check for error status. It's null by default when there isn't an error
    $sendingStatus = $this->settings->get('mta_log.status');

    if ($sendingStatus) {
      return self::PRODUCT_LISTINGS_SYNC_FAILED;
    }

    return self::PRODUCT_LISTINGS_SYNCED;
  }

  /**
   * Returns the number of channel issues/errors (e.g. account-related errors, product synchronization issues, etc.).
   *
   * @return int The number of issues to resolve, or 0 if there are no issues with the channel.
   */
  public function get_errors_count(): int { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    $error = $this->settings->get('mta_log.error');

    $count = 0;

    if (!empty($error)) {
      $count++;
    }

    $validationError = $this->settings->get(AuthorizedEmailsController::AUTHORIZED_EMAIL_ADDRESSES_ERROR_SETTING);

    if ($validationError && isset($validationError['invalid_sender_address'])) {
      $count++;
    }

    return $count;
  }

  /**
   * Returns an array of marketing campaign types that the channel supports.
   *
   * @return MarketingCampaignType[] Array of marketing campaign type objects.
   */
  public function get_supported_campaign_types(): array { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return $this->campaignTypes;
  }

  /**
   * Returns an array of the channel's marketing campaigns.
   *
   * @return MarketingCampaign[]
   */
  public function get_campaigns(): array { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return []; // will be updated in MAILPOET-5698
  }

  /**
   * Whether the task is completed.
   * If the setting 'version' is not null it means the welcome wizard
   * was already completed so we mark this task as completed as well.
   */
  protected function isMPSetupComplete(): bool {
    $version = $this->settings->get('version');

    return $version !== null;
  }

  /**
   * Generate the marketing channel campaign types
   *
   * @return MarketingCampaignType[]
   */
  protected function generateCampaignTypes(): array {
    return [
      self::CAMPAIGN_TYPE_NEWSLETTERS => new MarketingCampaignType(
        'mailpoet-newsletters',
        $this,
        'MailPoet Newsletters',
        'Send a newsletter with images, buttons, dividers, and social bookmarks. Or, just send a basic text email.',
        admin_url('admin.php?page=' . Menu::EMAILS_PAGE_SLUG . '#/new/standard'),
        $this->get_icon_url()
      ),
      self::CAMPAIGN_TYPE_POST_NOTIFICATIONS => new MarketingCampaignType(
        'mailpoet-post-notifications',
        $this,
        'MailPoet Post notifications',
        'Email your subscribers your latest content. You can send daily, weekly, monthly, or even immediately after publication.',
        admin_url('admin.php?page=' . Menu::EMAILS_PAGE_SLUG . '#/new/notification'),
        $this->get_icon_url()
      ),
      self::CAMPAIGN_TYPE_AUTOMATIONS => new MarketingCampaignType(
        'mailpoet-automations',
        $this,
        'MailPoet Automations',
        'Set up automations to send abandoned cart reminders, welcome new subscribers, celebrate first-time buyers, and much more.',
        admin_url('admin.php?page=' . Menu::AUTOMATION_TEMPLATES_PAGE_SLUG),
        $this->get_icon_url()
      ),
    ];
  }
}
