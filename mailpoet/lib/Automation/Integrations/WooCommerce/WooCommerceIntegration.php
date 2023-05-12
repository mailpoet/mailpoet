<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce;

use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\AbandonedCartSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\CustomerSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderStatusChangeSubject;
use MailPoet\Automation\Integrations\WooCommerce\Subjects\OrderSubject;
use MailPoet\Automation\Integrations\WooCommerce\SubjectTransformers\WordPressUserSubjectToWooCommerceCustomerSubjectTransformer;
use MailPoet\Automation\Integrations\WooCommerce\Triggers\OrderStatusChangedTrigger;

class WooCommerceIntegration {

  /** @var OrderStatusChangedTrigger */
  private $orderStatusChangedTrigger;

  /** @var AbandonedCartSubject */
  private $abandonedCartSubject;

  /** @var OrderStatusChangeSubject */
  private $orderStatusChangeSubject;

  /** @var OrderSubject */
  private $orderSubject;

  /** @var CustomerSubject */
  private $customerSubject;

  /** @var ContextFactory */
  private $contextFactory;

  /** @var WordPressUserSubjectToWooCommerceCustomerSubjectTransformer */
  private $wordPressUserToWooCommerceCustomerTransformer;

  public function __construct(
    OrderStatusChangedTrigger $orderStatusChangedTrigger,
    AbandonedCartSubject $abandonedCartSubject,
    OrderStatusChangeSubject $orderStatusChangeSubject,
    OrderSubject $orderSubject,
    CustomerSubject $customerSubject,
    ContextFactory $contextFactory,
    WordPressUserSubjectToWooCommerceCustomerSubjectTransformer $wordPressUserToWooCommerceCustomerTransformer
  ) {
    $this->orderStatusChangedTrigger = $orderStatusChangedTrigger;
    $this->abandonedCartSubject = $abandonedCartSubject;
    $this->orderStatusChangeSubject = $orderStatusChangeSubject;
    $this->orderSubject = $orderSubject;
    $this->customerSubject = $customerSubject;
    $this->contextFactory = $contextFactory;
    $this->wordPressUserToWooCommerceCustomerTransformer = $wordPressUserToWooCommerceCustomerTransformer;
  }

  public function register(Registry $registry): void {

    $registry->addContextFactory('woocommerce', function () {
      return $this->contextFactory->getContextData();
    });

    $registry->addSubject($this->abandonedCartSubject);
    $registry->addSubject($this->orderSubject);
    $registry->addSubject($this->orderStatusChangeSubject);
    $registry->addSubject($this->customerSubject);
    $registry->addTrigger($this->orderStatusChangedTrigger);
    $registry->addSubjectTransformer($this->wordPressUserToWooCommerceCustomerTransformer);
  }
}
