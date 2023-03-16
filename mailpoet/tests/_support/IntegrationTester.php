<?php declare(strict_types = 1);

use Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore;
use Codeception\Actor;
use Codeception\Scenario;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationRun;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Storage\AutomationRunStorage;
use MailPoet\Automation\Engine\Storage\AutomationStorage;
use MailPoet\Automation\Integrations\Core\Actions\DelayAction;
use MailPoet\DI\ContainerWrapper;
use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Filters\Filter;
use MailPoet\Util\Security;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Doctrine\DBAL\Connection;
use MailPoetVendor\Doctrine\DBAL\Driver\Statement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

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
class IntegrationTester extends Actor {

  /** @var ContainerWrapper */
  protected $diContainer;

  /** @var EntityManager */
  private $entityManager;

  private $wooOrderIds = [];

  private $createdUserEmails = [];

  /** @var Connection */
  private $connection;

  /** @var bool - whether WooCommerce data needs to be cleaned up after a test */
  private $isWooCleanupRequired;

  use _generated\IntegrationTesterActions;

  public function __construct(
    Scenario $scenario
  ) {
    parent::__construct($scenario);
    $this->diContainer = ContainerWrapper::getInstance(WP_DEBUG);
    $this->entityManager = $this->diContainer->get(EntityManager::class);
    $this->connection = $this->diContainer->get(Connection::class);
    $this->isWooCleanupRequired = false;
  }

  public function createWordPressUser(string $email, string $role) {
    $userId = wp_insert_user([
      'user_login' => explode('@', $email)[0],
      'user_email' => $email,
      'role' => $role,
      'user_pass' => '12123154',
    ]);
    if ($userId instanceof \WP_Error) {
      throw new \MailPoet\RuntimeException('Could not create WordPress user: ' . $userId->get_error_message());
    }
    $this->createdUserEmails[] = $email;
    return $userId;
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

  public function deleteCreatedWordpressUsers(): void {
    foreach ($this->createdUserEmails as $email) {
      $this->deleteWordPressUser($email);
    }
    $this->createdUserEmails = [];
  }

  public function createCustomer(string $email, string $role = 'customer'): int {
    $this->isWooCleanupRequired = true;
    global $wpdb;
    $userId = $this->createWordPressUser($email, $role);
    $this->connection->executeQuery("
      INSERT INTO {$wpdb->prefix}wc_customer_lookup (customer_id, user_id, first_name, last_name, email)
      VALUES ({$userId}, {$userId}, 'First Name', 'Last Name', '{$email}')
    ");
    return $userId;
  }

  public function createWooCommerceOrder(array $data = []): \WC_Order {
    $this->isWooCleanupRequired = true;
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

  public function getSubscriberEmailsMatchingDynamicFilter(DynamicSegmentFilterData $data, Filter $filter): array {
    $segment = new SegmentEntity('temporary segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $filterEntity = new DynamicSegmentFilterEntity($segment, $data);
    $this->entityManager->persist($filterEntity);
    $segment->addDynamicFilter($filterEntity);

    $queryBuilder = $filter->apply($this->getSubscribersQueryBuilder(), $filterEntity);
    $statement = $queryBuilder->execute();
    $results = $statement instanceof Statement ? $statement->fetchAllAssociative() : [];
    $emails = array_map(function($row) {
      $subscriber = $this->entityManager->find(SubscriberEntity::class, $row['inner_subscriber_id']);
      if (!$subscriber instanceof SubscriberEntity) {
        throw new \Exception('this is for PhpStan');
      }
      return $subscriber->getEmail();
    }, $results);

    return $emails;
  }

  public function getSubscribersQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id as inner_subscriber_id")
      ->from($subscribersTable);
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

  public function deleteTestData(): void {
    global $wpdb;
    $this->deleteTestWooOrders();
    $this->deleteCreatedWordpressUsers();
    if ($this->isWooCleanupRequired) {
      $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_customer_lookup");
      $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_stats");
      $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}wc_order_product_lookup");
      $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}woocommerce_order_itemmeta");
      $this->connection->executeQuery("TRUNCATE TABLE {$wpdb->prefix}woocommerce_order_items");
      $this->isWooCleanupRequired = false;
    }
  }
}
