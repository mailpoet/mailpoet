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
use MailPoet\Newsletter\Sending\ScheduledTaskQueuedSubscribersListingRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersListingRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Validator\Builder;
use MailPoet\WP\Functions as WPFunctions;

/**
 * `GET /mailpoet/v1/newsletters/{id}/sending-status`
 *
 * Per-subscriber send status for a single newsletter, surfaced as three tabs:
 * **Unprocessed** (pending recipients, read from the lean queue table) and
 * **Sent** / **Failed** (read from the processed log). The `group` request
 * param selects the tab and routes to the matching single-table repository —
 * there is no cross-table query. The response shape stays 1:1 with the other
 * DataViews-backed listings (`items`, `meta`, `filters`, `groups`) and
 * additionally carries the mailer / cron envelope the page uses to surface
 * sending notices.
 */
class SendingStatusListingEndpoint extends AbstractListingEndpoint {
  private const GROUP_UNPROCESSED = 'unprocessed';
  private const DEFAULT_GROUP = 'sent';

  /** @var ListingHandler */
  private $listingHandler;

  /** @var ScheduledTaskSubscribersListingRepository */
  private $taskSubscribersListingRepository;

  /** @var ScheduledTaskQueuedSubscribersListingRepository */
  private $queuedSubscribersListingRepository;

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

  /**
   * The tab the current request targets, resolved at the top of `handle()` and
   * consumed synchronously within the same call by `getListingRepository()` —
   * the one extension point the base class calls without the request or the
   * listing definition. Everywhere else (e.g. `buildItems()`) reads the group
   * off the `ListingDefinition`. A WordPress REST request is handled in a
   * single-threaded PHP process, so this per-request value is never shared
   * across requests.
   *
   * @var string
   */
  private $activeGroup = self::DEFAULT_GROUP;

  public function __construct(
    ListingHandler $listingHandler,
    ScheduledTaskSubscribersListingRepository $taskSubscribersListingRepository,
    ScheduledTaskQueuedSubscribersListingRepository $queuedSubscribersListingRepository,
    ScheduledTaskSubscriberResponseBuilder $responseBuilder,
    SendingQueuesRepository $sendingQueuesRepository,
    SettingsController $settings,
    CronHelper $cronHelper,
    WPFunctions $wp
  ) {
    parent::__construct($listingHandler);
    $this->listingHandler = $listingHandler;
    $this->taskSubscribersListingRepository = $taskSubscribersListingRepository;
    $this->queuedSubscribersListingRepository = $queuedSubscribersListingRepository;
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
    $this->activeGroup = $this->resolveGroup($request);

    /** @var Response $response */
    $response = parent::handle($request);
    $payload = $response->get_data();
    if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
      // Every tab shows the full chip set with counts, regardless of which tab
      // is active. Each count is its own cheap single-table query.
      $payload['data']['groups'] = $this->buildGroups($this->getTaskIds($request));
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
    return $this->activeGroup === self::GROUP_UNPROCESSED
      ? $this->queuedSubscribersListingRepository
      : $this->taskSubscribersListingRepository;
  }

  protected function buildItems(array $rows, ListingDefinition $definition): array {
    return $definition->getGroup() === self::GROUP_UNPROCESSED
      ? $this->responseBuilder->buildForQueuedListing($rows)
      : $this->responseBuilder->buildForListing($rows);
  }

  protected function getDefaultSortBy(): string {
    return 'subscriberId';
  }

  protected function getDefaultSortOrder(): string {
    return 'asc';
  }

  protected function getDefaultGroup(): ?string {
    return self::DEFAULT_GROUP;
  }

  protected function getRequestParameters(Request $request): array {
    return ['task_ids' => $this->getTaskIds($request)];
  }

  private function resolveGroup(Request $request): string {
    $groupParam = $request->getParam('group');
    return is_string($groupParam) && $groupParam !== '' ? $groupParam : self::DEFAULT_GROUP;
  }

  /**
   * @return int[]
   */
  private function getTaskIds(Request $request): array {
    $idParam = $request->getParam('id');
    $newsletterId = is_numeric($idParam) ? (int)$idParam : 0;
    $taskIds = $newsletterId > 0
      ? $this->sendingQueuesRepository->getTaskIdsByNewsletterId($newsletterId)
      : [];
    // The repositories filter by `task IN (:taskIds)`. When the newsletter has
    // no sending tasks (never sent, or the per-subscriber records were cleaned
    // up) fall back to a sentinel that matches no rows so the listing returns
    // empty instead of every subscriber across all emails.
    return $taskIds ?: [0];
  }

  /**
   * @param int[] $taskIds
   * @return array<int, array{name: string, label: string, count: int}>
   */
  private function buildGroups(array $taskIds): array {
    $definition = $this->listingHandler->getListingDefinition(['params' => ['task_ids' => $taskIds]]);
    return array_merge(
      $this->queuedSubscribersListingRepository->getGroups($definition),
      $this->taskSubscribersListingRepository->getGroups($definition)
    );
  }
}
