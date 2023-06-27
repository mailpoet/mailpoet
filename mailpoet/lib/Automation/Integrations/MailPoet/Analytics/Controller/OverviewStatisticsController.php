<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Analytics\Controller;

use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\MailPoet\Actions\SendEmailAction;
use MailPoet\Automation\Integrations\MailPoet\Analytics\Entities\QueryWithCompare;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\StatisticsClickEntity;
use MailPoet\Entities\StatisticsOpenEntity;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Newsletter\Statistics\WooCommerceRevenue;
use MailPoet\Newsletter\Url as NewsletterUrl;

class OverviewStatisticsController {
  /** @var NewslettersRepository */
  private $newslettersRepository;

  /** @var NewsletterStatisticsRepository */
  private $newsletterStatisticsRepository;

  /** @var NewsletterUrl */
  private $newsletterUrl;

  /** @var AutomationStorage */
  private $automationStorage;

  public function __construct(
    NewslettersRepository $newslettersRepository,
    NewsletterStatisticsRepository $newsletterStatisticsRepository,
    NewsletterUrl $newsletterUrl,
    AutomationStorage $automationStorage
  ) {
    $this->newslettersRepository = $newslettersRepository;
    $this->newsletterStatisticsRepository = $newsletterStatisticsRepository;
    $this->newsletterUrl = $newsletterUrl;
    $this->automationStorage = $automationStorage;
  }

  public function getStatisticsForAutomation(Automation $automation, QueryWithCompare $query): array {
    $currentEmails = $this->getAutomationEmailsInTimeSpan($automation, $query->getAfter(), $query->getBefore());
    $previousEmails = $this->getAutomationEmailsInTimeSpan($automation, $query->getCompareWithAfter(), $query->getCompareWithBefore());
    $data = [
      'sent' => ['current' => 0, 'previous' => 0],
      'opened' => ['current' => 0, 'previous' => 0],
      'clicked' => ['current' => 0, 'previous' => 0],
      'orders' => ['current' => 0, 'previous' => 0],
      'unsubscribed' => ['current' => 0, 'previous' => 0],
      'revenue' => ['current' => 0, 'previous' => 0],
      'emails' => [],
    ];
    if (!$currentEmails) {
      return $data;
    }

    $requiredData = [
      'totals',
      StatisticsClickEntity::class,
      StatisticsOpenEntity::class,
      WooCommerceRevenue::class,
    ];

    $currentStatistics = $this->newsletterStatisticsRepository->getBatchStatistics(
      $currentEmails,
      $query->getAfter(),
      $query->getBefore(),
      $requiredData
    );
    foreach ($currentStatistics as $newsletterId => $statistic) {
      $data['sent']['current'] += $statistic->getTotalSentCount();
      $data['opened']['current'] += $statistic->getOpenCount();
      $data['clicked']['current'] += $statistic->getClickCount();
      $data['unsubscribed']['current'] += $statistic->getUnsubscribeCount();
      $data['orders']['current'] += $statistic->getWooCommerceRevenue() ? $statistic->getWooCommerceRevenue()->getOrdersCount() : 0;
      $data['revenue']['current'] += $statistic->getWooCommerceRevenue() ? $statistic->getWooCommerceRevenue()->getValue() : 0;
      $newsletter = $this->newslettersRepository->findOneById($newsletterId);
      $data['emails'][$newsletterId]['id'] = $newsletterId;
      $data['emails'][$newsletterId]['name'] = $newsletter ? $newsletter->getSubject() : '';
      $data['emails'][$newsletterId]['sent'] = $statistic->getTotalSentCount();
      $data['emails'][$newsletterId]['opened'] = $statistic->getOpenCount();
      $data['emails'][$newsletterId]['clicked'] = $statistic->getClickCount();
      $data['emails'][$newsletterId]['unsubscribed'] = $statistic->getUnsubscribeCount();
      $data['emails'][$newsletterId]['orders'] = $statistic->getWooCommerceRevenue() ? $statistic->getWooCommerceRevenue()->getOrdersCount() : 0;
      $data['emails'][$newsletterId]['revenue'] = $statistic->getWooCommerceRevenue() ? $statistic->getWooCommerceRevenue()->getValue() : 0;
      $data['emails'][$newsletterId]['previewUrl'] = $newsletter ? $this->newsletterUrl->getViewInBrowserUrl($newsletter) : '';
      $data['emails'][$newsletterId]['order'] = count($data['emails']);
    }

    $previousStatistics = $this->newsletterStatisticsRepository->getBatchStatistics(
      $previousEmails,
      $query->getCompareWithAfter(),
      $query->getCompareWithBefore(),
      $requiredData
    );

    foreach ($previousStatistics as $statistic) {
      $data['sent']['previous'] += $statistic->getTotalSentCount();
      $data['opened']['previous'] += $statistic->getOpenCount();
      $data['clicked']['previous'] += $statistic->getClickCount();
      $data['unsubscribed']['previous'] += $statistic->getUnsubscribeCount();
      $data['orders']['previous'] += $statistic->getWooCommerceRevenue() ? $statistic->getWooCommerceRevenue()->getOrdersCount() : 0;
      $data['revenue']['previous'] += $statistic->getWooCommerceRevenue() ? $statistic->getWooCommerceRevenue()->getValue() : 0;
    }

    usort($data['emails'], function ($a, $b) {
      return $a['order'] <=> $b['order'];
    });

    return $data;
  }

  private function getAutomationEmailsInTimeSpan(Automation $automation, \DateTimeImmutable $after, \DateTimeImmutable $before): array {
    $automationVersions = $this->automationStorage->getAutomationVersionDates($automation->getId());
    usort(
      $automationVersions,
      function (array $a, array $b) {
        return $a['created_at'] <=> $b['created_at'];
      }
    );

    // filter automations that were created before the after date
    $versionIds = [];
    foreach ($automationVersions as $automationVersion) {
      if ($automationVersion['created_at'] > $before) {
        break;
      }
      if (!$versionIds || $automationVersion['created_at'] < $after) {
        $versionIds = [(int)$automationVersion['id']];
        continue;
      }
      $versionIds[] = (int)$automationVersion['id'];
    }

    $automations = $this->automationStorage->getAutomationWithDifferentVersions($versionIds);
    return $this->getEmailsFromAutomations($automations);

  }

  /**
   * @param Automation[] $automations
   * @return NewsletterEntity[]
   */
  private function getEmailsFromAutomations(array $automations): array {
    $emailSteps = [];
    foreach ($automations as $automation) {
      $emailSteps = array_merge(
        $emailSteps,
        array_values(
          array_filter(
            $automation->getSteps(),
            function($step) {
              return $step->getKey() === SendEmailAction::KEY;
            }
          )
        )
      );
    }
    $emailIds = array_unique(
      array_filter(
        array_map(
          function($step) {
            return $step->getArgs()['email_id'];
          },
          $emailSteps
        )
      )
    );

    return $this->newslettersRepository->findBy(['id' => $emailIds]);
  }
}
