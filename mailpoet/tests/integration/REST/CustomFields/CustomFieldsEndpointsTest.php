<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\CustomFields;

use MailPoet\REST\Test;
use MailPoet\Test\DataFactories\CustomField as CustomFieldFactory;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;

require_once __DIR__ . '/../Test.php';

class CustomFieldsEndpointsTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/custom-fields';

  public function _before() {
    parent::_before();
    wp_set_current_user(1);
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }

  public function testGetReturnsCustomFieldsWithCounts(): void {
    $subscriber = (new SubscriberFactory())->withEmail('x@example.com')->create();
    (new CustomFieldFactory())
      ->withName('Favorite color')
      ->withParams(['label' => 'Color'])
      ->withSubscriber($subscriber->getId(), 'blue')
      ->create();
    (new CustomFieldFactory())->withName('Birthday')->create();

    $payload = $this->getListingPayload($this->get(self::BASE_PATH));
    $items = $payload['items'];
    $this->assertCount(2, $items);
    $this->assertSame(2, $payload['meta']['count']);
    $this->assertSame(1, $payload['meta']['pages']);

    $byName = array_column($items, null, 'name');
    $favoriteColor = $byName['Favorite color'] ?? null;
    $birthday = $byName['Birthday'] ?? null;
    $this->assertIsArray($favoriteColor);
    $this->assertIsArray($birthday);
    $this->assertSame('Color', $favoriteColor['label']);
    $this->assertSame(1, $favoriteColor['subscribers_count']);
    $this->assertSame(0, $birthday['subscribers_count']);
  }

  public function testGetSupportsSearchAndPagination(): void {
    (new CustomFieldFactory())->withName('Customers')->create();
    (new CustomFieldFactory())->withName('Prospective')->create();
    (new CustomFieldFactory())->withName('VIP')->create();

    $data = $this->getListingPayload($this->get(self::BASE_PATH, ['query' => ['search' => 'Pro']]));
    $this->assertSame(1, $data['meta']['count']);
    $this->assertSame('Prospective', $data['items'][0]['name']);

    $page1 = $this->getListingPayload($this->get(self::BASE_PATH, ['query' => ['per_page' => 2, 'page' => 1, 'orderby' => 'name', 'order' => 'asc']]));
    $this->assertCount(2, $page1['items']);
    $this->assertSame(2, $page1['meta']['pages']);
  }

  public function testGetRejectsGuest(): void {
    wp_set_current_user(0);
    $data = $this->get(self::BASE_PATH);
    $this->assertSame('rest_forbidden', $data['code']);
  }

  /**
   * @param mixed $response
   * @return array{items: array<int, array<int|string, mixed>>, meta: array<int|string, mixed>}
   */
  private function getListingPayload($response): array {
    $this->assertIsArray($response);
    $data = $response['data'] ?? null;
    $this->assertIsArray($data);
    $items = $data['items'] ?? null;
    $meta = $data['meta'] ?? null;
    $this->assertIsArray($items);
    $this->assertIsArray($meta);

    $normalizedItems = [];
    foreach ($items as $item) {
      $this->assertIsArray($item);
      $normalizedItems[] = $item;
    }

    return [
      'items' => $normalizedItems,
      'meta' => $meta,
    ];
  }
}
