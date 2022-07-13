<?php

namespace MailPoet\Subscribers\ImportExport\PersonalDataExporters;

use MailPoet\CustomFields\CustomFieldsRepository;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\Source;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\WP\DateTime;
use MailPoet\WP\Functions as WPFunctions;

class SubscriberExporter {
  /*** @var SubscribersRepository */
  private $subscribersRepository;

  /*** @var CustomFieldsRepository */
  private $customFieldsRepository;

  /*** @var array<int, string> */
  private $customFields = [];

  public function __construct(
    SubscribersRepository $subscribersRepository,
    CustomFieldsRepository $customFieldsRepository
  ) {
    $this->subscribersRepository = $subscribersRepository;
    $this->customFieldsRepository = $customFieldsRepository;
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
      'group_label' => WPFunctions::get()->__('MailPoet Subscriber Data', 'mailpoet'),
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
        'name' => WPFunctions::get()->__('First Name', 'mailpoet'),
        'value' => $subscriber->getFirstName(),
      ],
      [
        'name' => WPFunctions::get()->__('Last Name', 'mailpoet'),
        'value' => $subscriber->getLastName(),
      ],
      [
        'name' => WPFunctions::get()->__('Email', 'mailpoet'),
        'value' => $subscriber->getEmail(),
      ],
      [
        'name' => WPFunctions::get()->__('Status', 'mailpoet'),
        'value' => $subscriber->getStatus(),
      ],
    ];
    if ($subscriber->getSubscribedIp()) {
      $result[] = [
        'name' => WPFunctions::get()->__('Subscribed IP', 'mailpoet'),
        'value' => $subscriber->getSubscribedIp(),
      ];
    }
    if ($subscriber->getConfirmedIp()) {
      $result[] = [
        'name' => WPFunctions::get()->__('Confirmed IP', 'mailpoet'),
        'value' => $subscriber->getConfirmedIp(),
      ];
    }
    $result[] = [
      'name' => WPFunctions::get()->__('Created at', 'mailpoet'),
      'value' => $subscriber->getCreatedAt()
        ? $subscriber->getCreatedAt()->format(DateTime::DEFAULT_DATE_TIME_FORMAT)
        : '',
    ];

    foreach ($subscriber->getSubscriberCustomFields() as $field) {
      if (isset($this->getCustomFields()[$field->getId()])) {
        $result[] = [
          'name' => $customFields[$field->getId()],
          'value' => $field->getValue(),
        ];
      }
    }

    $result[] = [
      'name' => WPFunctions::get()->__("Subscriber's subscription source", 'mailpoet'),
      'value' => $this->formatSource($subscriber->getSource()),
    ];

    return $result;
  }

  /**
   * @return array<int, string>
   */
  private function getCustomFields(): array {
    if (!empty($this->customFields)) {
      return $this->customFields;
    }

    $fields = $this->customFieldsRepository->findAll();
    foreach ($fields as $field) {
      $this->customFields[$field->getId()] = $field->getName();
    }
    return $this->customFields;
  }

  private function formatSource(string $source): string {
    switch ($source) {
      case Source::WORDPRESS_USER:
        return WPFunctions::get()->__('Subscriber information synchronized via WP user sync', 'mailpoet');
      case Source::FORM:
        return WPFunctions::get()->__('Subscription via a MailPoet subscription form', 'mailpoet');
      case Source::API:
        return WPFunctions::get()->__('Added by a 3rd party via MailPoet 3 API', 'mailpoet');
      case Source::ADMINISTRATOR:
        return WPFunctions::get()->__('Created by the administrator', 'mailpoet');
      case Source::IMPORTED:
        return WPFunctions::get()->__('Imported by the administrator', 'mailpoet');
      default:
        return WPFunctions::get()->__('Unknown', 'mailpoet');
    }
  }
}
