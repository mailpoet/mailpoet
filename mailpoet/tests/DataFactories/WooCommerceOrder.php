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

  public function __construct(
    \AcceptanceTester $tester
  ) {
    $uniqueId = bin2hex(random_bytes(7)); // phpcs:ignore
    $this->tester = $tester;
    $this->data = [
      'customer_id' => self::GUEST_CUSTOMER_ID,
      'status' => self::STATUS_PENDING,
      'billing' => [
        'city' => 'Paris',
        'address_1' => 'Rue Galien 2',
        'country' => 'FR',
        'first_name' => "Guest_First_$uniqueId",
        'last_name' => "Guest_Last_$uniqueId",
        'email' => "guest_$uniqueId@example.com",
      ],
      'currency' => 'EUR',
      'products' => null,
    ];
  }

  public function withStatus($status) {
    return $this->update(['status' => $status]);
  }

  public function withDateCreated($date) {
    return $this->update(['date_created' => $date]);
  }

  /**
   * @param array $customerData Customer created via WooCommerceCustomer factory
   * @return $this
   */
  public function withCustomer($customerData) {
    $billing = $this->data['billing'];
    $billing['first_name'] = $customerData['first_name'];
    $billing['last_name'] = $customerData['last_name'];
    $billing['email'] = $customerData['email'];
    return $this->update(['customer_id' => $customerData['id'], 'billing' => $billing]);
  }

  public function withCurrency($currency) {
    return $this->update(['currency' => $currency]);
  }

  /**
   * @param array $products array of Products created via WooCommerceProduct factory
   * @param int[] $quantities
   * @return $this
   */
  public function withProducts($products, $quantities = null) {
    $productsData = [];
    foreach ($products as $key => $product) {
      $productsData[] = [
        'product_id' => $product['id'],
        'name' => $product['name'],
        'qty' => isset($quantities[$key]) ? (int)$quantities[$key] : 1,
        'total' => (string)(isset($product['total']) ? $product['total'] : 10),
      ];
    }
    return $this->update(['products' => $productsData]);
  }

  public function create() {
    $cmd = ['wc', 'shop_order', 'create', '--porcelain', '--user=admin'];
    $cmd[] = '--status=' . $this->data['status'];
    $cmd[] = '--customer_id=' . $this->data['customer_id'];
    $cmd[] = '--billing=\'' . json_encode($this->data['billing']) . '\'';
    $cmd[] = '--currency=' . $this->data['currency'];
    if (is_array($this->data['products']) && !empty($this->data['products'])) {
      $cmd[] = '--line_items=\'' . json_encode($this->data['products']) . '\'';
    }
    $createOutput = $this->tester->cliToString($cmd);
    $orderOut = $this->tester->cliToString(['wc', 'shop_order', 'get', $createOutput, '--format=json', '--user=admin']);
    $order = json_decode($orderOut, true);
    if (isset($this->data['date_created'])) {
      $wcOrder = wc_get_order($order['id']);
      if ($wcOrder instanceof \WC_Order) {
        $wcOrder->set_date_created(get_gmt_from_date($this->data['date_created']));
        $wcOrder->set_date_modified(get_gmt_from_date($this->data['date_created']));
        $wcOrder->save();
      }
    }
    return $order;
  }

  /**
   * @param int $id
   */
  public function delete($id) {
    $this->tester->cliToArray(['wc', 'shop_order', 'delete', $id, '--force=1', '--user=admin']);
  }

  public function deleteAll() {
    $list = $this->tester->cliToArray(['wc', 'shop_order', 'list', '--format=json', '--user=admin', '--fields=id']);
    foreach (json_decode($list[0], true) as $item) {
      $this->delete($item['id']);
    }
  }

  private function update($updateData) {
    $data = $this->data;
    foreach ($updateData as $item => $value) {
      $data[$item] = $value;
    }
    $new = clone $this;
    $new->data = $data;
    return $new;
  }
}
