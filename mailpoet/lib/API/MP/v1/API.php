<?php

namespace MailPoet\API\MP\v1;

use MailPoet\Config\Changelog;
use MailPoet\Models\Segment;
use MailPoet\Models\Subscriber;
use MailPoet\Models\SubscriberSegment;
use MailPoet\Subscribers\RequiredCustomFieldValidator;
use MailPoet\Subscribers\Source;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class API {

  /** @var RequiredCustomFieldValidator */
  private $requiredCustomFieldValidator;

  /** @var CustomFields */
  private $customFields;

  /** @var Subscribers */
  private $subscribers;

  /** @var Changelog */
  private $changelog;

  public function __construct(
    RequiredCustomFieldValidator $requiredCustomFieldValidator,
    CustomFields $customFields,
    Subscribers $subscribers,
    Changelog $changelog
  ) {
    $this->requiredCustomFieldValidator = $requiredCustomFieldValidator;
    $this->customFields = $customFields;
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
    if (empty($listIds)) {
      throw new APIException(__('At least one segment ID is required.', 'mailpoet'), APIException::SEGMENT_REQUIRED);
    }

    // throw exception when subscriber does not exist
    $subscriber = Subscriber::findOne($subscriberId);
    if (!$subscriber) {
      throw new APIException(__('This subscriber does not exist.', 'mailpoet'), APIException::SUBSCRIBER_NOT_EXISTS);
    }

    // throw exception when none of the segments exist
    $foundSegments = Segment::whereIn('id', $listIds)->findMany();
    if (!$foundSegments) {
      $exception = WPFunctions::get()->_n('This list does not exist.', 'These lists do not exist.', count($listIds), 'mailpoet');
      throw new APIException($exception, APIException::LIST_NOT_EXISTS);
    }

    // throw exception when trying to subscribe to WP Users or WooCommerce Customers segments
    $foundSegmentsIds = [];
    foreach ($foundSegments as $segment) {
      if ($segment->type === Segment::TYPE_WP_USERS) {
        throw new APIException(__(sprintf("Can't unsubscribe from a WordPress Users list with ID %d.", $segment->id), 'mailpoet'), APIException::SUBSCRIBING_TO_WP_LIST_NOT_ALLOWED);
      }
      if ($segment->type === Segment::TYPE_WC_USERS) {
        throw new APIException(__(sprintf("Can't unsubscribe from a WooCommerce Customers list with ID %d.", $segment->id), 'mailpoet'), APIException::SUBSCRIBING_TO_WC_LIST_NOT_ALLOWED);
      }
      $foundSegmentsIds[] = $segment->id;
    }

    // throw an exception when one or more segments do not exist
    if (count($foundSegmentsIds) !== count($listIds)) {
      $missingIds = array_values(array_diff($listIds, $foundSegmentsIds));
      $exception = sprintf(
        WPFunctions::get()->_n('List with ID %s does not exist.', 'Lists with IDs %s do not exist.', count($missingIds), 'mailpoet'),
        implode(', ', $missingIds)
      );
      throw new APIException($exception, APIException::LIST_NOT_EXISTS);
    }

    SubscriberSegment::unsubscribeFromSegments($subscriber, $foundSegmentsIds);
    return $subscriber->withCustomFields()->withSubscriptions()->asArray();
  }

  public function getLists() {
    return Segment::where('type', Segment::TYPE_DEFAULT)
      ->findArray();
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
        __(sprintf('Failed to add subscriber: %s', strtolower(implode(', ', $newSubscriber->getErrors()))), 'mailpoet'),
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
    // throw exception when list name is missing
    if (empty($list['name'])) {
      throw new APIException(
        __('List name is required.', 'mailpoet'),
        APIException::LIST_NAME_REQUIRED
      );
    }

    // throw exception when list already exists
    if (Segment::where('name', $list['name'])->findOne()) {
      throw new APIException(
        __('This list already exists.', 'mailpoet'),
        APIException::LIST_EXISTS
      );
    }

    // filter out all incoming data that we don't want to change, like type,
    $list = array_intersect_key($list, array_flip(['name', 'description']));

    // add list
    $newList = Segment::create();
    $newList->hydrate($list);
    $newList->save();
    if ($newList->getErrors() !== false) {
      throw new APIException(
        __(sprintf('Failed to add list: %s', strtolower(implode(', ', $newList->getErrors()))), 'mailpoet'),
        APIException::FAILED_TO_SAVE_LIST
      );
    }

    // reload list to get the saved created|updated|delete dates/other fields
    $newList = Segment::findOne($newList->id);
    if (!$newList instanceof Segment) {
      throw new APIException(__('Failed to add list', 'mailpoet'), APIException::FAILED_TO_SAVE_LIST);
    }

    return $newList->asArray();
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
