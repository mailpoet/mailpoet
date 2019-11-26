<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Carbon\Carbon;
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
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoet\Util\License\Features\Subscribers as SubscribersFeature;
use MailPoet\WP\Functions as WPFunctions;
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
  private $cron_helper;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var StatsNotificationsRepository */
  private $repository;

  /** @var EntityManager */
  private $entity_manager;

  /** @var NewsletterLinkRepository */
  private $newsletter_link_repository;

  /** @var NewsletterStatisticsRepository */
  private $newsletter_statistics_repository;

  /** @var SubscribersFeature */
  private $subscribers_feature;

  /** @var SubscribersRepository */
  private $subscribers_repository;

  function __construct(
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings,
    CronHelper $cron_helper,
    MetaInfo $mailerMetaInfo,
    StatsNotificationsRepository $repository,
    NewsletterLinkRepository $newsletter_link_repository,
    NewsletterStatisticsRepository $newsletter_statistics_repository,
    EntityManager $entity_manager,
    SubscribersFeature $subscribers_feature,
    SubscribersRepository $subscribers_repository
  ) {
    $this->renderer = $renderer;
    $this->mailer = $mailer;
    $this->settings = $settings;
    $this->cron_helper = $cron_helper;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->repository = $repository;
    $this->entity_manager = $entity_manager;
    $this->newsletter_link_repository = $newsletter_link_repository;
    $this->newsletter_statistics_repository = $newsletter_statistics_repository;
    $this->subscribers_feature = $subscribers_feature;
    $this->subscribers_repository = $subscribers_repository;
  }

  /** @throws \Exception */
  function process($timer = false) {
    $timer = $timer ?: microtime(true);
    $settings = $this->settings->get(self::SETTINGS_KEY);
    foreach ($this->repository->findScheduled(Sending::RESULT_BATCH_SIZE) as $stats_notification_entity) {
      try {
        $extra_params = [
          'meta' => $this->mailerMetaInfo->getStatsNotificationMetaInfo(),
        ];
        $this->mailer->send($this->constructNewsletter($stats_notification_entity), $settings['address'], $extra_params);
      } catch (\Exception $e) {
        if (WP_DEBUG) {
          throw $e;
        }
      } finally {
        $this->markTaskAsFinished($stats_notification_entity->getTask());
      }
      $this->cron_helper->enforceExecutionLimit($timer);
    }
  }

  private function constructNewsletter(StatsNotificationEntity $stats_notification_entity) {
    $newsletter = $stats_notification_entity->getNewsletter();
    $link = $this->newsletter_link_repository->findTopLinkForNewsletter((int)$newsletter->getId());
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
    $statistics = $this->newsletter_statistics_repository->getStatistics($newsletter);
    $clicked = ($statistics->getClickCount() * 100) / $statistics->getTotalSentCount();
    $opened = ($statistics->getOpenCount() * 100) / $statistics->getTotalSentCount();
    $unsubscribed = ($statistics->getUnsubscribeCount() * 100) / $statistics->getTotalSentCount();
    $subject = $newsletter->getLatestQueue()->getNewsletterRenderedSubject();
    $subscribers_count = $this->subscribers_repository->getTotalSubscribers();
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
      'linkStats' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-newsletters#/stats/' . $newsletter->getId()),
      'clicked' => $clicked,
      'opened' => $opened,
      'subscribersLimitReached' => $this->subscribers_feature->check(),
      'subscribersLimit' => $this->subscribers_feature->getSubscribersLimit(),
      'upgradeNowLink' => 'https://account.mailpoet.com/?s=' . ($subscribers_count + 1),
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
    $this->entity_manager->flush();
  }

  public static function getShortcodeLinksMapping() {
    return [
      NewsletterLink::UNSUBSCRIBE_LINK_SHORT_CODE => __('Unsubscribe link', 'mailpoet'),
      '[link:subscription_manage_url]' => __('Manage subscription link', 'mailpoet'),
      '[link:newsletter_view_in_browser_url]' => __('View in browser link', 'mailpoet'),
    ];
  }

}
