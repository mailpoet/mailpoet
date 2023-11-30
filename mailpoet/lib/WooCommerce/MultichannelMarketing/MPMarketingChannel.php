<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce\MultichannelMarketing;

use Automattic\WooCommerce\Admin\Marketing\MarketingCampaign;
use Automattic\WooCommerce\Admin\Marketing\MarketingCampaignType;
use Automattic\WooCommerce\Admin\Marketing\MarketingChannelInterface;
use Automattic\WooCommerce\Admin\Marketing\Price;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Controller\OverviewStatisticsController;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\QueryWithCompare;
use MailPoet\Config\Menu;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Services\AuthorizedEmailsController;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\CdnAssetUrl;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Carbon\Carbon;

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

  /**
   * @var NewslettersRepository
   */
  private $newsletterRepository;

  /**
   * @var Helper
   */
  private $woocommerceHelper;

  /**
   * @var AutomationStorage
   */
  private $automationStorage;

  /**
   * @var NewsletterStatisticsRepository
   */
  private $newsletterStatisticsRepository;

  /**
   * @var OverviewStatisticsController
   */
  private $overviewStatisticsController;

  const CAMPAIGN_TYPE_NEWSLETTERS = 'mailpoet-newsletters';
  const CAMPAIGN_TYPE_POST_NOTIFICATIONS = 'mailpoet-post-notifications';
  const CAMPAIGN_TYPE_AUTOMATIONS = 'mailpoet-automations';

  public function __construct(
    CdnAssetUrl $cdnAssetUrl,
    SettingsController $settings,
    Bridge $bridge,
    NewslettersRepository $newsletterRepository,
    Helper $woocommerceHelper,
    AutomationStorage $automationStorage,
    NewsletterStatisticsRepository $newsletterStatisticsRepository,
    OverviewStatisticsController $overviewStatisticsController
  ) {
    $this->cdnAssetUrl = $cdnAssetUrl;
    $this->settings = $settings;
    $this->bridge = $bridge;
    $this->newsletterRepository = $newsletterRepository;
    $this->woocommerceHelper = $woocommerceHelper;
    $this->automationStorage = $automationStorage;
    $this->newsletterStatisticsRepository = $newsletterStatisticsRepository;
    $this->overviewStatisticsController = $overviewStatisticsController;
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
    return __('MailPoet', 'mailpoet');
  }

  /**
   * Returns the description of the marketing channel.
   *
   * @return string
   */
  public function get_description(): string { // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    return __('Create and send newsletters, post notifications and welcome emails from your WordPress.', 'mailpoet');
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
      $allCampaigns = $this->generateCampaigns();

    if (empty($allCampaigns)) {
        return [];
    }

    return $allCampaigns;
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
        __('MailPoet Newsletters', 'mailpoet'),
        __(
          'Send a newsletter with images, buttons, dividers, and social bookmarks. Or, just send a basic text email.',
          'mailpoet',
        ),
        admin_url('admin.php?page=' . Menu::EMAILS_PAGE_SLUG . '#/new/standard'),
        $this->get_icon_url()
      ),
      self::CAMPAIGN_TYPE_POST_NOTIFICATIONS => new MarketingCampaignType(
        'mailpoet-post-notifications',
        $this,
        __('MailPoet Post Notifications', 'mailpoet'),
        __(
          'Let MailPoet email your subscribers with your latest content. You can send daily, weekly, monthly, or even immediately after publication.',
          'mailpoet',
        ),
        admin_url('admin.php?page=' . Menu::EMAILS_PAGE_SLUG . '#/new/notification'),
        $this->get_icon_url()
      ),
      self::CAMPAIGN_TYPE_AUTOMATIONS => new MarketingCampaignType(
        'mailpoet-automations',
        $this,
        __('MailPoet Automations', 'mailpoet'),
        __('Set up automations to send abandoned cart reminders, welcome new subscribers, celebrate first-time buyers, and much more.', 'mailpoet'),
        admin_url('admin.php?page=' . Menu::AUTOMATION_TEMPLATES_PAGE_SLUG),
        $this->get_icon_url()
      ),
    ];
  }

  protected function getStandardNewsletterList(): array {
    $result = [];

    $userCurrency = $this->woocommerceHelper->getWoocommerceCurrency();

    // fetch the most recent newsletters limited to ten
    foreach ($this->newsletterRepository->getStandardNewsletterListWithMultipleStatuses(10) as $newsletter) {
        $newsLetterId = (string)$newsletter->getId();

        /** @var ?WooCommerceRevenue $wooRevenue */
        $wooRevenue = $this->newsletterStatisticsRepository->getWooCommerceRevenue($newsletter);

        $result[] = [
            'id' => $newsLetterId,
            'name' => $newsletter->getSubject(),
            'campaignType' => $this->campaignTypes[self::CAMPAIGN_TYPE_NEWSLETTERS],
            'url' => admin_url('admin.php?page=' . Menu::EMAILS_PAGE_SLUG . '/#/stats/' . $newsLetterId),
            'price' => [
                'amount' => $wooRevenue ? $wooRevenue->getValue() : 0,
                'currency' => $userCurrency,
            ],
        ];
    }

    return $result;
  }

  protected function getPostNotificationNewsletters(): array {
    $result = [];

    $userCurrency = $this->woocommerceHelper->getWoocommerceCurrency();

    // fetch the most recently sent post-notification history newsletters limited to ten
    foreach ($this->newsletterRepository->getNotificationHistoryItems(10) as $newsletter) {
      $newsLetterId = (string)$newsletter->getId();

      /** @var ?WooCommerceRevenue $wooRevenue */
      $wooRevenue = $this->newsletterStatisticsRepository->getWooCommerceRevenue($newsletter);

      $result[] = [
        'id' => $newsLetterId,
        'name' => $newsletter->getSubject(),
        'campaignType' => $this->campaignTypes[self::CAMPAIGN_TYPE_POST_NOTIFICATIONS],
        'url' => admin_url('admin.php?page=' . Menu::EMAILS_PAGE_SLUG . '/#/stats/' . $newsLetterId),
        'price' => [
          'amount' => $wooRevenue ? $wooRevenue->getValue() : 0,
          'currency' => $userCurrency,
        ],
      ];
    }

    return $result;
  }

  protected function getAutomations(): array {
    $result = [];

    // Fetch Automation stats within the last 90 days
    $primaryAfter = new \DateTimeImmutable((string)Carbon::now()->subDays(90)->toISOString());
    $primaryBefore = new \DateTimeImmutable((string)Carbon::now()->toISOString());
    $now = new \DateTimeImmutable('');

    $query = new QueryWithCompare($primaryAfter, $primaryBefore, $now, $now);
    $userCurrency = $this->woocommerceHelper->getWoocommerceCurrency();

    foreach ($this->automationStorage->getAutomations([Automation::STATUS_ACTIVE]) as $automation) {
      $automationId = (string)$automation->getId();

      $automationStatistics = $this->overviewStatisticsController->getStatisticsForAutomation($automation, $query);

      $result[] = [
        'id' => $automationId,
        'name' => $automation->getName(),
        'campaignType' => $this->campaignTypes[self::CAMPAIGN_TYPE_AUTOMATIONS],
        'url' => admin_url('admin.php?page=' . Menu::AUTOMATION_ANALYTICS_PAGE_SLUG . '&id=' . $automationId),
        'price' => [
          'amount' => $automationStatistics['revenue']['current'] ?? 0,
          'currency' => $userCurrency,
        ],
      ];
    }

    return $result;
  }

  protected function generateCampaigns(): array {
      return array_map(
          function (array $data) {
              $cost = null;

            if (isset( $data['price'] )) {
                $cost = new Price( (string)$data['price']['amount'], $data['price']['currency'] );
            }

              return new MarketingCampaign(
                  $data['id'],
                  $data['campaignType'],
                  $data['name'],
                  $data['url'],
                  $cost,
              );
          },
          array_merge(
              $this->getAutomations(),
              $this->getPostNotificationNewsletters(),
              $this->getStandardNewsletterList()
          )
      );
  }
}
