<?php declare(strict_types = 1);

namespace MailPoet\Abilities;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsBounceEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Util\License\Features\CapabilitiesManager;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

/**
 * The summary statistics of a sent newsletter, as the MailPoet campaign listing shows them.
 * Shared by the abilities that report on newsletters (MailPoet Premium uses it too).
 */
class NewsletterReportSummary {
  /** @var NewsletterStatisticsRepository */
  private $statisticsRepository;

  /** @var StatisticsUnsubscribesRepository */
  private $unsubscribesRepository;

  /** @var CapabilitiesManager */
  private $capabilitiesManager;

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    NewsletterStatisticsRepository $statisticsRepository,
    StatisticsUnsubscribesRepository $unsubscribesRepository,
    CapabilitiesManager $capabilitiesManager,
    WPFunctions $wp
  ) {
    $this->statisticsRepository = $statisticsRepository;
    $this->unsubscribesRepository = $unsubscribesRepository;
    $this->capabilitiesManager = $capabilitiesManager;
    $this->wp = $wp;
  }

  public function isDetailedStatisticsAvailable(): bool {
    $capability = $this->capabilitiesManager->getCapability('detailedAnalytics');
    return $capability !== null && !$capability->isRestricted;
  }

  /**
   * @param NewsletterEntity[] $newsletters
   * @return array<int, array<string, mixed>>
   */
  public function getItems(array $newsletters, bool $withUnsubscribeReasons): array {
    $statistics = $this->statisticsRepository->getBatchStatistics($newsletters, null, null, [
      'totals',
      StatisticsClickEntity::class,
      StatisticsOpenEntity::class,
      StatisticsUnsubscribeEntity::class,
      StatisticsBounceEntity::class,
      WooCommerceRevenue::class,
    ]);

    return array_map(function (NewsletterEntity $newsletter) use ($statistics, $withUnsubscribeReasons): array {
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
          'value' => round((float)$revenue->getValue(), 2),
          'currency' => (string)$revenue->getCurrency(),
          'orders' => (int)$revenue->getOrdersCount(),
        ] : null,
        'opens' => $newsletterStatistics->getOpenCount(),
        'open_rate' => $this->getRate($newsletterStatistics->getOpenCount(), $trackedSent),
        'machine_opens' => $this->statisticsRepository->getStatisticsMachineOpenCount($newsletter),
        'unsubscribes' => $newsletterStatistics->getUnsubscribeCount(),
        'bounces' => $newsletterStatistics->getBounceCount(),
      ];
      if ($withUnsubscribeReasons) {
        $item['unsubscribe_reasons'] = $this->getUnsubscribeReasons($newsletter);
      }
      return $item;
    }, $newsletters);
  }

  /** @return array<string, array<string, mixed>> */
  public function getItemSchemaProperties(): array {
    return [
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
        'description' => __('WooCommerce revenue attributed to the email, as in MailPoet stats: the totals of its orders, including shipping and tax. Null when WooCommerce is not active or no order was attributed to the email.', 'mailpoet'),
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
}
