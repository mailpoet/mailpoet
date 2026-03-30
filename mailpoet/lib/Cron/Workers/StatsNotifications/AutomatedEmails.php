<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Mailer\MailerFactory;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\Newsletter\Statistics\NewsletterStatistics;
use MailPoet\Newsletter\Statistics\NewsletterStatisticsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\TrackingConfig;
use MailPoet\WP\DateTime as WpDateTime;
use MailPoet\WP\Functions as WPFunctions;
use MailPoet\WPCOM\DotcomHelperFunctions;
use MailPoetVendor\Carbon\Carbon;

class AutomatedEmails extends SimpleWorker {
  const TASK_TYPE = 'stats_notification_automated_emails';

  /** @var MailerFactory */
  private $mailerFactory;

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

  /** @var WpDateTime */
  private $wpDateTime;

  /** @var DotcomHelperFunctions */
  private $dotcomHelperFunctions;

  public function __construct(
    MailerFactory $mailerFactory,
    Renderer $renderer,
    SettingsController $settings,
    NewslettersRepository $repository,
    NewsletterStatisticsRepository $newsletterStatisticsRepository,
    MetaInfo $mailerMetaInfo,
    TrackingConfig $trackingConfig,
    DotcomHelperFunctions $dotcomHelperFunctions
  ) {
    parent::__construct();
    $this->mailerFactory = $mailerFactory;
    $this->settings = $settings;
    $this->renderer = $renderer;
    $this->mailerMetaInfo = $mailerMetaInfo;
    $this->repository = $repository;
    $this->newsletterStatisticsRepository = $newsletterStatisticsRepository;
    $this->trackingConfig = $trackingConfig;
    $this->dotcomHelperFunctions = $dotcomHelperFunctions;
    $this->wpDateTime = new WpDateTime();
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
        $this->mailerFactory->getDefaultMailer()->send($this->constructNewsletter($newsletters, $settings), $settings['address'], $extraParams);
      }
    } catch (\Exception $e) {
      if (WP_DEBUG) {
        throw $e;
      }
    }
    return true;
  }

  /**
   * @param array<int, array{newsletter: NewsletterEntity, statistics: NewsletterStatistics}> $newsletters
   */
  private function constructNewsletter(array $newsletters, array $settings): array {
    $context = $this->prepareContext($newsletters, $settings);
    if ($this->dotcomHelperFunctions->isGarden()) {
      $subject = __('Your monthly automation stats are in!', 'mailpoet');
    } else {
      $subject = __('Your monthly stats are in!', 'mailpoet');
    }
    return [
      'subject' => $subject,
      'body' => [
        'html' => $this->renderer->render('emails/statsNotificationAutomatedEmails.html', $context),
        'text' => $this->renderer->render('emails/statsNotificationAutomatedEmails.txt', $context),
      ],
    ];
  }

  /**
   * @return array<int, array{newsletter: NewsletterEntity, statistics: NewsletterStatistics}>
   */
  protected function getNewsletters(): array {
    $result = [];
    $newsletters = $this->repository->findActiveByTypes(
      [NewsletterEntity::TYPE_AUTOMATIC, NewsletterEntity::TYPE_WELCOME, NewsletterEntity::TYPE_AUTOMATION]
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

  /**
   * @param array<int, array{newsletter: NewsletterEntity, statistics: NewsletterStatistics}> $newsletters
   * @return array
   */
  private function prepareContext(array $newsletters, array $settings = []): array {
    $context = [
      'linkSettings' => WPFunctions::get()->applyFilters(
        'mailpoet_stats_notification_link_settings',
        WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-settings#basics')
      ),
      'blogName' => WPFunctions::get()->getBloginfo('name'),
      'recipientFirstName' => $this->getRecipientFirstName($settings['address'] ?? ''),
      'newsletters' => [],
    ];
    foreach ($newsletters as $row) {
      $statistics = $row['statistics'];
      $newsletter = $row['newsletter'];
      $totalSentCount = $statistics->getTotalSentCount() ?: 1;
      $clicked = ($statistics->getClickCount() * 100) / $totalSentCount;
      $opened = ($statistics->getOpenCount() * 100) / $totalSentCount;
      $machineOpened = ($statistics->getMachineOpenCount() * 100) / $totalSentCount;
      $unsubscribed = ($statistics->getUnsubscribeCount() * 100) / $totalSentCount;
      $bounced = ($statistics->getBounceCount() * 100) / $totalSentCount;
      $context['newsletters'][] = [
        'linkStats' => WPFunctions::get()->applyFilters(
          'mailpoet_stats_notification_link_stats',
          WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-newsletters#/stats/' . $newsletter->getId()),
          $newsletter->getId()
        ),
        'clicked' => $clicked,
        'opened' => $opened,
        'machineOpened' => $machineOpened,
        'unsubscribed' => $unsubscribed,
        'bounced' => $bounced,
        'subject' => $newsletter->getSubject(),
      ];
    }
    return $context;
  }

  private function getRecipientFirstName(string $email): string {
    if (empty($email)) {
      return '';
    }
    $user = WPFunctions::get()->getUserBy('email', $email);
    if (!$user) {
      return '';
    }
    $firstName = $user->first_name; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    if (!empty($firstName)) {
      return $firstName;
    }
    $displayName = $user->display_name; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    return !empty($displayName) ? $displayName : '';
  }

  public function getNextRunDate() {
    $currentDateTime = $this->wpDateTime->getCurrentDateTime();
    $date = Carbon::instance($currentDateTime)->millisecond(0);
    // Get first Monday of next month at midnight
    $nextMonday = $date->endOfMonth()->next(Carbon::MONDAY)->startOfDay();
    // Add random time across the entire day (0-23 hours, 0-59 minutes, 0-59 seconds)
    return $nextMonday
      ->addHours(rand(0, 23))
      ->addMinutes(rand(0, 59))
      ->addSeconds(rand(0, 59));
  }
}
