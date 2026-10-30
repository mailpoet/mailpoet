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
      'description' => __('Monthly unsubscribe counts for all MailPoet emails, oldest first, in UTC calendar months ending with the current one. Counts are raw numbers, not rates. Use it to spot rising unsubscribes and to tell people leaving because of emails from unsubscribes made by the site. With detailed analytics (detailed_statistics_available), each month is also split by_method, by_source and by_reason; otherwise it has only its total.', 'mailpoet'),
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

    if (!$detailed) {
      foreach ($this->unsubscribesRepository->getMonthlyCountsPerMethod($from) as $row) {
        if (isset($result[$row['month']])) {
          $result[$row['month']]['total'] += $row['count'];
        }
      }
    } else {
      // One scan for all breakdowns; the table has no index on created_at.
      foreach ($this->unsubscribesRepository->getMonthlyCountsPerMethodSourceAndReason($from) as $row) {
        $month = $row['month'];
        if (!isset($result[$month])) {
          continue;
        }
        $result[$month]['total'] += $row['count'];
        foreach (['method' => 'by_method', 'source' => 'by_source', 'reason' => 'by_reason'] as $column => $key) {
          $result[$month][$key][$row[$column]] = ($result[$month][$key][$row[$column]] ?? 0) + $row['count'];
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
              'by_method' => $counts + [
                'description' => __('Counts per method: link (the unsubscribe link in an email), one_click (the unsubscribe button of the email client), unknown (not from an email, e.g. by an admin or through the API, or from before methods were tracked).', 'mailpoet'),
              ],
              'by_source' => $counts + [
                'description' => __('Counts per source: newsletter (from an email), manage (the subscription management page), admin (an administrator), order_checkout (unchecked at WooCommerce checkout), automation, mp_api (another plugin through the MailPoet API), unknown.', 'mailpoet'),
              ],
              'by_reason' => $counts + [
                'description' => __('Counts per reason: normal (no longer interested), nosignup (did not sign up), inappropriate (inappropriate content), spam, other, unspecified (no reason given). Reasons are optional and one-click unsubscribes never have one, so a high unspecified count is normal.', 'mailpoet'),
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
