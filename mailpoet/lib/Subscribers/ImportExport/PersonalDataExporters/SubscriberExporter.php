<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\CustomFieldEntity;
use MailPoet\Entities\StatisticsUnsubscribeEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Statistics\StatisticsUnsubscribesRepository;
use MailPoet\Statistics\UnsubscribeReasonTracker;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\DateTime;

class SubscriberExporter {
  /*** @var SubscribersRepository */
  private $subscribersRepository;

  /*** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /** @var StatisticsUnsubscribesRepository */
  private $statisticsUnsubscribesRepository;

  /** @var UnsubscribeReasonTracker */
  private $unsubscribeReasonTracker;

  /*** @var array<int, string> */
  private $customFields = [];

  public function __construct(
    SubscribersRepository $subscribersRepository,
    CustomFieldsRepository $customFieldsRepository,
    StatisticsUnsubscribesRepository $statisticsUnsubscribesRepository,
    UnsubscribeReasonTracker $unsubscribeReasonTracker
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->customFieldsRepository = $customFieldsRepository;
    $this->statisticsUnsubscribesRepository = $statisticsUnsubscribesRepository;
    $this->unsubscribeReasonTracker = $unsubscribeReasonTracker;
  }

  /**
   * @param string $email
   * @return array(data: mixed[], done: boolean)
   */
  public function export(string $email): array {
    return [
      'data' => $this->exportSubscriber($this->subscribersRepository->findOneBy(['email' => trim($email)])),
      'done' => true,
    ];
  }

  /**
   * @param SubscriberEntity|null $subscriber
   * @return array|mixed[][]
   */
  private function exportSubscriber(?SubscriberEntity $subscriber): array {
    if (!$subscriber) return [];
    return [[
      'group_id' => 'mailpoet-subscriber',
      'group_label' => __('MailPoet Subscriber Data', 'mailpoet'),
      'item_id' => 'subscriber-' . $subscriber->getId(),
      'data' => $this->getSubscriberExportData($subscriber),
    ]];
  }

  /**
   * @param SubscriberEntity $subscriber
   * @return mixed[][]
   */
  private function getSubscriberExportData(SubscriberEntity $subscriber): array {
    $customFields = $this->getCustomFields();
    $result = [
      [
        'name' => __('First Name', 'mailpoet'),
        'value' => $subscriber->getFirstName(),
      ],
      [
        'name' => __('Last Name', 'mailpoet'),
        'value' => $subscriber->getLastName(),
      ],
      [
        'name' => __('Email', 'mailpoet'),
        'value' => $subscriber->getEmail(),
      ],
      [
        'name' => __('Status', 'mailpoet'),
        'value' => $subscriber->getStatus(),
      ],
    ];
    if ($subscriber->getSubscribedIp()) {
      $result[] = [
        'name' => __('Subscribed IP', 'mailpoet'),
        'value' => $subscriber->getSubscribedIp(),
      ];
    }
    if ($subscriber->getConfirmedIp()) {
      $result[] = [
        'name' => __('Confirmed IP', 'mailpoet'),
        'value' => $subscriber->getConfirmedIp(),
      ];
    }
    $result[] = [
      'name' => __('Created at', 'mailpoet'),
      'value' => $subscriber->getCreatedAt()
        ? $subscriber->getCreatedAt()->format(DateTime::DEFAULT_DATE_TIME_FORMAT)
        : '',
    ];

    foreach ($subscriber->getSubscriberCustomFields() as $subscriberCustomField) {
      $customField = $subscriberCustomField->getCustomField();
      if (!$customField instanceof CustomFieldEntity) {
        continue;
      }
      $customFieldId = $customField->getId();
      if ($customFieldId !== null && isset($customFields[$customFieldId])) {
        $result[] = [
          'name' => $customFields[$customFieldId],
          'value' => $subscriberCustomField->getValue(),
        ];
      }
    }

    $result[] = [
      'name' => __("Subscriber's subscription source", 'mailpoet'),
      'value' => $this->formatSource($subscriber->getSource()),
    ];

    foreach ($this->getUnsubscribeReasonExportData($subscriber) as $item) {
      $result[] = $item;
    }

    return $result;
  }

  /**
   * @return mixed[][]
   */
  private function getUnsubscribeReasonExportData(SubscriberEntity $subscriber): array {
    $unsubscribes = $this->statisticsUnsubscribesRepository->findBy([
      'subscriber' => $subscriber,
    ], [
      'createdAt' => 'desc',
    ]);

    $reasonLabels = $this->unsubscribeReasonTracker->getReasonLabels();
    $result = [];
    foreach ($unsubscribes as $unsubscribe) {
      if (!$unsubscribe instanceof StatisticsUnsubscribeEntity || $unsubscribe->getReason() === null) {
        continue;
      }

      $result[] = [
        'name' => __('Unsubscribe reason', 'mailpoet'),
        'value' => $reasonLabels[$unsubscribe->getReason()] ?? $unsubscribe->getReason(),
      ];
      if ($unsubscribe->getReasonText() !== null) {
        $result[] = [
          'name' => __('Unsubscribe reason details', 'mailpoet'),
          'value' => $unsubscribe->getReasonText(),
        ];
      }
    }
    return $result;
  }

  /**
   * @return array<int, string>
   */
  private function getCustomFields(): array {
    if (count($this->customFields) > 0) {
      return $this->customFields;
    }

    $fields = $this->customFieldsRepository->findAll();
    foreach ($fields as $field) {
      $fieldId = $field->getId();
      if ($fieldId !== null) {
        $this->customFields[$fieldId] = $field->getName();
      }
    }
    return $this->customFields;
  }

  private function formatSource(string $source): string {
    switch ($source) {
      case Source::WORDPRESS_USER:
        return __('Subscriber information synchronized via WP user sync', 'mailpoet');
      case Source::FORM:
        return __('Subscription via a MailPoet subscription form', 'mailpoet');
      case Source::API:
        return __('Added by a 3rd party via MailPoet API', 'mailpoet');
      case Source::ADMINISTRATOR:
        return __('Created by the administrator', 'mailpoet');
      case Source::IMPORTED:
        return __('Imported by the administrator', 'mailpoet');
      default:
        return __('Unknown', 'mailpoet');
    }
  }
}
