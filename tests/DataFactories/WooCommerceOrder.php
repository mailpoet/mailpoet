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
    $unique_id = bin2hex(random_bytes(7)); // phpcs:ignore
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
      'currency' => 'EUR',
      'products' => null,
    ];
  }

  function withStatus($status) {
    return $this->update(['status' => $status]);
  }

  function withDateCreated($date) {
    return $this->update(['date_created' => $date]);
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

  function withCurrency($currency) {
    return $this->update(['currency' => $currency]);
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
        'total' => (string)(isset($product['total']) ? $product['total'] : 10),
      ];
    }
    return $this->update(['products' => $products_data]);
  }


  function create() {
    $cmd = ['wc', 'shop_order', 'create', '--porcelain', '--user=admin'];
    $cmd[] = '--status=' . $this->data['status'];
    $cmd[] = '--customer_id=' . $this->data['customer_id'];
    $cmd[] = '--billing=' . json_encode($this->data['billing']);
    $cmd[] = '--currency=' . $this->data['currency'];
    if (is_array($this->data['products']) && !empty($this->data['products'])) {
      $cmd[] = '--line_items=' . json_encode($this->data['products']);
    }
    $create_output = $this->tester->cliToArray($cmd);
    $order_out = $this->tester->cliToArray(['wc', 'shop_order', 'get', $create_output[0], '--format=json', '--user=admin']);
    $order = json_decode($order_out[0], true);
    if (isset($this->data['date_created'])) {
      wp_update_post([
        'ID' => $order['id'],
        'post_date' => $this->data['date_created'],
        'post_date_gmt' => get_gmt_from_date( $this->data['date_created'] ),
      ]);
    }
    return $order;
  }

  /**
   * @param int $id
   */
  function delete($id) {
    $this->tester->cliToArray(['wc', 'shop_order', 'delete', $id, '--force=1', '--user=admin']);
  }

  function deleteAll() {
    $list = $this->tester->cliToArray(['wc', 'shop_order', 'list', '--format=json', '--user=admin', '--fields=id']);
    foreach (json_decode($list[0], true) as $item) {
      $this->delete($item['id']);
    }
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
