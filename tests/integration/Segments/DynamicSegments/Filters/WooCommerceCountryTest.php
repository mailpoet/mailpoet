<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Subscribers\SubscribersRepository;
use MailPoetVendor\Doctrine\DBAL\ForwardCompatibility\DriverStatement;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;

class WooCommerceCountryTest extends \MailPoetTest {

  /** @var WooCommerceCountry */
  private $wooCommerceCountry;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function _before(): void {
    self::createLookUpTables();
    $this->wooCommerceCountry = $this->diContainer->get(WooCommerceCountry::class);
    $this->subscribersRepository = $this->diContainer->get(SubscribersRepository::class);

    $this->cleanup();

    $userId1 = $this->tester->createWordPressUser('customer1@example.com', 'customer');
    $userId2 = $this->tester->createWordPressUser('customer2@example.com', 'customer');
    $userId3 = $this->tester->createWordPressUser('customer3@example.com', 'customer');
    $userId4 = $this->tester->createWordPressUser('customer4@example.com', 'customer');

    $this->createCustomerLookupData(['user_id' => $userId1, 'email' => 'customer1@example.com', 'country' => 'CZ']);
    $this->createCustomerLookupData(['user_id' => $userId2, 'email' => 'customer2@example.com', 'country' => 'US']);
    $this->createCustomerLookupData(['user_id' => $userId3, 'email' => 'customer3@example.com', 'country' => 'US']);
    $this->createCustomerLookupData(['user_id' => $userId4, 'email' => 'customer4@example.com', 'country' => 'ES']);

  }

  public function testItAppliesFilter(): void {
    $segmentFilter = $this->getSegmentFilter('CZ');
    $queryBuilder = $this->wooCommerceCountry->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    assert($statement instanceof DriverStatement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber1->getEmail())->equals('customer1@example.com');
  }

  public function testItAppliesFilterAny(): void {
    $segmentFilter = $this->getSegmentFilter(['CZ','US']);
    $queryBuilder = $this->wooCommerceCountry->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    assert($statement instanceof DriverStatement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(3);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    assert($subscriber1 instanceof SubscriberEntity);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('customer1@example.com');
    $subscriber2 = $this->subscribersRepository->findOneById($result[1]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber2);
    expect($subscriber2)->isInstanceOf(SubscriberEntity::class);
    expect($subscriber2->getEmail())->equals('customer2@example.com');
    $subscriber3 = $this->subscribersRepository->findOneById($result[2]['inner_subscriber_id']);
    assert($subscriber3 instanceof SubscriberEntity);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber3);
    expect($subscriber3->getEmail())->equals('customer3@example.com');
  }

  public function testItAppliesFilterNone() {
    $segmentFilter = $this->getSegmentFilter(['CZ','US'], DynamicSegmentFilterData::OPERATOR_NONE);
    $queryBuilder = $this->wooCommerceCountry->apply($this->getQueryBuilder(), $segmentFilter);
    $statement = $queryBuilder->execute();
    assert($statement instanceof DriverStatement);
    $result = $statement->fetchAll();
    expect(count($result))->equals(1);
    $subscriber1 = $this->subscribersRepository->findOneById($result[0]['inner_subscriber_id']);
    $this->assertInstanceOf(SubscriberEntity::class, $subscriber1);
    expect($subscriber1->getEmail())->equals('customer4@example.com');
  }

  private function getQueryBuilder(): QueryBuilder {
    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    return $this->entityManager
      ->getConnection()
      ->createQueryBuilder()
      ->select("$subscribersTable.id as inner_subscriber_id")
      ->from($subscribersTable);
  }

  /**
   * @param string[]|string $country
   * @param string $operator
   * @return DynamicSegmentFilterEntity
   */
  private function getSegmentFilter($country, $operator = null): DynamicSegmentFilterEntity {
    $filterData = [
      'country_code' => $country,
    ];
    if ($operator) {
      $filterData['operator'] = $operator;
    }
    $data = new DynamicSegmentFilterData(
      DynamicSegmentFilterData::TYPE_WOOCOMMERCE,
      WooCommerceCountry::ACTION_CUSTOMER_COUNTRY,
      $filterData
    );
    $segment = new SegmentEntity('Dynamic Segment', SegmentEntity::TYPE_DYNAMIC, 'description');
    $this->entityManager->persist($segment);
    $dynamicSegmentFilter = new DynamicSegmentFilterEntity($segment, $data);
    $this->entityManager->persist($dynamicSegmentFilter);
    $segment->addDynamicFilter($dynamicSegmentFilter);
    return $dynamicSegmentFilter;
  }

  private function createCustomerLookupData(array $data) {
    $connection = $this->entityManager->getConnection();
    global $wpdb;
    $customerLookupTable = $wpdb->prefix . 'wc_customer_lookup';
    $connection->executeQuery("
      INSERT INTO {$customerLookupTable} (user_id, first_name, last_name, email, country)
        VALUES (
            {$data['user_id']},
            '',
            '',
            '{$data['email']}',
            '{$data['country']}'
        )
    ");
    $id = $connection->lastInsertId();
    $orderId = (int)$id + 1;
    $orderLookupTable = $wpdb->prefix . 'wc_order_stats';
    $connection->executeQuery("
      INSERT INTO {$orderLookupTable} (order_id, status, customer_id)
        VALUES (
            {$orderId},
            'wc-completed',
            {$id}
        )
    ");
  }

  private function cleanUpLookUpTables(): void {
    $connection = $this->entityManager->getConnection();
    global $wpdb;
    $lookupTable = $wpdb->prefix . 'wc_customer_lookup';
    $orderLookupTable = $wpdb->prefix . 'wc_order_stats';
    $connection->executeStatement("TRUNCATE $lookupTable");
    $connection->executeStatement("TRUNCATE $orderLookupTable");
  }

  public function _after(): void {
    $this->cleanUp();
  }

  private function cleanup(): void {
    $this->truncateEntity(SegmentEntity::class);
    $this->truncateEntity(SubscriberEntity::class);

    $emails = ['customer1@example.com', 'customer2@example.com', 'customer3@example.com', 'customer4@example.com'];
    foreach ($emails as $email) {
      $this->tester->deleteWordPressUser($email);
    }
    $this->cleanUpLookUpTables();
  }

  /**
   * Get WC Lookup Tables database schema.
   * Copied from WC-Admin version 2.9.2-plugin
   *
   * @return string
   */
  protected static function getSchema(): string {
    global $wpdb;

    $collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';

    // Max DB index length. See wp_get_db_schema().
    $maxIndexLength = 191;

    return "
		CREATE TABLE {$wpdb->prefix}wc_order_stats (
			order_id bigint(20) unsigned NOT NULL,
			parent_id bigint(20) unsigned DEFAULT 0 NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			date_created_gmt datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			num_items_sold int(11) DEFAULT 0 NOT NULL,
			total_sales double DEFAULT 0 NOT NULL,
			tax_total double DEFAULT 0 NOT NULL,
			shipping_total double DEFAULT 0 NOT NULL,
			net_total double DEFAULT 0 NOT NULL,
			returning_customer boolean DEFAULT NULL,
			status varchar(200) NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (order_id),
			KEY date_created (date_created),
			KEY customer_id (customer_id),
			KEY status (status({$maxIndexLength}))
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_order_product_lookup (
			order_item_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			variation_id BIGINT UNSIGNED NOT NULL,
			customer_id BIGINT UNSIGNED NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			product_qty INT NOT NULL,
			product_net_revenue double DEFAULT 0 NOT NULL,
			product_gross_revenue double DEFAULT 0 NOT NULL,
			coupon_amount double DEFAULT 0 NOT NULL,
			tax_amount double DEFAULT 0 NOT NULL,
			shipping_amount double DEFAULT 0 NOT NULL,
			shipping_tax_amount double DEFAULT 0 NOT NULL,
			PRIMARY KEY  (order_item_id),
			KEY order_id (order_id),
			KEY product_id (product_id),
			KEY customer_id (customer_id),
			KEY date_created (date_created)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_order_tax_lookup (
			order_id BIGINT UNSIGNED NOT NULL,
			tax_rate_id BIGINT UNSIGNED NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			shipping_tax double DEFAULT 0 NOT NULL,
			order_tax double DEFAULT 0 NOT NULL,
			total_tax double DEFAULT 0 NOT NULL,
			PRIMARY KEY (order_id, tax_rate_id),
			KEY tax_rate_id (tax_rate_id),
			KEY date_created (date_created)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_order_coupon_lookup (
			order_id BIGINT UNSIGNED NOT NULL,
			coupon_id BIGINT NOT NULL,
			date_created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			discount_amount double DEFAULT 0 NOT NULL,
			PRIMARY KEY (order_id, coupon_id),
			KEY coupon_id (coupon_id),
			KEY date_created (date_created)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_admin_notes (
			note_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			type varchar(20) NOT NULL,
			locale varchar(20) NOT NULL,
			title longtext NOT NULL,
			content longtext NOT NULL,
			content_data longtext NULL default null,
			status varchar(200) NOT NULL,
			source varchar(200) NOT NULL,
			date_created datetime NOT NULL default '0000-00-00 00:00:00',
			date_reminder datetime NULL default null,
			is_snoozable boolean DEFAULT 0 NOT NULL,
			layout varchar(20) DEFAULT '' NOT NULL,
			image varchar(200) NULL DEFAULT NULL,
			is_deleted boolean DEFAULT 0 NOT NULL,
			is_read boolean DEFAULT 0 NOT NULL,
			icon varchar(200) NOT NULL default 'info',
			PRIMARY KEY (note_id)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_admin_note_actions (
			action_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			note_id BIGINT UNSIGNED NOT NULL,
			name varchar(255) NOT NULL,
			label varchar(255) NOT NULL,
			query longtext NOT NULL,
			status varchar(255) NOT NULL,
			is_primary boolean DEFAULT 0 NOT NULL,
			actioned_text varchar(255) NOT NULL,
			nonce_action varchar(255) NULL DEFAULT NULL,
			nonce_name varchar(255) NULL DEFAULT NULL,
			PRIMARY KEY (action_id),
			KEY note_id (note_id)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_customer_lookup (
			customer_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED DEFAULT NULL,
			username varchar(60) DEFAULT '' NOT NULL,
			first_name varchar(255) NOT NULL,
			last_name varchar(255) NOT NULL,
			email varchar(100) NULL default NULL,
			date_last_active timestamp NULL default null,
			date_registered timestamp NULL default null,
			country char(2) DEFAULT '' NOT NULL,
			postcode varchar(20) DEFAULT '' NOT NULL,
			city varchar(100) DEFAULT '' NOT NULL,
			state varchar(100) DEFAULT '' NOT NULL,
			PRIMARY KEY (customer_id),
			UNIQUE KEY user_id (user_id),
			KEY email (email)
		) $collate;
		CREATE TABLE {$wpdb->prefix}wc_category_lookup (
			category_tree_id BIGINT UNSIGNED NOT NULL,
			category_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (category_tree_id,category_id)
		) $collate;
		";
  }

  /**
   * Create WC Lookup database tables.
   */
  public static function createLookUpTables() {
    if ((boolean)getenv('MULTISITE') === true) {
      $wpUpgradePath = getenv('WP_ROOT_MULTISITE');
    } else {
      $wpUpgradePath = getenv('WP_ROOT');
    }
    require_once($wpUpgradePath . '/wp-admin/includes/upgrade.php');

    dbDelta( self::getSchema() );
  }
}
