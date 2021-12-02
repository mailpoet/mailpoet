<?php

namespace MailPoet\Subscribers;

use MailPoet\Entities\SubscriberEntity;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
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

  /** @var SubscriberSaveController */
  private $subscriberSaveController;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SubscriberSegmentRepository */
  private $subscriberSegmentRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  public function __construct(
    SettingsController $settings,
    NewSubscriberNotificationMailer $newSubscriberNotificationMailer,
    ConfirmationEmailMailer $confirmationEmailMailer,
    WelcomeScheduler $welcomeScheduler,
    SegmentsRepository $segmentsRepository,
    SubscriberSaveController $subscriberSaveController,
    SubscribersRepository $subscribersRepository,
    SubscriberSegmentRepository $subscriberSegmentRepository
  ) {
    $this->settings = $settings;
    $this->newSubscriberNotificationMailer = $newSubscriberNotificationMailer;
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->subscriberSaveController = $subscriberSaveController;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscriberSegmentRepository = $subscriberSegmentRepository;
    $this->segmentsRepository = $segmentsRepository;
  }

  public function subscribe($subscriberData = [], $segmentIds = []): SubscriberEntity {
    // filter out keys from the subscriber_data array
    // that should not be editable when subscribing
    $subscriberData = $this->subscriberSaveController->filterOutReservedColumns($subscriberData);

    $signupConfirmationEnabled = (bool)$this->settings->get(
      'signup_confirmation.enabled'
    );

    $subscriberData['subscribed_ip'] = Helpers::getIP();

    $subscriber = $this->subscribersRepository->findOneBy(['email' => $subscriberData['email']]);
    if (!$subscriber && !isset($subscriberData['source'])) {
      $subscriberData['source'] = Source::FORM;
    }

    if (!$subscriber || !$signupConfirmationEnabled) {
      // create new subscriber or update if no confirmation is required
      $subscriber = $this->subscriberSaveController->createOrUpdate($subscriberData, $subscriber);
    } else {
      // store subscriber data to be updated after confirmation
      $unconfirmedData = $this->subscriberSaveController->filterOutReservedColumns($subscriberData);
      $unconfirmedData = json_encode($unconfirmedData);
      $subscriber->setUnconfirmedData($unconfirmedData ?: null);
    }

    // Update custom fields
    $this->subscriberSaveController->updateCustomFields($subscriberData, $subscriber);

    // restore trashed subscriber
    if ($subscriber->getDeletedAt()) {
      $subscriber->setDeletedAt(null);
    }

    // set status depending on signup confirmation setting
    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      if ($signupConfirmationEnabled === true) {
        $subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
      } else {
        $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
      }
    }

    $this->subscribersRepository->flush();
    // link subscriber to segments
    $segments = $this->segmentsRepository->findBy(['id' => $segmentIds]);
    $this->subscriberSegmentRepository->subscribeToSegments($subscriber, $segments);
    $this->confirmationEmailMailer->sendConfirmationEmailOnce($subscriber);

    $subscriberModel = Subscriber::findOne($subscriber->getId());

    // We want to send the notification on subscribe only when signupConfirmation is disabled
    if ($signupConfirmationEnabled === false && $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED && $subscriberModel) {
      $this->newSubscriberNotificationMailer->send($subscriberModel, Segment::whereIn('id', $segmentIds)->findMany());

      $this->welcomeScheduler->scheduleSubscriberWelcomeNotification(
        $subscriber->getId(),
        $segmentIds
      );
    }

    return $subscriber;
  }
}
