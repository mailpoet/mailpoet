<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\MailPoet\Fields\SubscriberFieldsFactory;
use MailPoet\Automation\Integrations\MailPoet\Payloads\SubscriberPayload;
use MailPoet\NotFoundException;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WPCOM\DotcomHelperFunctions;

/**
 * @implements Subject<SubscriberPayload>
 */
class SubscriberSubject implements Subject {
  const KEY = 'mailpoet:subscriber';
  private const SUBJECT_ID_ARG = 'subscriber_id';

  /** @var SubscriberFieldsFactory */
  private $subscriberFieldsFactory;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var DotcomHelperFunctions */
  private $dotcomHelperFunctions;

  public function __construct(
    SubscriberFieldsFactory $subscriberFieldsFactory,
    SubscribersRepository $subscribersRepository,
    DotcomHelperFunctions $dotcomHelperFunctions
  ) {
    $this->subscriberFieldsFactory = $subscriberFieldsFactory;
    $this->subscribersRepository = $subscribersRepository;
    $this->dotcomHelperFunctions = $dotcomHelperFunctions;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    if ($this->dotcomHelperFunctions->isGarden()) {
      // translators: automation subject (entity entering automation) title
      return __('Subscriber', 'mailpoet');
    }
    // translators: automation subject (entity entering automation) title
    return __('MailPoet subscriber', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      self::SUBJECT_ID_ARG => Builder::integer()->required(),
    ]);
  }

  public function getPayload(SubjectData $subjectData): Payload {
    $id = $subjectData->getArgs()[self::SUBJECT_ID_ARG];
    $subscriber = $this->subscribersRepository->findOneById($id);
    if (!$subscriber) {
      // translators: %d is the ID.
      throw NotFoundException::create()->withMessage(sprintf(__("Subscriber with ID '%d' not found.", 'mailpoet'), $id));
    }
    return new SubscriberPayload($subscriber);
  }

  /** @return Field[] */
  public function getFields(): array {
    return $this->subscriberFieldsFactory->getFields();
  }

  public static function getHashSqlExpression(string $subscriberIdExpression, string $subjectKeyExpression): string {
    return SubjectData::getHashSqlExpression(
      $subjectKeyExpression,
      sprintf(
        "CONCAT('a:1:{s:%d:\"%s\";i:', %s, ';}')",
        strlen(self::SUBJECT_ID_ARG),
        self::SUBJECT_ID_ARG,
        $subscriberIdExpression
      )
    );
  }
}
