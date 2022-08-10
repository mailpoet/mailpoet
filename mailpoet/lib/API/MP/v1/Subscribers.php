<?php

namespace MailPoet\API\MP\v1;

use MailPoet\API\JSON\ResponseBuilders\SubscribersResponseBuilder;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Features\FeaturesController;
use MailPoet\Newsletter\Scheduler\WelcomeScheduler;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\Subscribers\ConfirmationEmailMailer;
use MailPoet\Subscribers\NewSubscriberNotificationMailer;
use MailPoet\Subscribers\SubscriberSegmentRepository;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Tasks\Sending;
use MailPoet\WP\Functions as WPFunctions;

class Subscribers {
  const CONTEXT_SUBSCRIBE = 'subscribe';
  const CONTEXT_UNSUBSCRIBE = 'unsubscribe';

  /** @var SettingsController */
  private $settings;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var SegmentsRepository */
  private $segmentsRepository;

  /** @var SubscriberSegmentRepository */
  private $subscribersSegmentRepository;

  /** @var ConfirmationEmailMailer */
  private $confirmationEmailMailer;

  /** @var WelcomeScheduler */
  private $welcomeScheduler;

  /** @var SubscribersResponseBuilder */
  private $subscribersResponseBuilder;

  /** @var NewSubscriberNotificationMailer */
  private $newSubscriberNotificationMailer;

  /** @var FeaturesController */
  private $featuresController;

  /** @var WPFunctions */
  private $wp;

  public function __construct (
    ConfirmationEmailMailer $confirmationEmailMailer,
    NewSubscriberNotificationMailer $newSubscriberNotificationMailer,
    SegmentsRepository $segmentsRepository,
    SettingsController $settings,
    SubscriberSegmentRepository $subscriberSegmentRepository,
    SubscribersRepository $subscribersRepository,
    SubscribersResponseBuilder $subscribersResponseBuilder,
    WelcomeScheduler $welcomeScheduler,
    FeaturesController $featuresController,
    WPFunctions $wp
  ) {
    $this->confirmationEmailMailer = $confirmationEmailMailer;
    $this->newSubscriberNotificationMailer = $newSubscriberNotificationMailer;
    $this->segmentsRepository = $segmentsRepository;
    $this->settings = $settings;
    $this->subscribersSegmentRepository = $subscriberSegmentRepository;
    $this->subscribersRepository = $subscribersRepository;
    $this->subscribersResponseBuilder = $subscribersResponseBuilder;
    $this->welcomeScheduler = $welcomeScheduler;
    $this->featuresController = $featuresController;
    $this->wp = $wp;
  }

  /**
   * @throws APIException
   */
  public function subscribeToLists(
    $subscriberId,
    array $listIds,
    array $options = []
  ): array {
    $scheduleWelcomeEmail = !((isset($options['schedule_welcome_email']) && $options['schedule_welcome_email'] === false));
    $sendConfirmationEmail = !((isset($options['send_confirmation_email']) && $options['send_confirmation_email'] === false));
    $skipSubscriberNotification = isset($options['skip_subscriber_notification']) && $options['skip_subscriber_notification'] === true;
    $signupConfirmationEnabled = (bool)$this->settings->get('signup_confirmation.enabled');

    $this->checkSubscriberAndListParams($subscriberId, $listIds);
    $subscriber = $this->findSubscriber($subscriberId);
    $foundSegments = $this->getAndValidateSegments($listIds, self::CONTEXT_SUBSCRIBE);

    $this->subscribersSegmentRepository->subscribeToSegments($subscriber, $foundSegments);

    // set status depending on signup confirmation setting
    if ($subscriber->getStatus() !== SubscriberEntity::STATUS_SUBSCRIBED) {
      if ($signupConfirmationEnabled === true) {
        $subscriber->setStatus(SubscriberEntity::STATUS_UNCONFIRMED);
      } else {
        $subscriber->setStatus(SubscriberEntity::STATUS_SUBSCRIBED);
      }
      try {
        $this->subscribersRepository->flush();
      } catch (\Exception $e) {
        throw new APIException(
          // translators: %s is the error message
          sprintf(__('Failed to save a status of a subscriber : %s', 'mailpoet'), $e->getMessage()),
          APIException::FAILED_TO_SAVE_SUBSCRIBER
        );
      }

      // when global status changes to subscribed, fire subscribed hook for all subscribed segments
      if (
        $this->featuresController->isSupported(FeaturesController::AUTOMATION)
        && $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED
      ) {
        $subscriberSegments = $subscriber->getSubscriberSegments();
        foreach ($subscriberSegments as $subscriberSegment) {
          if ($subscriberSegment->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED) {
            $this->wp->doAction('mailpoet_segment_subscribed', $subscriberSegment);
          }
        }
      }
    }

    // schedule welcome email
    $foundSegmentsIds = array_map(
      function(SegmentEntity $segment) {
        return $segment->getId();
      }, $foundSegments
    );
    if ($scheduleWelcomeEmail && $subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED) {
      $this->_scheduleWelcomeNotification($subscriber, $foundSegmentsIds);
    }

    // send confirmation email
    if ($sendConfirmationEmail) {
      $this->_sendConfirmationEmail($subscriber);
    }

    if (!$skipSubscriberNotification && ($subscriber->getStatus() === SubscriberEntity::STATUS_SUBSCRIBED)) {
      $this->newSubscriberNotificationMailer->send($subscriber, $this->segmentsRepository->findBy(['id' => $foundSegmentsIds]));
    }

    $this->subscribersRepository->refresh($subscriber);
    return $this->subscribersResponseBuilder->build($subscriber);
  }

  public function unsubscribeFromLists($subscriberIdOrEmail, array $listIds): array {
    $this->checkSubscriberAndListParams($subscriberIdOrEmail, $listIds);
    $subscriber = $this->findSubscriber($subscriberIdOrEmail);
    $foundSegments = $this->getAndValidateSegments($listIds, self::CONTEXT_UNSUBSCRIBE);
    $this->subscribersSegmentRepository->unsubscribeFromSegments($subscriber, $foundSegments);

    return $this->subscribersResponseBuilder->build($subscriber);
  }

  /**
   * @throws APIException
   */
  protected function _scheduleWelcomeNotification(SubscriberEntity $subscriber, array $segments) {
    $result = $this->welcomeScheduler->scheduleSubscriberWelcomeNotification($subscriber->getId(), $segments);
    if (is_array($result)) {
      foreach ($result as $queue) {
        if ($queue instanceof Sending && $queue->getErrors()) {
          throw new APIException(
            // translators: %s is a comma-separated list of errors
            sprintf(__('Subscriber added, but welcome email failed to send: %s', 'mailpoet'), strtolower(implode(', ', $queue->getErrors()))),
            APIException::WELCOME_FAILED_TO_SEND
          );
        }
      }
    }
  }

  /**
   * @throws APIException
   */
  protected function _sendConfirmationEmail(SubscriberEntity $subscriberEntity) {
    try {
      $this->confirmationEmailMailer->sendConfirmationEmailOnce($subscriberEntity);
    } catch (\Exception $e) {
      throw new APIException(
        // translators: %s is the error message
        sprintf(__('Subscriber added to lists, but confirmation email failed to send: %s', 'mailpoet'), strtolower($e->getMessage())),
        APIException::CONFIRMATION_FAILED_TO_SEND
      );
    }
  }

  /**
   * @throws APIException
   */
  private function checkSubscriberAndListParams($subscriberIdOrEmail, array $listIds): void {
    if (empty($listIds)) {
      throw new APIException(__('At least one segment ID is required.', 'mailpoet'), APIException::SEGMENT_REQUIRED);
    }
    if (empty($subscriberIdOrEmail)) {
      throw new APIException(__('A subscriber is required.', 'mailpoet'), APIException::SUBSCRIBER_NOT_EXISTS);
    }
  }

  /**
   * @throws APIException
   */
  private function findSubscriber($subscriberIdOrEmail): SubscriberEntity {
    // throw exception when subscriber does not exist
    $subscriber = null;
    if (is_int($subscriberIdOrEmail) || (string)(int)$subscriberIdOrEmail === $subscriberIdOrEmail) {
      $subscriber = $this->subscribersRepository->findOneById($subscriberIdOrEmail);
    } else if (strlen(trim($subscriberIdOrEmail)) > 0) {
      $subscriber = $this->subscribersRepository->findOneBy(['email' => $subscriberIdOrEmail]);
    }

    if (!$subscriber) {
      throw new APIException(__('This subscriber does not exist.', 'mailpoet'), APIException::SUBSCRIBER_NOT_EXISTS);
    }

    return $subscriber;
  }

  /**
   * @return SegmentEntity[]
   * @throws APIException
   */
  private function getAndValidateSegments(array $listIds, string $context): array {
    // throw exception when none of the segments exist
    $foundSegments = $this->segmentsRepository->findBy(['id' => $listIds]);
    if (!$foundSegments) {
      $exception = _n('This list does not exist.', 'These lists do not exist.', count($listIds), 'mailpoet');
      throw new APIException($exception, APIException::LIST_NOT_EXISTS);
    }

    // throw exception when trying to subscribe to WP Users or WooCommerce Customers segments
    $foundSegmentsIds = [];
    foreach ($foundSegments as $foundSegment) {
      if ($foundSegment->getType() === SegmentEntity::TYPE_WP_USERS) {
        if ($context === self::CONTEXT_SUBSCRIBE) {
          // translators: %d is the ID of the segment
          $message = __("Can't subscribe to a WordPress Users list with ID %d.", 'mailpoet');
        } else {
          // translators: %d is the ID of the segment
          $message = __("Can't unsubscribe from a WordPress Users list with ID %d.", 'mailpoet');
        }
        throw new APIException(sprintf($message, $foundSegment->getId()), APIException::SUBSCRIBING_TO_WP_LIST_NOT_ALLOWED);
      }
      if ($foundSegment->getType() === SegmentEntity::TYPE_WC_USERS) {
        if ($context === self::CONTEXT_SUBSCRIBE) {
          // translators: %d is the ID of the segment
          $message = __("Can't subscribe to a WooCommerce Customers list with ID %d.", 'mailpoet');
        } else {
          // translators: %d is the ID of the segment
          $message = __("Can't unsubscribe from a WooCommerce Customers list with ID %d.", 'mailpoet');
        }
        throw new APIException(sprintf($message, $foundSegment->getId()), APIException::SUBSCRIBING_TO_WC_LIST_NOT_ALLOWED);
      }
      if ($foundSegment->getType() !== SegmentEntity::TYPE_DEFAULT) {
        if ($context === self::CONTEXT_SUBSCRIBE) {
          // translators: %d is the ID of the segment
          $message = __("Can't subscribe to a list with ID %d.", 'mailpoet');
        } else {
          // translators: %d is the ID of the segment
          $message = __("Can't unsubscribe from a list with ID %d.", 'mailpoet');
        }
        throw new APIException(sprintf($message, $foundSegment->getId()), APIException::SUBSCRIBING_TO_LIST_NOT_ALLOWED);
      }
      $foundSegmentsIds[] = $foundSegment->getId();
    }

    // throw an exception when one or more segments do not exist
    if (count($foundSegmentsIds) !== count($listIds)) {
      $missingIds = array_values(array_diff($listIds, $foundSegmentsIds));
      $exception = sprintf(
        // translators: %s is the count of lists
        _n('List with ID %s does not exist.', 'Lists with IDs %s do not exist.', count($missingIds), 'mailpoet'),
        implode(', ', $missingIds)
      );
      throw new APIException(sprintf($exception, implode(', ', $missingIds)), APIException::LIST_NOT_EXISTS);
    }

    return $foundSegments;
  }
}
