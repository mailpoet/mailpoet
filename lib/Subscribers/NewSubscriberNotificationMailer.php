<?php
namespace MailPoet\Subscribers;

use MailPoet\Config\Renderer;
use MailPoet\Mailer\Mailer;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class NewSubscriberNotificationMailer {

  const SETTINGS_KEY = 'subscriber_email_notification';

  /** @var Renderer */
  private $renderer;

  /** @var \MailPoet\Mailer\Mailer|null */
  private $mailer;

  /** @var SettingsController */
  private $settings;

  /**
   * @param \MailPoet\Mailer\Mailer|null $mailer
   * @param Renderer|null $renderer
   */
  function __construct($mailer = null, $renderer = null) {
    if ($renderer) {
      $this->renderer = $renderer;
    } else {
      $caching = !WP_DEBUG;
      $debugging = WP_DEBUG;
       $this->renderer = new Renderer($caching, $debugging);
    }
    $this->mailer = $mailer;
    $this->settings = new SettingsController();
  }

  /**
   * @param Subscriber $subscriber
   * @param Segment[] $segments
   *
   * @throws \Exception
   */
  function send(Subscriber $subscriber, array $segments) {
    $settings = $this->settings->get(NewSubscriberNotificationMailer::SETTINGS_KEY);
    if ($this->isDisabled($settings)) {
      return;
    }
    try {
      $this->getMailer()->send($this->constructNewsletter($subscriber, $segments), $settings['address']);
    } catch (\Exception $e) {
      if (WP_DEBUG) {
        throw $e;
      }
    }
  }

  public static function isDisabled($settings) {
    if (!is_array($settings)) {
      return true;
    }
    if (!isset($settings['enabled'])) {
      return true;
    }
    if (!isset($settings['address'])) {
      return true;
    }
    if (empty(trim($settings['address']))) {
      return true;
    }
    return !(bool)$settings['enabled'];
  }

  /**
   * @return Mailer
   */
  private function getMailer() {
    if (!$this->mailer) {
      $this->mailer = new Mailer();
    }
    return $this->mailer;
  }

  /**
   * @param Subscriber $subscriber
   * @param Segment[] $segments
   *
   * @return array
   * @throws \Exception
   */
  private function constructNewsletter(Subscriber $subscriber, array $segments) {
    $segment_names = $this->getSegmentNames($segments);
    $context = [
      'subscriber_email' => $subscriber->get('email'),
      'segments_names' => $segment_names,
      'link_settings' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-settings'),
      'link_premium' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-premium'),
    ];
    return [
      'subject' => sprintf(__('New subscriber to %s', 'mailpoet'), $segment_names),
      'body' => [
        'html' => $this->renderer->render('emails/newSubscriberNotification.html', $context),
        'text' => $this->renderer->render('emails/newSubscriberNotification.txt', $context),
      ],
    ];
  }

  /**
   * @param Segment[] $segments
   * @return string
   */
  private function getSegmentNames($segments) {
    $names = [];
    foreach ($segments as $segment) {
      $names[] = $segment->get('name');
    }
    return implode(', ', $names);
  }

}
