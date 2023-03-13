<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\MailPoet\Triggers;

use MailPoet\Automation\Engine\Data\StepRunArgs;
use MailPoet\Automation\Engine\Data\StepValidationArgs;
use MailPoet\Automation\Engine\Data\Subject;
use MailPoet\Automation\Engine\Hooks;
use MailPoet\Automation\Engine\Integration\Trigger;
use MailPoet\Automation\Integrations\MailPoet\Payloads\OrderStatusChangePayload;
use MailPoet\Automation\Integrations\MailPoet\Subjects\CustomerSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\OrderStatusChangeSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\MailPoet\Subjects\SubscriberSubject;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema\ObjectSchema;
use MailPoet\WooCommerce;
use MailPoet\WP\Functions;

class OrderStatusChangedTrigger implements Trigger {

  /** @var Functions */
  private $wp;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  /** @var Segments\WooCommerce */
  private $woocommerce;

  /** @var WooCommerce\Helper */
  private $woocommerceHelper;

  public function __construct(
    Functions $wp,
    SubscribersRepository $subscribersRepository,
    Segments\WooCommerce $wooCommerce,
    WooCommerce\Helper $woocommerceHelper
  ) {
    $this->wp = $wp;
    $this->subscribersRepository = $subscribersRepository;
    $this->woocommerce = $wooCommerce;
    $this->woocommerceHelper = $woocommerceHelper;
  }

  public function getKey(): string {
    return 'mailpoet:order-status-changed';
  }

  public function getName(): string {
    return __('Order status changed', 'mailpoet');
  }

  public function getArgsSchema(): ObjectSchema {
    return Builder::object([
      'from' => Builder::string()->required()->default('any'),
      'to' => Builder::string()->required()->default('wc-completed'),
    ]);
  }

  public function getSubjectKeys(): array {
    return [
      SubscriberSubject::KEY,
      OrderSubject::KEY,
      OrderStatusChangeSubject::KEY,
      CustomerSubject::KEY,
    ];
  }

  public function validate(StepValidationArgs $args): void {
  }

  public function registerHooks(): void {
    $this->wp->addAction(
      'woocommerce_order_status_changed',
      [
        $this,
        'handle',
      ],
      10,
      3
    );
  }

  public function handle(int $orderId, string $oldStatus, string $newStatus): void {
    $order = $this->woocommerceHelper->wcGetOrder($orderId);
    if (!$order instanceof \WC_Order) {
      return;
    }

    $subscriber = $this->findOrCreateSubscriber($order);
    if (!$subscriber instanceof SubscriberEntity) {
      return;
    }

    $this->wp->doAction(Hooks::TRIGGER, $this, [
      new Subject(OrderStatusChangeSubject::KEY, ['from' => $oldStatus, 'to' => $newStatus]),
      new Subject(OrderSubject::KEY, ['order_id' => $order->get_id()]),
      new Subject(CustomerSubject::KEY, ['customer_id' => $order->get_customer_id()]),
      new Subject(SubscriberSubject::KEY, ['subscriber_id' => $subscriber->getId()]),
    ]);
  }

  private function findOrCreateSubscriber(\WC_Order $order): ?SubscriberEntity {
    $subscriber = $this->findSubscriber($order);
    if ($subscriber) {
      return $subscriber;
    }

    $this->woocommerce->synchronizeGuestCustomer($order->get_id());

    return $this->findSubscriber($order);
  }

  private function findSubscriber(\WC_Order $order): ?SubscriberEntity {
    $subscriber = $this->subscribersRepository->findOneBy(['email' => $order->get_billing_email()]);
    if ($subscriber instanceof SubscriberEntity) {
      return $subscriber;
    }

    $subscriber = $this->subscribersRepository->findOneBy(['wpUserId' => $order->get_user_id()]);
    if ($subscriber instanceof SubscriberEntity) {
      return $subscriber;
    }

    return null;
  }

  public function isTriggeredBy(StepRunArgs $args): bool {
    $orderPayload = $args->getSinglePayloadByClass(OrderStatusChangePayload::class);
    if (!$orderPayload instanceof OrderStatusChangePayload) {
      return false;
    }
    $triggerArgs = $args->getStep()->getArgs();
    $configuredFrom = $triggerArgs['from'] ? str_replace('wc-', '', $triggerArgs['from']) : null;
    $configuredTo = $triggerArgs['to'] ? str_replace('wc-', '', $triggerArgs['to']) : null;
    if ($configuredFrom !== 'any' && $orderPayload->getFrom() !== $configuredFrom) {
      return false;
    }
    if ($configuredTo !== 'any' && $orderPayload->getTo() !== $configuredTo) {
      return false;
    }
    return true;
  }
}
