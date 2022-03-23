<?php

namespace MailPoet\Subscribers;

use MailPoet\Config\Renderer;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Mailer\MetaInfo;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class NewSubscriberNotificationMailer {

  const SETTINGS_KEY = 'subscriber_email_notification';

  /** @var Mailer */
  private $mailer;

  /** @var Renderer */
  private $renderer;

  /** @var SettingsController */
  private $settings;

  /** @var MetaInfo */
  private $mailerMetaInfo;

  public function __construct(
    Mailer $mailer,
    Renderer $renderer,
    SettingsController $settings
  ) {
    $this->mailer = $mailer;
    $this->renderer = $renderer;
    $this->settings = $settings;
    $this->mailerMetaInfo = new MetaInfo();
  }

  /**
   * This method can be removed and code calling it can be updated to call self::send()
   * once self::send() is migrated to use Doctrine instead of Paris.
   *
   * @param SegmentEntity[] $segments
   */
  public function sendWithSubscriberAndSegmentEntities(SubscriberEntity $subscriber, array $segments) {
    $subscriberModel = Subscriber::findOne($subscriber->getId());
    $segmentModels = [];

    foreach ($segments as $segmentEntity) {
      $segmentModel = Segment::findOne($segmentEntity->getId());

      if ($segmentModel instanceof Segment) {
        $segmentModels[] = $segmentModel;
      }
    }

    $this->send($subscriberModel, $segmentModels);
  }

  /**
   * @param Subscriber $subscriber
   * @param Segment[] $segments
   *
   * @throws \Exception
   */
  public function send(Subscriber $subscriber, array $segments) {
    $settings = $this->settings->get(NewSubscriberNotificationMailer::SETTINGS_KEY);
    if ($this->isDisabled($settings)) {
      return;
    }
    try {
      $extraParams = [
        'meta' => $this->mailerMetaInfo->getNewSubscriberNotificationMetaInfo(),
      ];
      $this->mailer->send($this->constructNewsletter($subscriber, $segments), $settings['address'], $extraParams);
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
   * @param Subscriber $subscriber
   * @param Segment[] $segments
   *
   * @return array
   * @throws \Exception
   */
  private function constructNewsletter(Subscriber $subscriber, array $segments) {
    $segmentNames = $this->getSegmentNames($segments);
    $context = [
      'subscriber_email' => $subscriber->get('email'),
      'segments_names' => $segmentNames,
      'link_settings' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-settings'),
      'link_premium' => WPFunctions::get()->getSiteUrl(null, '/wp-admin/admin.php?page=mailpoet-upgrade'),
    ];
    return [
      'subject' => sprintf(__('New subscriber to %s', 'mailpoet'), $segmentNames),
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
