<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Config\AccessControl;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsBounceEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class RecentNewslettersAbility implements Ability {
  public const NAME = 'mailpoet/list-recent-newsletters';

  private const DEFAULT_LIMIT = 10;
  private const MAX_LIMIT = 20;

  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterStatisticsRepository */
  private $statisticsRepository;

  /** @var StatisticsUnsubscribesRepository */
  private $unsubscribesRepository;

  /** @var CapabilitiesManager */
  private $capabilitiesManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    NewsletterStatisticsRepository $statisticsRepository,
    StatisticsUnsubscribesRepository $unsubscribesRepository,
    CapabilitiesManager $capabilitiesManager,
    WPFunctions $wp
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->statisticsRepository = $statisticsRepository;
    $this->unsubscribesRepository = $unsubscribesRepository;
    $this->capabilitiesManager = $capabilitiesManager;
    $this->wp = $wp;
  }

  public function getName(): string {
    return self::NAME;
  }

  public function getRegistrationArgs(): array {
    return [
      'label' => __('List recent MailPoet newsletters', 'mailpoet'),
      'description' => __('The most recently sent MailPoet newsletters (one-off campaigns, not automations or post notifications), newest first, with the summary statistics of the MailPoet campaign listing. Rates are percentages of tracked_sent. Judge performance by clicks and revenue first: opens are inflated by email clients that load images automatically (e.g. Apple Mail Privacy Protection), counted in machine_opens. Contains no subscriber data.', 'mailpoet'),
      'input_schema' => [
        'type' => 'object',
        'properties' => [
          'limit' => [
            'type' => 'integer',
            'minimum' => 1,
            'maximum' => self::MAX_LIMIT,
            'default' => self::DEFAULT_LIMIT,
            'description' => __('How many newsletters to return.', 'mailpoet'),
          ],
        ],
        'additionalProperties' => false,
        'default' => ['limit' => self::DEFAULT_LIMIT],
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
    $limit = (int)($input['limit'] ?? self::DEFAULT_LIMIT);
    $detailedStatisticsAvailable = $this->isDetailedStatisticsAvailable();
    $newsletters = $this->newslettersRepository->findLatestSentStandard($limit);
    $statistics = $this->statisticsRepository->getBatchStatistics($newsletters, null, null, [
      'totals',
      StatisticsClickEntity::class,
      StatisticsOpenEntity::class,
      StatisticsUnsubscribeEntity::class,
      StatisticsBounceEntity::class,
      WooCommerceRevenue::class,
    ]);

    $items = array_map(function (NewsletterEntity $newsletter) use ($statistics, $detailedStatisticsAvailable): array {
      $newsletterStatistics = $statistics[$newsletter->getId()];
      $sentAt = $newsletter->getSentAt();
      $trackedSent = $newsletterStatistics->getTrackedSentCount();
      $revenue = $newsletterStatistics->getWooCommerceRevenue();
      // Same order, rates and revenue attribution as the MailPoet campaign listing.
      $item = [
        'id' => (int)$newsletter->getId(),
        'subject' => $newsletter->getSubject(),
        'sent_at' => $sentAt ? $sentAt->format('c') : null,
        'stats_url' => $this->wp->adminUrl('admin.php?page=mailpoet-newsletters#/stats/' . $newsletter->getId()),
        'total_sent' => $newsletterStatistics->getTotalSentCount(),
        'tracked_sent' => $trackedSent,
        'clicks' => $newsletterStatistics->getClickCount(),
        'click_rate' => $this->getRate($newsletterStatistics->getClickCount(), $trackedSent),
        'revenue' => $revenue ? [
          'value' => (float)$revenue->getValue(),
          'currency' => (string)$revenue->getCurrency(),
          'orders' => (int)$revenue->getOrdersCount(),
        ] : null,
        'opens' => $newsletterStatistics->getOpenCount(),
        'open_rate' => $this->getRate($newsletterStatistics->getOpenCount(), $trackedSent),
        'machine_opens' => $this->statisticsRepository->getStatisticsMachineOpenCount($newsletter),
        'unsubscribes' => $newsletterStatistics->getUnsubscribeCount(),
        'bounces' => $newsletterStatistics->getBounceCount(),
      ];
      if ($detailedStatisticsAvailable) {
        $item['unsubscribe_reasons'] = $this->getUnsubscribeReasons($newsletter);
      }
      return $item;
    }, $newsletters);

    return [
      'newsletters' => $items,
      'detailed_statistics_available' => $detailedStatisticsAvailable,
    ];
  }

  private function getRate(int $count, int $trackedSent): ?float {
    return $trackedSent > 0 ? round($count * 100 / $trackedSent, 1) : null;
  }

  private function getUnsubscribeReasons(NewsletterEntity $newsletter): array {
    $reasons = array_fill_keys(
      array_merge(StatisticsUnsubscribeEntity::REASONS, [StatisticsUnsubscribeEntity::REASON_UNSPECIFIED]),
      0
    );
    foreach ($this->unsubscribesRepository->getReasonCountsForNewsletter($newsletter) as $row) {
      $reasons[(string)$row['reason']] = (int)$row['count'];
    }
    return $reasons;
  }

  private function isDetailedStatisticsAvailable(): bool {
    $capability = $this->capabilitiesManager->getCapability('detailedAnalytics');
    return $capability !== null && !$capability->isRestricted;
  }

  private function getOutputSchema(): array {
    return [
      'type' => 'object',
      'properties' => [
        'newsletters' => [
          'type' => 'array',
          'items' => [
            'type' => 'object',
            'properties' => [
              'id' => ['type' => 'integer'],
              'subject' => ['type' => 'string'],
              'sent_at' => ['type' => ['string', 'null'], 'format' => 'date-time'],
              'stats_url' => ['type' => 'string', 'format' => 'uri', 'description' => __('The campaign report in MailPoet.', 'mailpoet')],
              'total_sent' => ['type' => 'integer'],
              'tracked_sent' => ['type' => 'integer', 'description' => __('Recipients whose opens and clicks could be measured; the base for the rates.', 'mailpoet')],
              'clicks' => ['type' => 'integer', 'description' => __('Unique subscribers who clicked a link.', 'mailpoet')],
              'click_rate' => ['type' => ['number', 'null'], 'description' => __('Percent of tracked_sent, null when nothing was tracked.', 'mailpoet')],
              'revenue' => [
                'type' => ['object', 'null'],
                'description' => __('WooCommerce revenue attributed to the email, as in MailPoet stats. Null when WooCommerce is not active or no order was attributed to the email.', 'mailpoet'),
                'properties' => [
                  'value' => ['type' => 'number'],
                  'currency' => ['type' => 'string'],
                  'orders' => ['type' => 'integer'],
                ],
              ],
              'opens' => ['type' => 'integer', 'description' => __('Unique subscribers who opened the email.', 'mailpoet')],
              'open_rate' => ['type' => ['number', 'null'], 'description' => __('Percent of tracked_sent, null when nothing was tracked.', 'mailpoet')],
              'machine_opens' => ['type' => 'integer', 'description' => __('Opens MailPoet identified as automatic image loading rather than a person reading.', 'mailpoet')],
              'unsubscribes' => ['type' => 'integer'],
              'bounces' => ['type' => 'integer'],
              'unsubscribe_reasons' => [
                'type' => 'object',
                'additionalProperties' => ['type' => 'integer'],
                'description' => __('Counts per reason: normal (no longer interested), nosignup, inappropriate, spam, other, unspecified.', 'mailpoet'),
              ],
            ],
          ],
        ],
        'detailed_statistics_available' => [
          'type' => 'boolean',
          'description' => __('False when the plan does not include detailed analytics, so unsubscribe_reasons is left out.', 'mailpoet'),
        ],
      ],
    ];
  }
}
