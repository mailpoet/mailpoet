<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Carbon\Carbon;
use MailPoet\Config\Renderer;
use MailPoet\Cron\Workers\SimpleWorker;
use MailPoet\Features\FeaturesController;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\ScheduledTask;
use MailPoet\Settings\SettingsController;
use MailPoet\WooCommerce\Helper as WCHelper;
use MailPoet\WP\Functions as WPFunctions;

if (!defined('ABSPATH')) exit;

class AutomatedEmails extends SimpleWorker {
  const TASK_TYPE = 'stats_notification_automated_emails';

  /** @var \MailPoet\Mailer\Mailer */
  private $mailer;

  /** @var SettingsController */
  private $settings;

  /** @var Renderer */
  private $renderer;

  /** @var WCHelper */
  private $woocommerce_helper;

  /** @var float */
  public $timer;

  function __construct(
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings,
    WCHelper $woocommerce_helper,
    $timer = false
  ) {
    parent::__construct($timer);
    $this->mailer = $mailer;
    $this->settings = $settings;
    $this->renderer = $renderer;
    $this->woocommerce_helper = $woocommerce_helper;
    $this->timer = $timer ?: microtime(true);
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
        $this->mailer->send($this->constructNewsletter($newsletters), $settings['address']);
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
    $newsletters = Newsletter
      ::whereNull('deleted_at')
      ->whereIn('type', [Newsletter::TYPE_AUTOMATIC, Newsletter::TYPE_WELCOME])
      ->where('status', Newsletter::STATUS_ACTIVE)
      ->orderByAsc('subject')
      ->findMany();
    foreach ($newsletters as $newsletter) {
      $newsletter
        ->withSendingQueue()
        ->withTotalSent()
        ->withStatistics($this->woocommerce_helper, new FeaturesController()); // TODO this is here temporarily until https://github.com/mailpoet/mailpoet/pull/2157 is merged after that we can remove the second argument
    }
    $result = [];
    foreach ($newsletters as $newsletter) {
      if ($newsletter->total_sent) {
        $result[] = $newsletter;
      }
    }
    return $result;
  }

  /**
   * @param Newsletter[] $newsletters
   * @return array
   */
  private function prepareContext(array $newsletters) {
    $context = [
      'linkSettings' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-settings#basics'),
      'newsletters' => [],
    ];
    foreach ($newsletters as $newsletter) {
      $clicked = ($newsletter->statistics['clicked'] * 100) / $newsletter->total_sent;
      $opened = ($newsletter->statistics['opened'] * 100) / $newsletter->total_sent;
      $context['newsletters'][] = [
        'linkStats' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-newsletters#/stats/' . $newsletter->id),
        'clicked' => $clicked,
        'opened' => $opened,
        'subject' => $newsletter->subject,
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
