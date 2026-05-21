<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\NewslettersResponseBuilder;
use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Newsletter\Listing\NewsletterListingRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

/**
 * `GET /mailpoet/v1/newsletters`
 *
 * Each email "tab" in the admin UI (Standard, Post Notifications, Notification
 * History, Re-engagement) is the same underlying listing scoped by the `type`
 * query parameter; the repository inspects the listing definition's `params`
 * to apply the correct WHERE on `newsletters.type`. The response shape stays
 * 1:1 with the legacy JSON endpoint at the top level (`items`, `meta`,
 * `filters`, `groups`) and additionally carries the mailer / cron envelope
 * fields the listing page used to read from the legacy meta.
 */
class NewslettersListingEndpoint extends AbstractListingEndpoint {
  public const SUPPORTED_TYPES = [
    NewsletterEntity::TYPE_STANDARD,
    NewsletterEntity::TYPE_NOTIFICATION,
    NewsletterEntity::TYPE_NOTIFICATION_HISTORY,
    NewsletterEntity::TYPE_RE_ENGAGEMENT,
    NewsletterEntity::TYPE_WELCOME,
    NewsletterEntity::TYPE_AUTOMATIC,
  ];

  /** @var NewsletterListingRepository */
  private $newsletterListingRepository;

  /** @var NewslettersResponseBuilder */
  private $newslettersResponseBuilder;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ListingHandler $listingHandler,
    NewsletterListingRepository $newsletterListingRepository,
    NewslettersResponseBuilder $newslettersResponseBuilder,
    SettingsController $settings,
    CronHelper $cronHelper,
    WPFunctions $wp
  ) {
    parent::__construct($listingHandler);
    $this->newsletterListingRepository = $newsletterListingRepository;
    $this->newslettersResponseBuilder = $newslettersResponseBuilder;
    $this->settings = $settings;
    $this->cronHelper = $cronHelper;
    $this->wp = $wp;
  }

  public function checkPermissions(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  public function handle(Request $request): Response {
    /** @var Response $response */
    $response = parent::handle($request);
    $payload = $response->get_data();
    if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
      $payload['data']['mta_log'] = $this->settings->get('mta_log');
      $payload['data']['mta_method'] = $this->settings->get('mta.method');
      $payload['data']['cron_accessible'] = $this->cronHelper->isDaemonAccessible();
      $payload['data']['current_time'] = $this->wp->currentTime('mysql');
      $response->set_data($payload);
    }
    return $response;
  }

  public static function getRequestSchema(): array {
    return array_merge(parent::getRequestSchema(), [
      'type' => Builder::string(),
      'parent_id' => Builder::integer(),
    ]);
  }

  protected function getListingRepository(): ListingRepository {
    return $this->newsletterListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    $items = $this->newslettersResponseBuilder->buildForListing($rows);
    $filteredItems = [];
    foreach ($items as $item) {
      $filtered = $this->wp->applyFilters('mailpoet_api_newsletters_listing_item', $item);
      $filteredItems[] = is_array($filtered) ? $filtered : $item;
    }
    return $filteredItems;
  }

  protected function getDefaultSortBy(): string {
    return 'updated_at';
  }

  protected function getDefaultSortOrder(): string {
    return 'desc';
  }

  protected function getDefaultGroup(): ?string {
    return 'all';
  }

  protected function getRequestParameters(Request $request): array {
    $params = [];
    $typeParam = $request->getParam('type');
    if (is_string($typeParam) && in_array($typeParam, self::SUPPORTED_TYPES, true)) {
      $params['type'] = $typeParam;
    }
    $parentIdParam = $request->getParam('parent_id');
    if (is_numeric($parentIdParam)) {
      $params['parentId'] = (int)$parentIdParam;
    }
    return $params;
  }
}
