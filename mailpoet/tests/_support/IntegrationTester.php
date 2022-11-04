<?php

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Util\Security;
use MailPoet\WooCommerce\Helper;

require_once(ABSPATH . 'wp-admin/includes/user.php');
require_once(ABSPATH . 'wp-admin/includes/ms.php');

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
// phpcs:ignore PSR1.Classes.ClassDeclaration
class IntegrationTester extends \Codeception\Actor {
  use _generated\IntegrationTesterActions;

  private $wooOrderIds = [];

  public function createWordPressUser(string $email, string $role) {
    return wp_insert_user([
      'user_login' => explode('@', $email)[0],
      'user_email' => $email,
      'role' => $role,
      'user_pass' => '12123154',
    ]);
  }

  public function deleteWordPressUser(string $email) {
    $user = get_user_by('email', $email);
    if (!$user) {
      return;
    }
    if (is_multisite()) {
      wpmu_delete_user($user->ID);
    } else {
      wp_delete_user($user->ID);
    }
  }

  public function createWooCommerceOrder(array $data = []): \WC_Order {
    $helper = ContainerWrapper::getInstance()->get(Helper::class);
    $order = $helper->wcCreateOrder([]);

    if (isset($data['date_created'])) {
      $order->set_date_created($data['date_created']);
    }

    if (isset($data['billing_email'])) {
      $order->set_billing_email($data['billing_email']);
    }

    $order->save();

    $this->wooOrderIds[] = $order->get_id();

    return $order;
  }

  public function updateWooOrderStats(int $orderId): void {
    if (!class_exists('Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore')) {
      return;
    }
    DataStore::sync_order($orderId);
  }

  public function deleteTestWooOrder(int $wooOrderId) {
    $helper = ContainerWrapper::getInstance()->get(Helper::class);
    $order = $helper->wcGetOrder($wooOrderId);
    if ($order instanceof \WC_Order) {
      $order->delete(true);
    }
  }

  public function deleteTestWooOrders() {
    $helper = ContainerWrapper::getInstance()->get(Helper::class);
    foreach ($this->wooOrderIds as $wooOrderId) {
      $order = $helper->wcGetOrder($wooOrderId);
      if ($order instanceof \WC_Order) {
        $order->delete(true);
      }
    }
    $this->wooOrderIds = [];
  }

  public function uniqueId($length = 10): string {
    return Security::generateRandomString($length);
  }

  /**
   * Compares two DateTimeInterface objects by comparing timestamp values.
   * $delta parameter specifies tolerated difference
   */
  public function assertEqualDateTimes(DateTimeInterface $date1 = null, DateTimeInterface $date2 = null, int $delta = 0) {
    if (!$date1 instanceof DateTimeInterface) {
      throw new \Exception('$date1 is not DateTimeInterface');
    }
    if (!$date2 instanceof DateTimeInterface) {
      throw new \Exception('$date2 is not DateTimeInterface');
    }
    expect($date1->getTimestamp())->equals($date2->getTimestamp(), $delta);
  }
}
