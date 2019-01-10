<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use Carbon\Carbon;
use MailPoet\Config\Renderer;
use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Newsletter;
use MailPoet\Models\NewsletterLink;
use MailPoet\Models\ScheduledTask;
use MailPoet\Models\Setting;
use MailPoet\Tasks\Sending;

class Worker {

  const TASK_TYPE = 'stats_notification';
  const SETTINGS_KEY = 'stats_notifications';

  const SENDER_EMAIL_PREFIX = 'wordpress@';

  /** @var float */
  public $timer;

  /** @var Renderer */
  private $renderer;

  /** @var \MailPoet\Mailer\Mailer */
  private $mailer;

  function __construct(Mailer $mailer, Renderer $renderer, $timer = false) {
    $this->timer = $timer ?: microtime(true);
    $this->renderer = $renderer;
    $this->mailer = $mailer;
  }

  /** @throws \Exception */
  function process() {
    $settings = Setting::getValue(self::SETTINGS_KEY);
    $this->mailer->sender = $this->mailer->getSenderNameAndAddress($this->constructSenderEmail());
    foreach($this->getTasks() as $task) {
      try {
        $this->mailer->send($this->constructNewsletter($task), $settings['address']);
      } catch(\Exception $e) {
        //if(WP_DEBUG) {
          throw $e;
        //}
      } finally {
        $this->markTaskAsFinished($task);
      }
      CronHelper::enforceExecutionLimit($this->timer);
    }
  }

  private function constructSenderEmail() {
    $url_parts = parse_url(home_url());
    $site_name = strtolower($url_parts['host']);
    if(strpos($site_name, 'www.') === 0) {
      $site_name = substr($site_name, 4);
    }
    return [
      'address' => self::SENDER_EMAIL_PREFIX . $site_name,
      'name' => self::SENDER_EMAIL_PREFIX . $site_name,
    ];
  }

  private function getTasks() {
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
      ],
    ];
  }

  private function getNewsletter(ScheduledTask $task) {
    $statsNotificationModel = $task->statsNotification()->findOne();
    return $statsNotificationModel
      ->newsletter()
      ->findOne()
      ->withSendingQueue()
      ->withTotalSent()
      ->withStatistics();
  }

  private function prepareContext(Newsletter $newsletter, NewsletterLink $link) {
    return [
      'subject' => $newsletter->subject,
      'preheader' => sprintf(_x(
        '%1$s%% opens, %2$s%% clicks, %3$s%% unsubscribes in a nutshell.', 'newsletter open rate, click rate and unsubscribe rate', 'mailpoet'),
        number_format(($newsletter->statistics['clicked'] * 100) / $newsletter->total_sent, 2),
        number_format(($newsletter->statistics['opened'] * 100) / $newsletter->total_sent,2),
        number_format(($newsletter->statistics['unsubscribed'] * 100) / $newsletter->total_sent,2)
      ),
      'topLinkClicks' => $link->clicksCount,
      'topLink' => $link->url,
      'linkSettings' => get_site_url(null, '/wp-admin/admin.php?page=mailpoet-settings#basics'),
      'linkStats' => get_site_url(null, '/wp-admin/admin.php?page=mailpoet-newsletters#/stats/' . $newsletter->id()),
      'premiumPluginActive' => is_plugin_active('mailpoet-premium/mailpoet-premium.php'),
    ];
  }

  private function markTaskAsFinished(ScheduledTask $task) {
    $task->status = ScheduledTask::STATUS_COMPLETED;
    $task->processed_at = new Carbon;
    $task->scheduled_at = null;
    $task->save();
  }

}
