<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

if (!defined('ABSPATH')) exit;

class UnsubscribeTrendAbility implements Ability {
  public const NAME = 'mailpoet/get-unsubscribe-trend';

  private const DEFAULT_MONTHS = 6;
  private const MAX_MONTHS = 12;
  private const UNKNOWN_SOURCE = 'unknown';
  private const SOURCES = [
    StatisticsUnsubscribeEntity::SOURCE_NEWSLETTER,
    StatisticsUnsubscribeEntity::SOURCE_MANAGE,
    StatisticsUnsubscribeEntity::SOURCE_ADMINISTRATOR,
    StatisticsUnsubscribeEntity::SOURCE_ORDER_CHECKOUT,
    StatisticsUnsubscribeEntity::SOURCE_AUTOMATION,
    StatisticsUnsubscribeEntity::SOURCE_MP_API,
    self::UNKNOWN_SOURCE,
  ];
  private const METHODS = [
    StatisticsUnsubscribeEntity::METHOD_LINK,
    StatisticsUnsubscribeEntity::METHOD_ONE_CLICK,
    StatisticsUnsubscribeEntity::METHOD_UNKNOWN,
  ];

  /** @var StatisticsUnsubscribesRepository */
  private $unsubscribesRepository;

  /** @var CapabilitiesManager */
  private $capabilitiesManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    StatisticsUnsubscribesRepository $unsubscribesRepository,
    CapabilitiesManager $capabilitiesManager,
    WPFunctions $wp
  ) {
    $this->unsubscribesRepository = $unsubscribesRepository;
    $this->capabilitiesManager = $capabilitiesManager;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('Get MailPoet unsubscribe trend', 'mailpoet'),
      'description' => __('Monthly unsubscribe counts for all MailPoet emails. On plans with detailed analytics, each month is also split by how people unsubscribed (by_method), where the unsubscribe came from (by_source) and the reason given (by_reason). by_method: link (the unsubscribe link in an email), one_click (the unsubscribe button of the email client, List-Unsubscribe), or unknown (not unsubscribed from an email, e.g. by an admin or through the API, and records from before methods were tracked). by_source: newsletter (from an email), manage (the subscription management page), admin (an administrator unsubscribed them), order_checkout (unchecked at WooCommerce checkout), automation, mp_api (another plugin through the MailPoet API), or unknown. Counts are raw numbers, not rates. Use it to spot rising unsubscribes and to tell subscribers leaving because of emails from unsubscribes made by the site. Months are calendar months in UTC, oldest first, ending with the current month. by_method, by_source and by_reason are detailed statistics, included only on plans with detailed analytics; detailed_statistics_available tells whether they are, otherwise each month has only its total. Reasons are optional and one-click unsubscribes never include one, so a high unspecified count is normal.', 'mailpoet'),
      'input_schema' => [
        'type' => 'object',
        'properties' => [
          'months' => [
            'type' => 'integer',
            'minimum' => 1,
            'maximum' => self::MAX_MONTHS,
            'default' => self::DEFAULT_MONTHS,
            'description' => __('Number of calendar months to return, including the current one.', 'mailpoet'),
          ],
        ],
        'additionalProperties' => false,
        'default' => ['months' => self::DEFAULT_MONTHS],
      ],
      'output_schema' => $this->getOutputSchema(),
      'execute_callback' => [$this, 'execute'],
      'permission_callback' => [$this, 'hasPermission'],
      'meta' => AbilitiesRegistrar::getReadOnlyMeta(),
    ];
  }

  public function hasPermission(): bool {
    return $this->wp->currentUserCan(AccessControl::PERMISSION_MANAGE_EMAILS);
  }

  /** @param array|null $input */
  public function execute($input = null): array {
    $months = (int)($input['months'] ?? self::DEFAULT_MONTHS);
    $from = Carbon::now()->startOfMonth()->subMonths($months - 1);
    // The breakdowns are detailed statistics, which are part of paid plans.
    $detailed = $this->isDetailedStatisticsAvailable();

    $result = [];
    for ($month = $from->copy(); $month->lessThanOrEqualTo(Carbon::now()); $month->addMonth()) {
      $item = [
        'month' => $month->format('Y-m'),
        'total' => 0,
      ];
      if ($detailed) {
        $item['by_method'] = array_fill_keys(self::METHODS, 0);
        $item['by_source'] = array_fill_keys(self::SOURCES, 0);
        $item['by_reason'] = array_fill_keys(
          array_merge(StatisticsUnsubscribeEntity::REASONS, [StatisticsUnsubscribeEntity::REASON_UNSPECIFIED]),
          0
        );
      }
      $result[$item['month']] = $item;
    }

    foreach ($this->unsubscribesRepository->getMonthlyCountsPerMethod($from) as $row) {
      if (!isset($result[$row['month']])) {
        continue;
      }
      $result[$row['month']]['total'] += $row['count'];
      if ($detailed) {
        $result[$row['month']]['by_method'][$row['method']] = $row['count'];
      }
    }

    if ($detailed) {
      foreach ($this->unsubscribesRepository->getMonthlyCountsPerSource($from) as $row) {
        if (isset($result[$row['month']])) {
          $result[$row['month']]['by_source'][$row['source']] = $row['count'];
        }
      }
      foreach ($this->unsubscribesRepository->getMonthlyCountsPerReason($from) as $row) {
        if (isset($result[$row['month']])) {
          $result[$row['month']]['by_reason'][$row['reason']] = $row['count'];
        }
      }
    }

    return [
      'months' => array_values($result),
      'detailed_statistics_available' => $detailed,
    ];
  }

  private function isDetailedStatisticsAvailable(): bool {
    $capability = $this->capabilitiesManager->getCapability('detailedAnalytics');
    return $capability !== null && !$capability->isRestricted;
  }

  private function getOutputSchema(): array {
    $counts = [
      'type' => 'object',
      'additionalProperties' => ['type' => 'integer'],
    ];
    return [
      'type' => 'object',
      'properties' => [
        'months' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'month' => ['type' => 'string', 'description' => __('Calendar month as YYYY-MM.', 'mailpoet')],
              'total' => ['type' => 'integer'],
              'by_method' => $counts,
              'by_source' => $counts,
              'by_reason' => $counts + [
                'description' => __('Counts per reason: normal (no longer interested), nosignup (did not sign up), inappropriate (inappropriate content), spam, other, unspecified (no reason given).', 'mailpoet'),
              ],
            ],
          ],
        ],
        'detailed_statistics_available' => [
          'type' => 'boolean',
          'description' => __('False when the plan does not include detailed analytics, so by_method, by_source and by_reason are left out.', 'mailpoet'),
        ],
      ],
    ];
  }
}
