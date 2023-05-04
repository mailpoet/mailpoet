<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\Subscribers\Statistics\SubscriberStatisticsRepository;

class SubscriberStatisticFieldsFactory {
  /** @var SubscriberStatisticsRepository */
  private $subscriberStatisticsRepository;

  public function __construct(
    SubscriberStatisticsRepository $subscriberStatisticsRepository
  ) {
    $this->subscriberStatisticsRepository = $subscriberStatisticsRepository;
  }

  /** @return Field[] */
  public function getFields(): array {
    return [
      new Field(
        'mailpoet:subscriber:email-sent-count',
        Field::TYPE_INTEGER,
        __('Email — sent count', 'mailpoet'),
        function (SubscriberPayload $payload) {
          $stats = $this->subscriberStatisticsRepository->getStatistics($payload->getSubscriber());
          return $stats->getTotalSentCount();
        }
      ),
      new Field(
        'mailpoet:subscriber:email-opened-count',
        Field::TYPE_INTEGER,
        __('Email — opened count', 'mailpoet'),
        function (SubscriberPayload $payload) {
          $stats = $this->subscriberStatisticsRepository->getStatistics($payload->getSubscriber());
          return $stats->getOpenCount();
        }
      ),
      new Field(
        'mailpoet:subscriber:email-machine-opened-count',
        Field::TYPE_INTEGER,
        __('Email — machine opened count', 'mailpoet'),
        function (SubscriberPayload $payload) {
          $stats = $this->subscriberStatisticsRepository->getStatistics($payload->getSubscriber());
          return $stats->getMachineOpenCount();
        }
      ),
      new Field(
        'mailpoet:subscriber:email-clicked-count',
        Field::TYPE_INTEGER,
        __('Email — clicked count', 'mailpoet'),
        function (SubscriberPayload $payload) {
          $stats = $this->subscriberStatisticsRepository->getStatistics($payload->getSubscriber());
          return $stats->getClickCount();
        }
      ),
    ];
  }
}
