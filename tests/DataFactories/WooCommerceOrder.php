<?php

namespace MailPoet\Test\DataFactories;

class WooCommerceOrder {

  /** @var \AcceptanceTester */
  private $tester;

  /** @var array */
  private $data;

  const GUEST_CUSTOMER_ID = 0;

  const STATUS_PENDING = 'pending';
  const STATUS_ON_HOLD = 'on-hold';
  const STATUS_FAILED = 'failed';
  const STATUS_PROCESSING = 'processing';
  const STATUS_COMPLETED = 'completed';
  const STATUS_REFUNDED = 'refunded';

  function __construct(\AcceptanceTester $tester) {
    $unique_id = uniqid();
    $this->tester = $tester;
    $this->data = [
      'customer_id' => self::GUEST_CUSTOMER_ID,
      'status' => self::STATUS_PENDING,
      'billing' => [
        'city' => 'Paris',
        'address_1' => 'Rue Galien 2',
        'country' => 'FR',
        'first_name' => "Guest_First_$unique_id",
        'last_name' => "Guest_Last_$unique_id",
        'email' => "guest_$unique_id@example.com",
      ],
      'products' => null,
    ];
  }

  function withStatus($status) {
    return $this->update(['status' => $status]);
  }

  /**
   * @param array $customer_data Customer created via WooCommerceCustomer factory
   * @return $this
   */
  function withCustomer($customer_data) {
    $billing = $this->data['billing'];
    $billing['first_name'] = $customer_data['first_name'];
    $billing['last_name'] = $customer_data['last_name'];
    $billing['email'] = $customer_data['email'];
    return $this->update(['customer_id' => $customer_data['id'], 'billing' => $billing]);
  }

  /**
   * @param array $products array of Products created via WooCommerceProduct factory
   * @param int[] $quantities
   * @return $this
   */
  function withProducts($products, $quantities = null) {
    $products_data = [];
    foreach ($products as $key => $product) {
      $products_data[] = [
        'product_id' => $product['id'],
        'name' => $product['name'],
        'qty' => isset($quantities[$key]) ? (int)$quantities[$key] : 1,

      ];
    }
    return $this->update(['products' => $products_data]);
  }


  function create() {
    $cmd = "wc shop_order create --porcelain --allow-root --user=admin";
    $cmd .= ' --status=' . $this->data['status'];
    $cmd .= ' --customer_id=' . $this->data['customer_id'];
    $cmd .= " --billing='" . json_encode($this->data['billing']) . "'";
    if (is_array($this->data['products']) && !empty($this->data['products'])) {
      $cmd .= " --line_items='" . json_encode($this->data['products']) . "'";
    }
    $create_output = $this->tester->cliToArray($cmd);
    $order_out = $this->tester->cliToArray("wc shop_order get $create_output[0] --format=json --allow-root --user=admin");
    return json_decode($order_out[0], true);
  }

  private function update($update_data) {
    $data = $this->data;
    foreach ($update_data as $item => $value) {
      $data[$item] = $value;
    }
    $new = clone $this;
    $new->data = $data;
    return $new;
  }
}
