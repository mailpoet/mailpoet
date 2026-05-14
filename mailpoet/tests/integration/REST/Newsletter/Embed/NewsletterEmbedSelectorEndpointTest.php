<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\Newsletter\Embed;

use MailPoet\REST\Test;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/../../Test.php';

class NewsletterEmbedSelectorEndpointTest extends Test {
  private const BASE_PATH = '/mailpoet/v1/newsletter-embeds';

  public function _before() {
    parent::_before();
    wp_set_current_user(1);
  }

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }

  public function testItReturnsFilteredSelectorRows(): void {
    $old = (new NewsletterFactory())
      ->withSubject('Old embed')
      ->withSentStatus()
      ->withSendingQueue(['processed_at' => new Carbon('2024-01-01 10:00:00')])
      ->create();
    $new = (new NewsletterFactory())
      ->withSubject('New embed')
      ->withSentStatus()
      ->withSendingQueue(['processed_at' => new Carbon('2024-02-01 10:00:00')])
      ->create();
    (new NewsletterFactory())
      ->withSubject('Draft embed')
      ->withDraftStatus()
      ->withSendingQueue(['processed_at' => new Carbon('2024-03-01 10:00:00')])
      ->create();

    $data = $this->get(self::BASE_PATH, ['query' => ['limit' => 10]]);
    $items = $data['data']['items'];
    $ids = array_column($items, 'id');

    $this->assertContains($old->getId(), $ids);
    $this->assertContains($new->getId(), $ids);
    $this->assertLessThan(array_search($old->getId(), $ids, true), array_search($new->getId(), $ids, true));

    foreach ($items as $item) {
      $this->assertArrayHasKey('id', $item);
      $this->assertArrayHasKey('label', $item);
      $this->assertArrayHasKey('subject', $item);
      $this->assertArrayHasKey('sentAt', $item);
      $this->assertArrayHasKey('type', $item);
      $this->assertArrayNotHasKey('url', $item);
      $this->assertArrayNotHasKey('body', $item);
    }
  }

  public function testItSupportsSearchAndLimit(): void {
    (new NewsletterFactory())
      ->withSubject('Alpha embed')
      ->withSentStatus()
      ->withSendingQueue()
      ->create();
    (new NewsletterFactory())
      ->withSubject('Beta embed')
      ->withSentStatus()
      ->withSendingQueue()
      ->create();

    $data = $this->get(self::BASE_PATH, ['query' => ['search' => 'Alpha', 'limit' => 1]]);
    $items = $data['data']['items'];

    $this->assertCount(1, $items);
    $this->assertSame('Alpha embed', $items[0]['subject']);
  }

  public function testItRejectsGuests(): void {
    wp_set_current_user(0);

    $data = $this->get(self::BASE_PATH);

    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testItRejectsSubscribers(): void {
    $userId = wp_create_user('newsletter_embed_subscriber_' . uniqid(), 'password', 'newsletter-embed-subscriber-' . uniqid() . '@localhost.test');
    $this->assertIsInt($userId);
    $user = new \WP_User($userId);
    $user->set_role('subscriber');
    wp_set_current_user($userId);

    $data = $this->get(self::BASE_PATH);

    $this->assertSame('rest_forbidden', $data['code']);
  }
}
