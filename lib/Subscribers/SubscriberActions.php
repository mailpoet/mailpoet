<?php

namespace MailPoet\Subscribers;

use MailPoet\Listing\BulkActionFactory;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\Scheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;

class SubscriberActions {

  /** @var SettingsController */
  private $settings;

  /** @var NewSubscriberNotificationMailer */
  private $new_subscriber_notification_mailer;

  /** @var ConfirmationEmailMailer */
  private $confirmation_email_mailer;

  /** @var BulkActionFactory */
  private $bulk_action_factory;

  public function __construct(
    SettingsController $settings,
    NewSubscriberNotificationMailer $new_subscriber_notification_mailer,
    ConfirmationEmailMailer $confirmation_email_mailer,
    BulkActionFactory $bulk_action_factory
  ) {
    $this->settings = $settings;
    $this->new_subscriber_notification_mailer = $new_subscriber_notification_mailer;
    $this->confirmation_email_mailer = $confirmation_email_mailer;
    $this->bulk_action_factory = $bulk_action_factory;
    $this->bulk_action_factory->registerAction('\MailPoet\Models\Subscriber', 'bulkSendConfirmationEmail', $this);
  }

  function subscribe($subscriber_data = [], $segment_ids = []) {
    // filter out keys from the subscriber_data array
    // that should not be editable when subscribing
    $subscriber_data = Subscriber::filterOutReservedColumns($subscriber_data);

    $signup_confirmation_enabled = (bool)$this->settings->get(
      'signup_confirmation.enabled'
    );

    $subscriber_data['subscribed_ip'] = Helpers::getIP();

    $subscriber = Subscriber::findOne($subscriber_data['email']);

    if ($subscriber === false || !$signup_confirmation_enabled) {
      // create new subscriber or update if no confirmation is required
      $subscriber = Subscriber::createOrUpdate($subscriber_data);
      if ($subscriber->getErrors() !== false) {
        $subscriber = Source::setSource($subscriber, Source::FORM);
        $subscriber->save();
        return $subscriber;
      }

      $subscriber = Subscriber::findOne($subscriber->id);
    } else {
      // store subscriber data to be updated after confirmation
      $subscriber->setUnconfirmedData($subscriber_data);
      $subscriber->setExpr('updated_at', 'NOW()');
    }

    // restore trashed subscriber
    if ($subscriber->deleted_at !== null) {
      $subscriber->setExpr('deleted_at', 'NULL');
    }

    // set status depending on signup confirmation setting
    if ($subscriber->status !== Subscriber::STATUS_SUBSCRIBED) {
      if ($signup_confirmation_enabled === true) {
        $subscriber->set('status', Subscriber::STATUS_UNCONFIRMED);
      } else {
        $subscriber->set('status', Subscriber::STATUS_SUBSCRIBED);
      }
    }

    $subscriber = Source::setSource($subscriber, Source::FORM);

    if ($subscriber->save()) {
      // link subscriber to segments
      SubscriberSegment::subscribeToSegments($subscriber, $segment_ids);

      $this->confirmation_email_mailer->sendConfirmationEmail($subscriber);

      if ($subscriber->status === Subscriber::STATUS_SUBSCRIBED) {
        $this->new_subscriber_notification_mailer->send($subscriber, Segment::whereIn('id', $segment_ids)->findMany());

        Scheduler::scheduleSubscriberWelcomeNotification(
          $subscriber->id,
          $segment_ids
        );
      }
    }

    return $subscriber;
  }

  function bulkSendConfirmationEmail($orm) {
    $subscribers = $orm
      ->where('status', Subscriber::STATUS_UNCONFIRMED)
      ->findMany();

    $emails_sent = 0;
    if (!empty($subscribers)) {
      foreach ($subscribers as $subscriber) {
        if ($this->confirmation_email_mailer->sendConfirmationEmail($subscriber)) {
          $emails_sent++;
        }
      }
    }

    return [
      'count' => $emails_sent,
    ];
  }

}
