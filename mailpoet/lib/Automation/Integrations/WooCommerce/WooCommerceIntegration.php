<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce;

use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderStatusChangeSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\ProductsSubject;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\OrderStatusChangedTrigger;

class WooCommerceIntegration {

  /** @var OrderStatusChangedTrigger */
  private $orderStatusChangedTrigger;

  /** @var OrderStatusChangeSubject */
  private $orderStatusChangeSubject;

  /** @var OrderSubject */
  private $orderSubject;

  /** @var CustomerSubject */
  private $customerSubject;

  /** @var ProductsSubject */
  private $productsSubject;

  /** @var ContextFactory */
  private $contextFactory;

  public function __construct(
    OrderStatusChangedTrigger $orderStatusChangedTrigger,
    OrderStatusChangeSubject $orderStatusChangeSubject,
    OrderSubject $orderSubject,
    CustomerSubject $customerSubject,
    ProductsSubject $productsSubject,
    ContextFactory $contextFactory
  ) {
    $this->orderStatusChangedTrigger = $orderStatusChangedTrigger;
    $this->orderStatusChangeSubject = $orderStatusChangeSubject;
    $this->orderSubject = $orderSubject;
    $this->customerSubject = $customerSubject;
    $this->productsSubject = $productsSubject;
    $this->contextFactory = $contextFactory;
  }

  public function register(Registry $registry): void {

    $registry->addContextFactory('woocommerce', function () {
      return $this->contextFactory->getContextData();
    });

    $registry->addSubject($this->orderSubject);
    $registry->addSubject($this->orderStatusChangeSubject);
    $registry->addSubject($this->customerSubject);
    $registry->addSubject($this->productsSubject);
    $registry->addTrigger($this->orderStatusChangedTrigger);
  }
}
