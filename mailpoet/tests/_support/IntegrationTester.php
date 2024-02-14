<?php declare(strict_types = 1);

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
use MailPoet\InvalidStateException;
use MailPoet\Segments\DynamicSegments\Filters\Filter;
use MailPoet\Util\Security;
use MailPoet\WooCommerce\Helper;
use MailPoetVendor\Carbon\Carbon;
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
class IntegrationTester extends \Codeception\Actor {
  use _generated\IntegrationTesterActions;

  /** @var EntityManager */
  private $entityManager;

  private $wpTermIds = [];

  private $wooProductIds = [];

  private $wooOrderIds = [];

  private $wooCouponIds = [];

  private $createdUsers = [];

  private $createdCommentIds = [];

  public function __construct(
    Scenario $scenario
  ) {
    parent::__construct($scenario);
    $this->entityManager = ContainerWrapper::getInstance()->get(EntityManager::class);
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

  /**
   * Deletes a WP user directly from the database without triggering any hooks.
   * Needed to be able to test deleting orphaned subscribers.
   */
  public function deleteWPUserFromDatabase(int $id): void {
    global $wpdb;

    $this->entityManager->getConnection()->executeStatement(
      "DELETE FROM {$wpdb->users} WHERE id = :id",
      ['id' => $id], ['id' => \PDO::PARAM_INT]
    );
  }

  public function createWordPressTerm(string $term, string $taxonomy, array $args = []): int {
    $term = wp_insert_term($term, $taxonomy, $args);
    if ($term instanceof WP_Error) {
      throw new InvalidStateException('Failed to create term');
    }

    $this->wpTermIds[$taxonomy] = $this->wpTermIds[$taxonomy] ?? [];
    $this->wpTermIds[$taxonomy][] = $term['term_id'];
    return $term['term_id'];
  }

  public function createCustomer(string $email, string $role = 'customer'): int {
    return $this->createWordPressUser($email, $role);
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

  public function createWooCommerceProduct(array $data): WC_Product {
    $product = new WC_Product_Simple();

    if (isset($data['name'])) {
      $product->set_name($data['name']);
    }

    if (isset($data['category_ids'])) {
      $product->set_category_ids($data['category_ids']);
    }

    if (isset($data['tag_ids'])) {
      $product->set_tag_ids($data['tag_ids']);
    }

    if (isset($data['price'])) {
      $product->set_price($data['price']);
    }

    if (isset($data['attributes'])) {
      $product->set_attributes($data['attributes']);
    }

    $product->save();

    if (isset($data['attributes'])) {
      // Manually trigger the updating of the product attributes lookup table because we can't depend on the scheduled task running by the time we need the data in tests
      // 1 corresponds to the action to insert lookup data, \Automattic\WooCommerce\Internal\ProductAttributesLookup\LookupDataStore::ACTION_INSERT
      do_action('woocommerce_run_product_attribute_lookup_update_callback', $product->get_id(), 1);
    }

    $this->wooProductIds[] = $product->get_id();
    return $product;
  }

  public function getWooCommerceProductAttribute($name, $options = []): WC_Product_Attribute {
    $attributeId = $this->ensureProductAttributeTaxonomyExists($name, $options);
    $attribute = new WC_Product_Attribute();
    $attribute->set_name('pa_' . $name);
    $attribute->set_id($attributeId);
    $attribute->set_options($options);
    $attribute->set_visible(true);
    $attribute->set_variation(false);
    return $attribute;
  }

  public function getWooCommerceProductAttributeTermId(string $name, string $value): int {
    $term = get_term_by('slug', $value, 'pa_' . $name);
    if (!$term instanceof \WP_Term) {
      throw new \Exception(sprintf("Failed to get term '%s' for attribute '%s'", $value, $name));
    }
    //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    return $term->term_id;
  }

  private function ensureProductAttributeTaxonomyExists(string $name, array $options): int {
    $attributeTaxonomy = 'pa_' . $name;
    $attributeId = wc_attribute_taxonomy_id_by_name($name);
    if (!$attributeId) {
      $args = [
        'name' => $name,
        'slug' => wc_sanitize_taxonomy_name($name),
        'type' => 'select', // or 'text' depending on your needs
        'order_by' => 'menu_order',
        'has_archives' => true,
      ];

      $attributeId = wc_create_attribute($args);
      if (!is_int($attributeId)) {
        throw new \Exception(sprintf("Failed to create attribute '%s'", $name));
      }
    }

    if (!taxonomy_exists($attributeTaxonomy)) {
      register_taxonomy($attributeTaxonomy, 'product');
    }

    foreach ($options as $option) {
      if (!term_exists($option, $attributeTaxonomy)) {
        $this->createWordPressTerm($option, $attributeTaxonomy);
      }
    }

    return $attributeId;
  }

  /**
   * @param array $data - includes default args for wc_create_order plus some extras.
   * The defaults are currently:
   *    'status'        => null,
   *    'customer_id'   => null,
   *    'customer_note' => null,
   *    'parent'        => null,
   *    'created_via'   => null,
   *    'cart_hash'     => null,
   * @return WC_Order
   */
  public function createWooCommerceOrder(array $data = []): \WC_Order {
    $helper = ContainerWrapper::getInstance()->get(Helper::class);
    $order = $helper->wcCreateOrder($data);

    $order->set_billing_email($data['billing_email'] ?? md5($this->uniqueId()) . '@example.com');

    if (isset($data['date_created'])) {
      $order->set_date_created($data['date_created']);
    }

    if (isset($data['billing_postcode'])) {
      $order->set_billing_postcode($data['billing_postcode']);
    }

    if (isset($data['billing_city'])) {
      $order->set_billing_city($data['billing_city']);
    }

    if (isset($data['total'])) {
      $order->set_total($data['total']);
    }

    $order->save();

    $orderId = $order->get_id();
    $this->wooOrderIds[] = $orderId;
    $this->updateWooOrderStats($orderId);

    return $order;
  }

  public function createWooProductReview(int $customerId, string $customerEmail, int $productId, int $rating, Carbon $date = null): int {
    if ($date === null) {
      $date = Carbon::now()->subDay();
    }
    $commentId = wp_insert_comment([
      'comment_type' => 'review',
      'user_id' => $customerId,
      'comment_author_email' => $customerEmail,
      'comment_post_ID' => $productId,
      'comment_parent' => 0,
      'comment_date' => $date->toDateTimeString(),
      'comment_approved' => 1,
      'comment_content' => "This is a $rating star review",
    ]);
    if (!is_int($commentId)) {
      throw new \Exception('Failed to insert review comment');
    }
    add_comment_meta($commentId, 'rating', $rating, true);
    $this->createdCommentIds[] = $commentId;
    return $commentId;
  }

  public function createWooCommerceCoupon(array $data): int {
    $coupon = new WC_Coupon();

    if (isset($data['code'])) {
      $coupon->set_code($data['code']);
    }

    if (isset($data['amount'])) {
      $coupon->set_amount($data['amount']);
    }

    $coupon->save();
    $id = $coupon->get_id();
    $this->wooCouponIds[] = $id;
    return $id;
  }

  public function updateWooOrderStats(int $orderId): void {
    if (class_exists('Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore')) {
      \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore::sync_order($orderId);
    }
    if (class_exists('Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore')) {
      \Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore::sync_order_coupons($orderId);
    }
    if (class_exists('Automattic\WooCommerce\Admin\API\Reports\Products\DataStore')) {
      \Automattic\WooCommerce\Admin\API\Reports\Products\DataStore::sync_order_products($orderId);
    }
  }

  public function deleteWordPressTerms(): void {
    foreach ($this->wpTermIds as $taxonomy => $termIds) {
      foreach ($termIds as $termId) {
        wp_delete_term($termId, $taxonomy);
      }
    }
    $this->wpTermIds = [];
  }

  public function deleteTestWooOrder(int $wooOrderId) {
    $helper = ContainerWrapper::getInstance()->get(Helper::class);
    $order = $helper->wcGetOrder($wooOrderId);
    if ($order instanceof \WC_Order) {
      $order->delete(true);
    }
  }

  public function deleteTestWooProducts(): void {
    $helper = ContainerWrapper::getInstance()->get(Helper::class);
    foreach ($this->wooProductIds as $wooProductId) {
      $product = $helper->wcGetProduct($wooProductId);
      if ($product instanceof WC_Product) {
        $product->delete(true);
      }
    }
    $this->wooProductIds = [];
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

  public function deleteTestWooCoupons(): void {
    foreach ($this->wooCouponIds as $couponId) {
      $coupon = new WC_Coupon($couponId);
      if ($coupon->get_id() > 0) {
        $coupon->delete(true);
      }
    }
    $this->wooCouponIds = [];
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
    verify($date1->getTimestamp())->equalsWithDelta($date2->getTimestamp(), $delta);
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
    $trigger = array_values($automation->getTriggers())[0] ?? null;
    $triggerKey = $trigger ? $trigger->getKey() : '';
    $automationRun = new AutomationRun(
      $automation->getId(),
      $automation->getVersionId(),
      $triggerKey,
      $subjects
    );
    $automationRunStorage = ContainerWrapper::getInstance()->get(AutomationRunStorage::class);
    return $automationRunStorage->getAutomationRun($automationRunStorage->createAutomationRun($automationRun));
  }

  public function getSubscriberEmailsMatchingDynamicFilter(DynamicSegmentFilterData $data, Filter $filter): array {
    $segment = new SegmentEntity('temporary segment ' . Security::generateRandomString(10), SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $filterEntity = new DynamicSegmentFilterEntity($segment, $data);
    $this->entityManager->persist($filterEntity);
    $segment->addDynamicFilter($filterEntity);
    $this->entityManager->flush();
    $queryBuilder = $filter->apply($this->getSubscribersQueryBuilder(), $filterEntity);
    return $this->getSubscriberEmailsFromQueryBuilder($queryBuilder);
  }

  /**
   * @param QueryBuilder $queryBuilder
   * @return string[] - array of subscriber emails
   */
  public function getSubscriberEmailsFromQueryBuilder(QueryBuilder $queryBuilder): array {
    $statement = $queryBuilder->execute();
    $results = $statement instanceof Statement ? $statement->fetchAllAssociative() : [];
    return array_map(function($row) {
      $subscriber = $this->entityManager->find(SubscriberEntity::class, $row['inner_subscriber_id']);
      if (!$subscriber instanceof SubscriberEntity) {
        throw new \Exception('this is for PhpStan');
      }
      return $subscriber->getEmail();
    }, $results);
  }

  public function getSubscribersQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("DISTINCT $subscribersTable.id as inner_subscriber_id")
      ->from($subscribersTable);
  }

  public function cleanup() {
    $this->deleteWordPressTerms();
    $this->deleteCreatedUsers();
    $this->deleteCreatedComments();
    $this->deleteTestWooProducts();
    $this->deleteTestWooOrders();
    $this->deleteTestWooCoupons();
  }

  private function deleteCreatedComments() {
    foreach ($this->createdCommentIds as $commentId) {
      wp_delete_comment($commentId, true);
    }
    $this->createdCommentIds = [];
  }
}
