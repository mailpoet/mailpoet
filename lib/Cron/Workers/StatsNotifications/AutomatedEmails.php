<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Carbon\Carbon;
use MailPoet\Config\Renderer;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class AutomatedEmails extends SimpleWorker {
  const TASK_TYPE = 'stats_notification_automated_emails';

  /** @var \MailPoet\Mailer\Mailer */
  private $mailer;

  /** @var SettingsController */
  private $settings;

  /** @var Renderer */
  private $renderer;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  /** @var float */
  public $timer;

  /** @var NewslettersRepository */
  private $repository;

  /** @var NewsletterStatisticsRepository */
  private $newsletter_statistics_repository;

  function __construct(
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings,
    NewslettersRepository $repository,
    NewsletterStatisticsRepository $newsletter_statistics_repository,
    MetaInfo $mailerMetaInfo,
    $timer = false
  ) {
    parent::__construct($timer);
    $this->mailer = $mailer;
    $this->settings = $settings;
    $this->renderer = $renderer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->timer = $timer ?: microtime(true);
    $this->repository = $repository;
    $this->newsletter_statistics_repository = $newsletter_statistics_repository;
  }

  function checkProcessingRequirements() {
    $settings = $this->settings->get(Worker::SETTINGS_KEY);
    if (!is_array($settings)) {
      return false;
    }
    if (!isset($settings['automated'])) {
      return false;
    }
    if (!isset($settings['address'])) {
      return false;
    }
    if (empty(trim($settings['address']))) {
      return false;
    }
    if (!(bool)$this->settings->get('tracking.enabled')) {
      return false;
    }
    return (bool)$settings['automated'];
  }

  function processTaskStrategy(ScheduledTask $task) {
    try {
      $settings = $this->settings->get(Worker::SETTINGS_KEY);
      $newsletters = $this->getNewsletters();
      if ($newsletters) {
        $extra_params = [
          'meta' => $this->mailerMetaInfo->getStatsNotificationMetaInfo(),
        ];
        $this->mailer->send($this->constructNewsletter($newsletters), $settings['address'], $extra_params);
      }
    } catch (\Exception $e) {
      if (WP_DEBUG) {
        throw $e;
      }
    }
    return true;
  }

  /**
   * @param Newsletter[] $newsletters
   * @return array
   * @throws \Exception
   */
  private function constructNewsletter($newsletters) {
    $context = $this->prepareContext($newsletters);
    return [
      'subject' => __('Your monthly stats are in!', 'mailpoet'),
      'body' => [
        'html' => $this->renderer->render('emails/statsNotificationAutomatedEmails.html', $context),
        'text' => $this->renderer->render('emails/statsNotificationAutomatedEmails.txt', $context),
      ],
    ];
  }

  protected function getNewsletters() {
    $result = [];
    $newsletters = $this->repository->findActiveByTypes(
      [NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::TYPE_WELCOME]
    );
    foreach ($newsletters as $newsletter) {
      $statistics = $this->newsletter_statistics_repository->getStatistics($newsletter);
      if ($statistics->getTotalSentCount()) {
        $result[] = [
          'statistics' => $statistics,
          'newsletter' => $newsletter,
        ];
      }
    }
    return $result;
  }

  private function prepareContext(array $newsletters) {
    $context = [
      'linkSettings' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-settings#basics'),
      'newsletters' => [],
    ];
    foreach ($newsletters as $row) {
      /** @var NewsletterStatistics $statistics */
      $statistics = $row['statistics'];
      /** @var NewsletterEntity $newsletter */
      $newsletter = $row['newsletter'];
      $clicked = ($statistics->getClickCount() * 100) / $statistics->getTotalSentCount();
      $opened = ($statistics->getOpenCount() * 100) / $statistics->getTotalSentCount();
      $context['newsletters'][] = [
        'linkStats' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-newsletters#/stats/' . $newsletter->getId()),
        'clicked' => $clicked,
        'opened' => $opened,
        'subject' => $newsletter->getSubject(),
      ];
    }
    return $context;
  }

  static function getNextRunDate() {
    $wp = new WPFunctions;
    $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    return $date->endOfMonth()->next(Carbon::MONDAY)->midDay();
  }
}
