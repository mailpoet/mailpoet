<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Tags;

use MailPoet\REST\Test;
use MailPoet\Tags\TagRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;

require_once __DIR__ . '/../Test.php';

class TagsEndpointsTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/tags';

  /** @var TagRepository */
  private $repository;

  /** @var int */
  private $editorUserId;

  public function _before() {
    parent::_before();
    $this->repository = $this->diContainer->get(TagRepository::class);
    wp_set_current_user(1);
    $userId = wp_create_user('tags_editor_' . uniqid(), 'password', 'tags-editor-' . uniqid() . '@localhost.test');
    $this->assertIsNumeric($userId);
    $user = new \WP_User($userId);
    $user->add_role('editor');
    $this->editorUserId = $userId;
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
    is_multisite() ? wpmu_delete_user($this->editorUserId) : wp_delete_user($this->editorUserId);
  }

  public function testGetReturnsTagsWithCounts(): void {
    $customers = (new TagFactory())->withName('Customers')->create();
    (new TagFactory())->withName('Prospects')->create();
    (new SubscriberFactory())->withEmail('x@example.com')->withTags([$customers])->create();

    $payload = $this->getPayload($this->get(self::BASE_PATH));
    $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
    $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
    $this->assertCount(2, $items);
    $this->assertSame(2, $meta['count']);
    $this->assertSame(1, $meta['pages']);

    $byName = array_column($items, null, 'name');
    $this->assertIsArray($byName['Customers']);
    $this->assertIsArray($byName['Prospects']);
    $this->assertSame(1, $byName['Customers']['subscribers_count']);
    $this->assertSame(0, $byName['Prospects']['subscribers_count']);
  }

  /**
   * @param mixed $response
   * @return array<string, mixed>
   */
  private function getPayload($response): array {
    $this->assertIsArray($response);
    $payload = $response['data'] ?? null;
    $this->assertIsArray($payload);
    /** @var array<string, mixed> $payload */
    return $payload;
  }

  public function testGetSupportsSearchAndPagination(): void {
    (new TagFactory())->withName('Customers')->create();
    (new TagFactory())->withName('Prospective')->create();
    (new TagFactory())->withName('VIP')->create();

    $data = $this->get(self::BASE_PATH, ['query' => ['search' => 'Pro']]);
    $this->assertSame(1, $data['data']['meta']['count']);
    $this->assertSame('Prospective', $data['data']['items'][0]['name']);

    $page1 = $this->get(self::BASE_PATH, ['query' => ['per_page' => 2, 'page' => 1, 'orderby' => 'name', 'order' => 'asc']]);
    $this->assertCount(2, $page1['data']['items']);
    $this->assertSame(2, $page1['data']['meta']['pages']);
  }

  public function testGetRejectsGuest(): void {
    wp_set_current_user(0);
    $data = $this->get(self::BASE_PATH);
    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testPostCreatesTag(): void {
    $data = $this->post(self::BASE_PATH, ['json' => ['name' => 'New tag', 'description' => 'desc']]);
    $this->assertSame('New tag', $data['data']['name']);
    $this->assertSame('desc', $data['data']['description']);
    $this->assertSame(0, $data['data']['subscribers_count']);
    $this->assertNotNull($this->repository->findOneBy(['name' => 'New tag']));
  }

  public function testPostRejectsEmptyName(): void {
    $data = $this->post(self::BASE_PATH, ['json' => ['name' => '   ']]);
    $this->assertSame('mailpoet_tags_name_required', $data['code']);
    $this->assertSame(400, $data['data']['status']);
  }

  public function testPostRejectsDuplicateName(): void {
    (new TagFactory())->withName('Duplicate')->create();
    $data = $this->post(self::BASE_PATH, ['json' => ['name' => 'Duplicate']]);
    $this->assertSame('mailpoet_tags_duplicate', $data['code']);
    $this->assertSame(409, $data['data']['status']);
  }

  public function testPutUpdatesTag(): void {
    $tag = (new TagFactory())->withName('Old')->create();

    $data = $this->put(sprintf('%s/%d', self::BASE_PATH, $tag->getId()), [
      'json' => ['name' => 'Renamed', 'description' => 'updated'],
    ]);
    $this->assertSame('Renamed', $data['data']['name']);
    $this->assertSame('updated', $data['data']['description']);

    $refreshed = $this->repository->findOneById($tag->getId());
    $this->assertNotNull($refreshed);
    $this->assertSame('Renamed', $refreshed->getName());
  }

  public function testPutRejectsDuplicateName(): void {
    (new TagFactory())->withName('Existing')->create();
    $tag = (new TagFactory())->withName('Other')->create();

    $data = $this->put(sprintf('%s/%d', self::BASE_PATH, $tag->getId()), [
      'json' => ['name' => 'Existing'],
    ]);
    $this->assertSame('mailpoet_tags_duplicate', $data['code']);
  }

  public function testPutReturns404ForMissingTag(): void {
    $data = $this->put(self::BASE_PATH . '/999999', ['json' => ['name' => 'X']]);
    $this->assertSame('mailpoet_tags_not_found', $data['code']);
    $this->assertSame(404, $data['data']['status']);
  }

  public function testDeleteRemovesTag(): void {
    $tag = (new TagFactory())->withName('Gone')->create();

    $data = $this->delete(sprintf('%s/%d', self::BASE_PATH, $tag->getId()));
    $this->assertSame(['data' => null], $data);

    $this->assertNull($this->repository->findOneById($tag->getId()));
  }

  public function testBulkDeleteRemovesMultipleTags(): void {
    $a = (new TagFactory())->withName('A')->create();
    $b = (new TagFactory())->withName('B')->create();
    $c = (new TagFactory())->withName('C')->create();

    $data = $this->post(self::BASE_PATH . '/bulk-delete', [
      'json' => ['ids' => [(int)$a->getId(), (int)$b->getId()]],
    ]);
    $this->assertSame(2, $data['data']['deleted']);

    $this->assertNull($this->repository->findOneById($a->getId()));
    $this->assertNull($this->repository->findOneById($b->getId()));
    $this->assertNotNull($this->repository->findOneById($c->getId()));
  }

  public function testBulkDeleteRejectsGuest(): void {
    $tag = (new TagFactory())->withName('Keep')->create();
    wp_set_current_user(0);

    $data = $this->post(self::BASE_PATH . '/bulk-delete', [
      'json' => ['ids' => [(int)$tag->getId()]],
    ]);
    $this->assertSame('rest_forbidden', $data['code']);

    $this->assertNotNull($this->repository->findOneById($tag->getId()));
  }
}
