<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WooCommerce\Fields;

use MailPoet\Automation\Engine\Data\Field;
use MailPoet\Automation\Integrations\WooCommerce\Payloads\CustomerPayload;
use MailPoet\WooCommerce\WooCommerceSubscriptions\Helper as WCS;

class CustomerSubscriptionFieldsFactory {
  /** @var WCS */
  private $wcs;

  public function __construct(
    WCS $wcs
  ) {
    $this->wcs = $wcs;
  }

  /** @return Field[] */
  public function getFields(): array {
    return [
      new Field(
        'woocommerce:customer:active-subscription-count',
        Field::TYPE_INTEGER,
        __('Active subscriptions count', 'mailpoet'),
        function (CustomerPayload $payload) {
          $customer = $payload->getCustomer();
          if (!$customer) {
            return 0;
          }
          if (!$this->wcs->isWooCommerceSubscriptionsActive()) {
            return 0;
          }

          $activeSubscriptions = $this->wcs->wcsGetSubscriptions([
            'customer_id' => $customer->get_id(),
            'subscription_status' => ['active', 'pending-cancel'],
            'limit' => -1,
          ]);
          return count($activeSubscriptions);
        }
      ),
    ];
  }
}
