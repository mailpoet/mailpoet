<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Cron\CronHelper;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterLinkEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\StatsNotificationEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Worker {

  const TASK_TYPE = 'stats_notification';
  const SETTINGS_KEY = 'stats_notifications';

  /** @var Renderer */
  private $renderer;

  /** @var \MailPoet\Mailer\Mailer */
  private $mailer;

  /** @var SettingsController */
  private $settings;

  /** @var CronHelper */
  private $cronHelper;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var StatsNotificationsRepository */
  private $repository;

  /** @var EntityManager */
  private $entityManager;

  /** @var NewsletterLinkRepository */
  private $newsletterLinkRepository;

  /** @var NewsletterStatisticsRepository */
  private $newsletterStatisticsRepository;

  /** @var SubscribersFeature */
  private $subscribersFeature;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings,
    CronHelper $cronHelper,
    MetaInfo $mailerMetaInfo,
    StatsNotificationsRepository $repository,
    NewsletterLinkRepository $newsletterLinkRepository,
    NewsletterStatisticsRepository $newsletterStatisticsRepository,
    EntityManager $entityManager,
    SubscribersFeature $subscribersFeature,
    SubscribersRepository $subscribersRepository
  ) {
    $this->renderer = $renderer;
    $this->mailer = $mailer;
    $this->settings = $settings;
    $this->cronHelper = $cronHelper;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->repository = $repository;
    $this->entityManager = $entityManager;
    $this->newsletterLinkRepository = $newsletterLinkRepository;
    $this->newsletterStatisticsRepository = $newsletterStatisticsRepository;
    $this->subscribersFeature = $subscribersFeature;
    $this->subscribersRepository = $subscribersRepository;
  }

  /** @throws \Exception */
  public function process($timer = false) {
    $timer = $timer ?: microtime(true);
    $settings = $this->settings->get(self::SETTINGS_KEY);
    // Cleanup potential orphaned task created due bug MAILPOET-3015
    $this->repository->deleteOrphanedScheduledTasks();
    foreach ($this->repository->findScheduled(Sending::RESULT_BATCH_SIZE) as $statsNotificationEntity) {
      try {
        $extraParams = [
          'meta' => $this->mailerMetaInfo->getStatsNotificationMetaInfo(),
        ];
        $this->mailer->send($this->constructNewsletter($statsNotificationEntity), $settings['address'], $extraParams);
      } catch (\Exception $e) {
        if (WP_DEBUG) {
          throw $e;
        }
      } finally {
        $task = $statsNotificationEntity->getTask();
        if ($task instanceof ScheduledTaskEntity) {
          $this->markTaskAsFinished($task);
        }
      }
      $this->cronHelper->enforceExecutionLimit($timer);
    }
  }

  private function constructNewsletter(StatsNotificationEntity $statsNotificationEntity) {
    $newsletter = $statsNotificationEntity->getNewsletter();
    if (!$newsletter instanceof NewsletterEntity) {
      throw new \RuntimeException('Missing newsletter entity for statistic notification.');
    }
    $link = $this->newsletterLinkRepository->findTopLinkForNewsletter((int)$newsletter->getId());
    $context = $this->prepareContext($newsletter, $link);
    $subject = $newsletter->getLatestQueue()->getNewsletterRenderedSubject();
    return [
      'subject' => sprintf(_x('Stats for email %s', 'title of an automatic email containing statistics (newsletter open rate, click rate, etc)', 'mailpoet'), $subject),
      'body' => [
        'html' => $this->renderer->render('emails/statsNotification.html', $context),
        'text' => $this->renderer->render('emails/statsNotification.txt', $context),
      ],
    ];
  }

  private function prepareContext(NewsletterEntity $newsletter, NewsletterLinkEntity $link = null) {
    $statistics = $this->newsletterStatisticsRepository->getStatistics($newsletter);
    $clicked = ($statistics->getClickCount() * 100) / $statistics->getTotalSentCount();
    $opened = ($statistics->getOpenCount() * 100) / $statistics->getTotalSentCount();
    $unsubscribed = ($statistics->getUnsubscribeCount() * 100) / $statistics->getTotalSentCount();
    $subject = $newsletter->getLatestQueue()->getNewsletterRenderedSubject();
    $subscribersCount = $this->subscribersRepository->getTotalSubscribers();
    $hasValidApiKey = $this->subscribersFeature->hasValidApiKey();
    $context = [
      'subject' => $subject,
      'preheader' => sprintf(_x(
        '%1$s%% opens, %2$s%% clicks, %3$s%% unsubscribes in a nutshell.', 'newsletter open rate, click rate and unsubscribe rate', 'mailpoet'),
        number_format($opened, 2),
        number_format($clicked, 2),
        number_format($unsubscribed, 2)
      ),
      'topLinkClicks' => 0,
      'linkSettings' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-settings#basics'),
      'linkStats' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-newsletters&stats=' . $newsletter->getId()),
      'clicked' => $clicked,
      'opened' => $opened,
      'subscribersLimitReached' => $this->subscribersFeature->check(),
      'hasValidApiKey' => $hasValidApiKey,
      'subscribersLimit' => $this->subscribersFeature->getSubscribersLimit(),
      'upgradeNowLink' => $hasValidApiKey ? 'https://account.mailpoet.com/upgrade' : 'https://account.mailpoet.com/?s=' . ($subscribersCount + 1),
    ];
    if ($link) {
      $context['topLinkClicks'] = $link->getTotalClicksCount();
      $mappings = self::getShortcodeLinksMapping();
      $context['topLink'] = isset($mappings[$link->getUrl()]) ? $mappings[$link->getUrl()] : $link->getUrl();
    }
    return $context;
  }

  private function markTaskAsFinished(ScheduledTaskEntity $task) {
    $task->setStatus(ScheduledTask::STATUS_COMPLETED);
    $task->setProcessedAt(new Carbon);
    $task->setScheduledAt(null);
    $this->entityManager->flush();
  }

  public static function getShortcodeLinksMapping() {
    return [
      NewsletterLink::UNSUBSCRIBE_LINK_SHORT_CODE => __('Unsubscribe link', 'mailpoet'),
      '[link:subscription_manage_url]' => __('Manage subscription link', 'mailpoet'),
      '[link:newsletter_view_in_browser_url]' => __('View in browser link', 'mailpoet'),
    ];
  }
}
