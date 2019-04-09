<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Carbon\Carbon;
use MailPoet\Config\Renderer;
use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\StatsNotification;
use MailPoet\Settings\SettingsController;
use MailPoet\Tasks\Sending;
use MailPoet\WP\Functions as WPFunctions;

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

  function __construct(Mailer $mailer, Renderer $renderer, SettingsController $settings, $timer = false) {
    $this->timer = $timer ?: microtime(true);
    $this->renderer = $renderer;
    $this->mailer = $mailer;
    $this->settings = $settings;
  }

  /** @throws \Exception */
  function process() {
    $settings = $this->settings->get(self::SETTINGS_KEY);
    foreach (self::getDueTasks() as $task) {
      try {
        $this->mailer->send($this->constructNewsletter($task), $settings['address']);
      } catch (\Exception $e) {
        if (WP_DEBUG) {
          throw $e;
        }
      } finally {
        $this->markTaskAsFinished($task);
      }
      CronHelper::enforceExecutionLimit($this->timer);
    }
  }

  public static function getDueTasks() {
    $date = new Carbon();
    return ScheduledTask::orderByAsc('priority')
      ->orderByAsc('updated_at')
      ->whereNull('deleted_at')
      ->where('status', ScheduledTask::STATUS_SCHEDULED)
      ->whereLte('scheduled_at', $date)
      ->where('type', self::TASK_TYPE)
      ->limit(Sending::RESULT_BATCH_SIZE)
      ->findMany();
  }

  private function constructNewsletter(ScheduledTask $task) {
    $newsletter = $this->getNewsletter($task);
    $link = NewsletterLink::findTopLinkForNewsletter($newsletter);
    $context = $this->prepareContext($newsletter, $link);
    return [
      'subject' => sprintf(_x('Stats for email %s', 'title of an automatic email containing statistics (newsletter open rate, click rate, etc)', 'mailpoet'), $newsletter->subject),
      'body' => [
        'html' => $this->renderer->render('emails/statsNotification.html', $context),
        'text' => $this->renderer->render('emails/statsNotification.txt', $context),
      ],
    ];
  }

  private function getNewsletter(ScheduledTask $task) {
    $statsNotificationModel = $task->statsNotification()->findOne();
    if (!$statsNotificationModel instanceof StatsNotification) {
      throw new \Exception('Newsletter not found');
    }
    $newsletter = $statsNotificationModel->newsletter()->findOne();
    if (!$newsletter instanceof Newsletter) {
      throw new \Exception('Newsletter not found');
    }
    return $newsletter
      ->withSendingQueue()
      ->withTotalSent()
      ->withStatistics();
  }

  /**
   * @param Newsletter $newsletter
   * @param \stdClass|NewsletterLink $link
   * @return array
   */
  private function prepareContext(Newsletter $newsletter, $link = null) {
    $clicked = ($newsletter->statistics['clicked'] * 100) / $newsletter->total_sent;
    $opened = ($newsletter->statistics['opened'] * 100) / $newsletter->total_sent;
    $unsubscribed = ($newsletter->statistics['unsubscribed'] * 100) / $newsletter->total_sent;
    $context =  [
      'subject' => $newsletter->subject,
      'preheader' => sprintf(_x(
        '%1$s%% opens, %2$s%% clicks, %3$s%% unsubscribes in a nutshell.', 'newsletter open rate, click rate and unsubscribe rate', 'mailpoet'),
        number_format($opened, 2),
        number_format($clicked, 2),
        number_format($unsubscribed, 2)
      ),
      'topLinkClicks' => 0,
      'linkSettings' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-settings#basics'),
      'linkStats' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-newsletters#/stats/' . $newsletter->id()),
      'premiumPage' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-premium'),
      'premiumPluginActive' => WPFunctions::get()->isPluginActive('mailpoet-premium/mailpoet-premium.php'),
      'clicked' => $clicked,
      'opened' => $opened,
    ];
    if ($link) {
      $context['topLinkClicks'] = (int)$link->clicksCount;
      $context['topLink'] = $link->url;
    }
    return $context;
  }

  private function markTaskAsFinished(ScheduledTask $task) {
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->processed_at = new Carbon;
    $task->scheduled_at = null;
    $task->save();
  }

}
