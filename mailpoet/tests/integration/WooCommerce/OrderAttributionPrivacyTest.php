<?php declare(strict_types = 1);

namespace MailPoet\WooCommerce;

use MailPoet\Entities\SubscriberEntity;
use WC_Order;

/**
 * @group woo
 */
class OrderAttributionPrivacyTest extends \MailPoetTest {
  /** @var SubscriberEntity */
  private $subscriber;

  /** @var OrderAttributionPrivacy */
  private $privacy;

  public function _before() {
    parent::_before();
    $this->subscriber = $this->createSubscriber('attribution-privacy@example.com');
    $this->entityManager->flush();
    $this->privacy = $this->diContainer->get(OrderAttributionPrivacy::class);
  }

  public function testItExportsAttributionMetaForOrdersAttributedToTheSubscriber(): void {
    $order = $this->createAttributedOrder($this->subscriber);
    $order->update_meta_data(OrderAttributionReconciler::RECONCILIATION_META_KEY, '{"woo_click_id":11}');
    $order->save_meta_data();

    $result = $this->privacy->export($this->subscriber->getEmail());

    verify($result['done'])->true();
    verify($result['data'])->arrayCount(1);
    $item = $result['data'][0];
    verify($item['group_id'])->equals('mailpoet-woocommerce-order-attribution');
    verify($item['item_id'])->equals('order-' . $order->get_id());
    $values = array_column($item['data'], 'value', 'name');
    verify($values['Order number'])->equals($order->get_order_number());
    verify($values['Email click ID'])->equals('11');
    verify($values['Email ID'])->equals('22');
    verify($values['Sending queue ID'])->equals('33');
    verify($values['Subscriber ID'])->equals((string)$this->subscriber->getId());
    verify($values['Attribution reconciliation record'])->equals('{"woo_click_id":11}');
  }

  public function testItExportsNothingForAnUnknownEmail(): void {
    $this->createAttributedOrder($this->subscriber);

    $result = $this->privacy->export('unknown@example.com');

    verify($result['data'])->arrayCount(0);
    verify($result['done'])->true();
  }

  public function testItErasesPersonalIdentifiersAndKeepsCampaignFields(): void {
    $order = $this->createAttributedOrder($this->subscriber);
    $order->update_meta_data(OrderAttributionReconciler::RECONCILIATION_META_KEY, '{"woo_click_id":11}');
    $order->save_meta_data();

    $result = $this->privacy->erase($this->subscriber->getEmail());

    verify($result['items_removed'])->true();
    verify($result['done'])->true();
    $order = $this->reloadOrder($order);
    verify($order->meta_exists(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_SUBSCRIBER_ID)))->false();
    verify($order->meta_exists(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID)))->false();
    verify($order->meta_exists(OrderAttributionReconciler::RECONCILIATION_META_KEY))->false();
    verify($order->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_NEWSLETTER_ID)))->equals('22');
    verify($order->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_QUEUE_ID)))->equals('33');
  }

  public function testEraseLeavesOrdersOfOtherSubscribersUntouched(): void {
    $otherSubscriber = $this->createSubscriber('other-subscriber@example.com');
    $this->entityManager->flush();
    $this->createAttributedOrder($this->subscriber);
    $otherOrder = $this->createAttributedOrder($otherSubscriber);

    $this->privacy->erase($this->subscriber->getEmail());

    $otherOrder = $this->reloadOrder($otherOrder);
    verify($otherOrder->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_SUBSCRIBER_ID)))->equals((string)$otherSubscriber->getId());
    verify($otherOrder->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID)))->equals('11');
  }

  public function testEraseReportsNothingRemovedForAnUnknownEmail(): void {
    $this->createAttributedOrder($this->subscriber);

    $result = $this->privacy->erase('unknown@example.com');

    verify($result['items_removed'])->false();
    verify($result['done'])->true();
  }

  public function testWpErasureFlowErasesAttributionBeforeSubscriberAnonymization(): void {
    $order = $this->createAttributedOrder($this->subscriber);
    $email = $this->subscriber->getEmail();

    // WordPress's Erase Personal Data tool runs each registered eraser to
    // completion in registration order, always with the original request email
    $erasers = apply_filters('wp_privacy_personal_data_erasers', []);
    foreach ($erasers as $eraser) {
      $page = 1;
      do {
        $response = call_user_func($eraser['callback'], $email, $page);
        $this->assertIsArray($response);
        $page++;
      } while (empty($response['done']));
    }

    $order = $this->reloadOrder($order);
    verify($order->meta_exists(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_SUBSCRIBER_ID)))->false();
    verify($order->meta_exists(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID)))->false();
    $this->entityManager->refresh($this->subscriber);
    verify($this->subscriber->getEmail())->stringNotContainsString($email);
  }

  public function testItDoesNotLeaveTheCptQueryFilterRegisteredAfterErasure(): void {
    $this->createAttributedOrder($this->subscriber);

    $this->privacy->erase($this->subscriber->getEmail());

    $registered = has_filter(
      'woocommerce_order_data_store_cpt_get_orders_query',
      [$this->privacy, 'translateSubscriberIdQueryVar']
    );
    verify($registered)->false();
  }

  public function testItRemovesPersonalIdentifiersOnWooOrderAnonymization(): void {
    $order = $this->createAttributedOrder($this->subscriber);
    $order->update_meta_data(OrderAttributionReconciler::RECONCILIATION_META_KEY, '{"woo_click_id":11}');
    $order->save_meta_data();

    do_action('woocommerce_privacy_remove_order_personal_data', $order);

    $order = $this->reloadOrder($order);
    verify($order->meta_exists(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_SUBSCRIBER_ID)))->false();
    verify($order->meta_exists(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID)))->false();
    verify($order->meta_exists(OrderAttributionReconciler::RECONCILIATION_META_KEY))->false();
    verify($order->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_NEWSLETTER_ID)))->equals('22');
    verify($order->get_meta(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_QUEUE_ID)))->equals('33');
  }

  private function createAttributedOrder(SubscriberEntity $subscriber): WC_Order {
    $order = wc_create_order();
    $this->assertInstanceOf(WC_Order::class, $order);
    $order->set_billing_email($subscriber->getEmail());
    $order->set_total('15');
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_CLICK_ID), '11');
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_NEWSLETTER_ID), '22');
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_QUEUE_ID), '33');
    $order->update_meta_data(OrderAttributionFields::getMetaKey(OrderAttributionFields::FIELD_SUBSCRIBER_ID), (string)$subscriber->getId());
    $order->save();
    return $order;
  }

  private function reloadOrder(WC_Order $order): WC_Order {
    $reloaded = wc_get_order($order->get_id());
    $this->assertInstanceOf(WC_Order::class, $reloaded);
    return $reloaded;
  }

  private function createSubscriber(string $email): SubscriberEntity {
    $subscriber = new SubscriberEntity();
    $subscriber->setEmail($email);
    $subscriber->setFirstName('First');
    $subscriber->setLastName('Last');
    $this->entityManager->persist($subscriber);
    return $subscriber;
  }
}
