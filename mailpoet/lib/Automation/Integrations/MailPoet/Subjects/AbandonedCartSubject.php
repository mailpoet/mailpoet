<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Subjects;

use MailPoet\Automation\Engine\Data\Subject as SubjectData;
use MailPoet\Automation\Engine\Exceptions\InvalidStateException;
use MailPoet\Automation\Engine\Integration\Payload;
use MailPoet\Automation\Engine\Integration\Subject;
use MailPoet\Automation\Integrations\MailPoet\Payloads\AbandonedCartPayload;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WooCommerce\Helper as WooCommerceHelper;

/**
 * @implements Subject<AbandonedCartPayload>
 */
class AbandonedCartSubject implements Subject {
  const KEY = 'mailpoet:abandoned_cart';

  /** @var WooCommerceHelper */
  private $woocommerceHelper;

  public function __construct(
    WooCommerceHelper $woocommerceHelper
  ) {
    $this->woocommerceHelper = $woocommerceHelper;
  }

  public function getKey(): string {
    return self::KEY;
  }

  public function getName(): string {
    return __('MailPoet Abandoned Cart', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'user_id' => Builder::integer()->required(),
      'last_activity_at' => Builder::string()->required()->default(30),
      'product_ids' => Builder::array(Builder::integer())->required(),
    ]);
  }

  public function getPayload(SubjectData $subjectData): Payload {
    if (!$this->woocommerceHelper->isWooCommerceActive()) {
      throw InvalidStateException::create()->withMessage('WooCommerce is not active');
    }
    $lastActivityAt = \DateTimeImmutable::createFromFormat(\DateTime::W3C, $subjectData->getArgs()['last_activity_at']);
    if (!$lastActivityAt) {
      throw InvalidStateException::create()->withMessage('Invalid abandoned cart time');
    }

    $customer = new \WC_Customer($subjectData->getArgs()['user_id']);

    return new AbandonedCartPayload($customer, $lastActivityAt, $subjectData->getArgs()['product_ids']);
  }

  public function getFields(): array {
    return [];
  }
}
