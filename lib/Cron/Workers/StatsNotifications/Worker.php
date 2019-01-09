<?php

namespace MailPoet\Cron\Workers\StatsNotifications;

use MailPoet\Config\Renderer;
use MailPoet\Cron\CronHelper;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Setting;

/**
 * TODO:
 * - add processing of this task to Daemon
 * - check JIRA what to do next and how to send the newsletter
 * - see \MailPoet\Subscribers\NewSubscriberNotificationMailer how to send an email, now with DI everything should be easy
 */
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
    try {
      $this->mailer->getSenderNameAndAddress($this->constructSenderEmail());
      $this->mailer->send($this->constructNewsletter(), $settings['address']);
    } catch(\Exception $e) {
      if(WP_DEBUG) {
        throw $e;
      }
    }

    CronHelper::enforceExecutionLimit($this->timer);
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

  private function constructNewsletter() {
    $context = [
      'link_settings' => get_site_url(null, '/wp-admin/admin.php?page=mailpoet-settings'),
      'link_premium' => get_site_url(null, '/wp-admin/admin.php?page=mailpoet-premium'),
    ];
    return [
      'subject' => sprintf(__('New subscriber to ', 'mailpoet')),
      'body' => [
        'html' => $this->renderer->render('emails/newSubscriberNotification.html', $context),
        'text' => $this->renderer->render('emails/newSubscriberNotification.txt', $context),
      ],
    ];
  }

}
