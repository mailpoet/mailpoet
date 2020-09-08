<?php

namespace MailPoet\Subscribers;

use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;

class SubscriberActions {

  /** @var SettingsController */
  private $settings;

  /** @var NewSubscriberNotificationMailer */
  private $newSubscriberNotificationMailer;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  public function __construct(
    SettingsController $settings,
    NewSubscriberNotificationMailer $newSubscriberNotificationMailer,
    ConfirmationEmailMailer $confirmationEmailMailer,
    WelcomeScheduler $welcomeScheduler
  ) {
    $this->settings = $settings;
    $this->newSubscriberNotificationMailer = $newSubscriberNotificationMailer;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->welcomeScheduler = $welcomeScheduler;
  }

  public function subscribe($subscriberData = [], $segmentIds = []) {
    // filter out keys from the subscriber_data array
    // that should not be editable when subscribing
    $subscriberData = Subscriber::filterOutReservedColumns($subscriberData);

    $signupConfirmationEnabled = (bool)$this->settings->get(
      'signup_confirmation.enabled'
    );

    $subscriberData['subscribed_ip'] = Helpers::getIP();

    $subscriber = Subscriber::findOne($subscriberData['email']);

    if ($subscriber === false || !$signupConfirmationEnabled) {
      // create new subscriber or update if no confirmation is required
      $subscriber = Subscriber::createOrUpdate($subscriberData);
      if ($subscriber->getErrors() !== false) {
        $subscriber = Source::setSource($subscriber, Source::FORM);
        $subscriber->save();
        return $subscriber;
      }

      $subscriber = Subscriber::findOne($subscriber->id);
    } else {
      // store subscriber data to be updated after confirmation
      $subscriber->setUnconfirmedData($subscriberData);
      $subscriber->setExpr('updated_at', 'NOW()');
    }

    // restore trashed subscriber
    if ($subscriber->deletedAt !== null) {
      $subscriber->setExpr('deleted_at', 'NULL');
    }

    // set status depending on signup confirmation setting
    if ($subscriber->status !== Subscriber::STATUS_SUBSCRIBED) {
      if ($signupConfirmationEnabled === true) {
        $subscriber->set('status', Subscriber::STATUS_UNCONFIRMED);
      } else {
        $subscriber->set('status', Subscriber::STATUS_SUBSCRIBED);
      }
    }

    $subscriber = Source::setSource($subscriber, Source::FORM);

    if ($subscriber->save()) {
      // link subscriber to segments
      SubscriberSegment::subscribeToSegments($subscriber, $segmentIds);

      $this->confirmationEmailMailer->sendConfirmationEmailOnce($subscriber);

      if ($subscriber->status === Subscriber::STATUS_SUBSCRIBED) {
        $this->newSubscriberNotificationMailer->send($subscriber, Segment::whereIn('id', $segmentIds)->findMany());

        $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
          $subscriber->id,
          $segmentIds
        );
      }
    }

    return $subscriber;
  }
}
