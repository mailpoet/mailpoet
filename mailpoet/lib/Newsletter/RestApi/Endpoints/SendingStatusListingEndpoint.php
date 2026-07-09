<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\RestApi\Endpoints;

use MailPoet\API\JSON\ResponseBuilders\ScheduledTaskSubscriberResponseBuilder;
use MailPoet\API\REST\AbstractListingEndpoint;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\Config\AccessControl;
use MailPoet\Cron\CronHelper;
use MailPoet\Listing\Handler as ListingHandler;
use MailPoet\Listing\ListingDefinition;
use MailPoet\Listing\ListingRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersListingRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

/**
 * `GET /mailpoet/v1/newsletters/{id}/sending-status`
 *
 * Per-subscriber send status (Sent / Failed / Unprocessed) for a single
 * newsletter. Replaces the legacy `sending_task_subscribers` JSON listing.
 * The response shape stays 1:1 with the other DataViews-backed listings
 * (`items`, `meta`, `filters`, `groups`) and additionally carries the
 * mailer / cron envelope the page uses to surface sending notices.
 */
class SendingStatusListingEndpoint extends AbstractListingEndpoint {
  /** @var ScheduledTaskSubscribersListingRepository */
  private $taskSubscribersListingRepository;

  /** @var ScheduledTaskSubscriberResponseBuilder */
  private $responseBuilder;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    ListingHandler $listingHandler,
    ScheduledTaskSubscribersListingRepository $taskSubscribersListingRepository,
    ScheduledTaskSubscriberResponseBuilder $responseBuilder,
    SendingQueuesRepository $sendingQueuesRepository,
    SettingsController $settings,
    CronHelper $cronHelper,
    WPFunctions $wp
  ) {
    parent::__construct($listingHandler);
    $this->taskSubscribersListingRepository = $taskSubscribersListingRepository;
    $this->responseBuilder = $responseBuilder;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
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
      'id' => Builder::integer()->required(),
    ]);
  }

  protected function getListingRepository(): ListingRepository {
    return $this->taskSubscribersListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    $items = $this->responseBuilder->buildForListing($rows);
    $taskIds = array_values(array_unique(array_filter(array_column($items, 'taskId'))));
    $timezones = $this->sendingQueuesRepository->getGroupTimezonesByTaskIds($taskIds);
    foreach ($items as &$item) {
      $group = $timezones[$item['taskId']] ?? null;
      $item['timezone'] = $group ? $group['timezone'] : null;
      $item['timezoneFallbackUsed'] = $group ? $group['fallbackUsed'] : false;
    }
    return $items;
  }

  protected function getDefaultSortBy(): string {
    return 'failed';
  }

  protected function getDefaultSortOrder(): string {
    return 'desc';
  }

  protected function getDefaultGroup(): ?string {
    return 'all';
  }

  protected function getRequestParameters(Request $request): array {
    $idParam = $request->getParam('id');
    $newsletterId = is_numeric($idParam) ? (int)$idParam : 0;
    $taskIds = $newsletterId > 0
      ? $this->sendingQueuesRepository->getTaskIdsByNewsletterId($newsletterId)
      : [];
    // The repository filters task subscribers by `task IN (:taskIds)`. When the
    // newsletter has no sending tasks (never sent, or the per-subscriber
    // records were cleaned up) fall back to a sentinel that matches no rows so
    // the listing returns empty instead of every subscriber across all emails.
    return ['task_ids' => $taskIds ?: [0]];
  }
}
