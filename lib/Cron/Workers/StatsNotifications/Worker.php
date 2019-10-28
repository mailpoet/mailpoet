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
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class Worker {

  const TASK_TYPE = 'stats_notification';
  const SETTINGS_KEY = 'stats_notifications';

  /** @var float */
  public $timer;

  /** @var Renderer */
  private $renderer;

  /** @var \MailPoet\Mailer\Mailer */
  private $mailer;

  /** @var SettingsController */
  private $settings;

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

  function __construct(
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings,
    MetaInfo $mailerMetaInfo,
    StatsNotificationsRepository $repository,
    NewsletterLinkRepository $newsletter_link_repository,
    NewsletterStatisticsRepository $newsletter_statistics_repository,
    EntityManager $entity_manager,
    $timer = false
  ) {
    $this->timer = $timer ?: microtime(true);
    $this->renderer = $renderer;
    $this->mailer = $mailer;
    $this->settings = $settings;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->repository = $repository;
    $this->entity_manager = $entity_manager;
    $this->newsletter_link_repository = $newsletter_link_repository;
    $this->newsletter_statistics_repository = $newsletter_statistics_repository;
  }

  /** @throws \Exception */
  function process() {
    $settings = $this->settings->get(self::SETTINGS_KEY);
    foreach (self::getDueTasks() as $stats_notification_entity) {
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
      CronHelper::enforceExecutionLimit($this->timer);
    }
  }

  /**
   * @return StatsNotificationEntity[]
   */
  private function getDueTasks() {
    return $this->repository->findDueTasks(Sending::RESULT_BATCH_SIZE);
  }

  private function constructNewsletter(StatsNotificationEntity $stats_notification_entity) {
    $newsletter = $stats_notification_entity->getNewsletter();
    try {
      $link = $this->newsletter_link_repository->findTopLinkForNewsletter($newsletter->getId());
    } catch (\MailPoetVendor\Doctrine\ORM\UnexpectedResultException $e) {
      $link = null;
    }
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
    $this->entity_manager->persist($task);
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
