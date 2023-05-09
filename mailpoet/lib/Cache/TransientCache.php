<?php declare(strict_types = 1);

namespace MailPoet\Cache;

use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;

class TransientCache {
  public const SUBSCRIBERS_STATISTICS_COUNT_KEY = 'mailpoet_subscribers_statistics_count_cache';
  public const SUBSCRIBERS_GLOBAL_STATUS_STATISTICS_COUNT_KEY = 'mailpoet_subscribers_statistics_count_global_status_cache';
  public const SUBSCRIBERS_HOMEPAGE_STATISTICS_COUNT_KEY = 'mailpoet_subscribers_statistics_count_homepage_cache';

  /** @var WPFunctions */
  private $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function getItem(string $key, int $id): ?array {
    $items = $this->getItems($key);
    return $items[$id] ?? null;
  }

  public function getOldestCreatedAt(string $key): ?\DateTime {
    $oldest = $this->getOldestItem($key);
    return $oldest['created_at'] ?? null;
  }

  public function getOldestItem(string $key): ?array {
    $items = $this->getItems($key);
    $oldest = null;
    foreach ($items as $item) {
      if ($oldest === null || $item['created_at'] < $oldest['created_at']) {
        $oldest = $item;
      }
    }
    return $oldest;
  }

  public function setItem(string $key, array $item, int $id): void {
    $items = $this->getItems($key) ?? [];
    $items[$id] = [
      'item' => $item,
      'created_at' => Carbon::now(),
    ];
    $this->setItems($key, $items);
  }

  public function invalidateItem(string $key, int $id): void {
    $items = $this->getItems($key);
    unset($items[$id]);
    if (count($items)) {
      $this->setItems($key, $items);
    } else {
      $this->deleteItems($key);
    }
  }

  public function invalidateItems(string $key): void {
    $this->deleteItems($key);
  }

  private function deleteItems(string $key): void {
    $this->wp->deleteTransient($key);
  }

  private function setItems(string $key, array $items): void {
    $this->wp->setTransient($key, $items);
  }

  public function getItems(string $key): array {
    return $this->wp->getTransient($key) ?: [];
  }
}
