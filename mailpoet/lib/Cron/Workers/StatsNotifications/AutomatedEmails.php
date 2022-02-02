<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Newsletter;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

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

  /** @var NewslettersRepository */
  private $repository;

  /** @var NewsletterStatisticsRepository */
  private $newsletterStatisticsRepository;

  /** @var TrackingConfig */
  private $trackingConfig;

  public function __construct(
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings,
    NewslettersRepository $repository,
    NewsletterStatisticsRepository $newsletterStatisticsRepository,
    MetaInfo $mailerMetaInfo,
    TrackingConfig $trackingConfig
  ) {
    parent::__construct();
    $this->mailer = $mailer;
    $this->settings = $settings;
    $this->renderer = $renderer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->repository = $repository;
    $this->newsletterStatisticsRepository = $newsletterStatisticsRepository;
    $this->trackingConfig = $trackingConfig;
  }

  public function checkProcessingRequirements() {
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
    if (!$this->trackingConfig->isEmailTrackingEnabled()) {
      return false;
    }
    return (bool)$settings['automated'];
  }

  public function processTaskStrategy(ScheduledTaskEntity $task, $timer) {
    try {
      $settings = $this->settings->get(Worker::SETTINGS_KEY);
      $newsletters = $this->getNewsletters();
      if ($newsletters) {
        $extraParams = [
          'meta' => $this->mailerMetaInfo->getStatsNotificationMetaInfo(),
        ];
        $this->mailer->send($this->constructNewsletter($newsletters), $settings['address'], $extraParams);
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
      $statistics = $this->newsletterStatisticsRepository->getStatistics($newsletter);
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
      $machineOpened = ($statistics->getMachineOpenCount() * 100) / $statistics->getTotalSentCount();
      $context['newsletters'][] = [
        'linkStats' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-newsletters#/stats/' . $newsletter->getId()),
        'clicked' => $clicked,
        'opened' => $opened,
        'machineOpened' => $machineOpened,
        'subject' => $newsletter->getSubject(),
      ];
    }
    return $context;
  }

  public function getNextRunDate() {
    $wp = new WPFunctions;
    $date = Carbon::createFromTimestamp($wp->currentTime('timestamp'));
    return $date->endOfMonth()->next(Carbon::MONDAY)->midDay();
  }
}
