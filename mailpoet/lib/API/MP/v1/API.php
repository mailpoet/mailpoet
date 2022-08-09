<?php

namespace MailPoet\API\MP\v1;

use MailPoet\Config\Changelog;
use MailPoet\Models\Subscriber;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\Source;
use MailPoet\Util\Helpers;

/**
 * API used by other plugins
 * Do not add bodies of methods into this class. Use other classes. See CustomFields or Subscribers.
 * This class is under refactor, and we are going to move most of the remaining implementations from here.
 */
class API {

  /** @var RequiredCustomFieldValidator */
  private $requiredCustomFieldValidator;

  /** @var CustomFields */
  private $customFields;

  /** @var Segments */
  private $segments;

  /** @var Subscribers */
  private $subscribers;

  /** @var Changelog */
  private $changelog;

  public function __construct(
    RequiredCustomFieldValidator $requiredCustomFieldValidator,
    CustomFields $customFields,
    Segments $segments,
    Subscribers $subscribers,
    Changelog $changelog
  ) {
    $this->requiredCustomFieldValidator = $requiredCustomFieldValidator;
    $this->customFields = $customFields;
    $this->segments = $segments;
    $this->subscribers = $subscribers;
    $this->changelog = $changelog;
  }

  public function getSubscriberFields() {
    return $this->customFields->getSubscriberFields();
  }

  public function addSubscriberField(array $data = []) {
    try {
      return $this->customFields->addSubscriberField($data);
    } catch (\InvalidArgumentException $e) {
      throw new APIException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * @throws APIException
   */
  public function subscribeToList($subscriberId, $listId, $options = []): array {
    return $this->subscribeToLists($subscriberId, [$listId], $options);
  }

  /**
   * @throws APIException
   */
  public function subscribeToLists($subscriberId, array $listIds, $options = []) {
      return $this->subscribers->subscribeToLists($subscriberId, $listIds, $options);
  }

  public function unsubscribeFromList($subscriberId, $listId) {
    return $this->unsubscribeFromLists($subscriberId, [$listId]);
  }

  public function unsubscribeFromLists($subscriberId, array $listIds) {
    return $this->subscribers->unsubscribeFromLists($subscriberId, $listIds);
  }

  public function getLists(): array {
    return $this->segments->getAll();
  }

  public function addSubscriber(array $subscriber, $listIds = [], $options = []) {
    $sendConfirmationEmail = (isset($options['send_confirmation_email']) && $options['send_confirmation_email'] === false) ? false : true;
    $scheduleWelcomeEmail = (isset($options['schedule_welcome_email']) && $options['schedule_welcome_email'] === false) ? false : true;
    $skipSubscriberNotification = (isset($options['skip_subscriber_notification']) && $options['skip_subscriber_notification'] === true) ? true : false;

    // throw exception when subscriber email is missing
    if (empty($subscriber['email'])) {
      throw new APIException(
        __('Subscriber email address is required.', 'mailpoet'),
        APIException::EMAIL_ADDRESS_REQUIRED
      );
    }

    // throw exception when subscriber already exists
    if (Subscriber::findOne($subscriber['email'])) {
      throw new APIException(
        __('This subscriber already exists.', 'mailpoet'),
        APIException::SUBSCRIBER_EXISTS
      );
    }

    if (empty($subscriber['subscribed_ip'])) {
      $subscriber['subscribed_ip'] = Helpers::getIP();
    }

    // separate data into default and custom fields
    [$defaultFields, $customFields] = Subscriber::extractCustomFieldsFromFromObject($subscriber);

    // filter out all incoming data that we don't want to change, like status ...
    $defaultFields = array_intersect_key($defaultFields, array_flip(['email', 'first_name', 'last_name', 'subscribed_ip']));

    // if some required default fields are missing, set their values
    $defaultFields = Subscriber::setRequiredFieldsDefaultValues($defaultFields);

    $this->requiredCustomFieldValidator->validate($customFields);

    // add subscriber
    $newSubscriber = Subscriber::create();
    $newSubscriber->hydrate($defaultFields);
    $newSubscriber = Source::setSource($newSubscriber, Source::API);
    $newSubscriber->save();
    if ($newSubscriber->getErrors() !== false) {
      throw new APIException(
        // translators: %s is a comma-seperated list of errors.
        sprintf(__('Failed to add subscriber: %s', 'mailpoet'), strtolower(implode(', ', $newSubscriber->getErrors()))),
        APIException::FAILED_TO_SAVE_SUBSCRIBER
      );
    }
    if (!empty($customFields)) {
      $newSubscriber->saveCustomFields($customFields);
    }

    // reload subscriber to get the saved status/created|updated|delete dates/other fields
    $newSubscriber = Subscriber::findOne($newSubscriber->id);

    // subscribe to segments and optionally: 1) send confirmation email, 2) schedule welcome email(s)
    if (!empty($listIds)) {
      $this->subscribeToLists($newSubscriber->id, $listIds, [
        'send_confirmation_email' => $sendConfirmationEmail,
        'schedule_welcome_email' => $scheduleWelcomeEmail,
        'skip_subscriber_notification' => $skipSubscriberNotification,
      ]);
    }
    return $newSubscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  public function addList(array $list) {
    return $this->segments->addList($list);
  }

  public function getSubscriber($subscriberEmail) {
    $subscriber = Subscriber::findOne($subscriberEmail);
    // throw exception when subscriber does not exist
    if (!$subscriber) {
      throw new APIException(__('This subscriber does not exist.', 'mailpoet'), APIException::SUBSCRIBER_NOT_EXISTS);
    }
    return $subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  public function isSetupComplete() {
    return !(
      $this->changelog->shouldShowWelcomeWizard()
      || $this->changelog->shouldShowWooCommerceListImportPage()
      || $this->changelog->shouldShowRevenueTrackingPermissionPage()
      || $this->changelog->isMp2MigrationInProgress()
      || $this->changelog->shouldShowMp2Migration()
    );
  }
}
