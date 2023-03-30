<?php declare(strict_types = 1);

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore;
use Codeception\Scenario;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Util\Security;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Doctrine\DBAL\Connection;

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

  private $createdUsers = [];

  /** @var Connection */
  private $connection;

  public function __construct(
    Scenario $scenario
  ) {
    parent::__construct($scenario);
    $this->connection = ContainerWrapper::getInstance()->get(Connection::class);
  }

  public function createWordPressUser(string $email, string $role) {
    $userId = wp_insert_user([
      'user_login' => explode('@', $email)[0],
      'user_email' => $email,
      'role' => $role,
      'user_pass' => '12123154',
    ]);

    if ($userId instanceof WP_Error) {
      throw new Exception(sprintf("Unable to create WordPress user with email $email: %s", $userId->get_error_message()));
    }

    $this->createdUsers[] = $email;

    return $userId;
  }

  public function createCustomer(string $email, string $role = 'customer'): int {
    global $wpdb;
    $userId = $this->createWordPressUser($email, $role);
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_customer_lookup (customer_id, user_id, first_name, last_name, email)
      VALUES ({$userId}, {$userId}, 'First Name', 'Last Name', '{$email}')
    ");
    return $userId;
  }

  public function deleteCreatedUsers() {
    foreach ($this->createdUsers as $createdUserEmail) {
      $this->deleteWordPressUser($createdUserEmail);
    }
    $this->createdUsers = [];
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
  public function assertEqualDateTimes(?DateTimeInterface $date1, ?DateTimeInterface $date2, int $delta = 0) {
    if (!$date1 instanceof DateTimeInterface) {
      throw new \Exception('$date1 is not DateTimeInterface');
    }
    if (!$date2 instanceof DateTimeInterface) {
      throw new \Exception('$date2 is not DateTimeInterface');
    }
    expect($date1->getTimestamp())->equals($date2->getTimestamp(), $delta);
  }

  public function createAutomation(string $name, Step ...$steps): ?Automation {
    $automationStorage = ContainerWrapper::getInstance()->get(AutomationStorage::class);

    if (!$steps) {
      $steps[] = new Step('trigger', Step::TYPE_TRIGGER, \MailPoet\Automation\Integrations\MailPoet\Triggers\SomeoneSubscribesTrigger::KEY, [], []);
    }
    //If we only have a trigger, add a delay step to make the automation valid.
    if (count($steps) === 1) {
      $delay = ContainerWrapper::getInstance()->get(DelayAction::class);
      $delayStep = new Step('delay', Step::TYPE_ACTION, $delay->getKey(), [], []);
      $steps[0]->setNextSteps([new NextStep($delayStep->getId())]);
      $steps[] = $delayStep;
    }
    $steps = array_merge(
      [
        'root' => new Step('root', Step::TYPE_ROOT, 'root', [], [new NextStep($steps[0]->getId())]),
      ],
      $steps
    );

    $stepsWithIds = [];
    foreach ($steps as $step) {
      $stepsWithIds[$step->getId()] = $step;
    }
    $automation = new Automation($name, $stepsWithIds, wp_get_current_user());
    $automation->setStatus(Automation::STATUS_ACTIVE);
    return $automationStorage->getAutomation($automationStorage->createAutomation($automation));
  }

  public function createAutomationRun(Automation $automation, $subjects = []): ?AutomationRun {
    $trigger = array_filter($automation->getSteps(), function(Step $step): bool { return $step->getType() === Step::TYPE_TRIGGER;

    });
    $triggerKeys = array_map(function(Step $step): string { return $step->getKey();

    }, $trigger);
    $triggerKey = count($triggerKeys) > 0 ? current($triggerKeys) : '';

    $automationRun = new AutomationRun(
      $automation->getId(),
      $automation->getVersionId(),
      $triggerKey,
      $subjects
    );
    $automationRunStorage = ContainerWrapper::getInstance()->get(AutomationRunStorage::class);
    return $automationRunStorage->getAutomationRun($automationRunStorage->createAutomationRun($automationRun));
  }

  public function cleanup() {
    $this->deleteCreatedUsers();
    $this->deleteTestWooOrders();
  }
}
