<?php declare(strict_types = 1);

namespace MailPoet\Settings;

use MailPoet\WP\Functions as WPFunctions;

class MailPoetPageResolver {
  private const LOCK_OPTION = 'mailpoet_pages_repair_lock';
  private const LOCK_TTL = 60;
  private const RE_ENGAGEMENT_KEY = 'reEngagement.page';
  private const REPAIRABLE_KEYS = [
    'subscription.pages.confirmation' => Pages::PAGE_SUBSCRIPTIONS,
    'subscription.pages.confirm_unsubscribe' => Pages::PAGE_SUBSCRIPTIONS,
    'subscription.pages.manage' => Pages::PAGE_SUBSCRIPTIONS,
    'subscription.pages.unsubscribe' => Pages::PAGE_SUBSCRIPTIONS,
    'subscription.pages.captcha' => Pages::PAGE_CAPTCHA,
    self::RE_ENGAGEMENT_KEY => Pages::PAGE_SUBSCRIPTIONS,
  ];

  private WPFunctions $wp;

  private SettingsController $settings;

  /** @var array<string, \WP_Post|null> */
  private array $pages = [];

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
  }

  /**
   * @param mixed $pageId
   */
  public function getPublishedPage($pageId): ?\WP_Post {
    $id = is_numeric($pageId) ? (int)$pageId : 0;
    if ($id <= 0) {
      return null;
    }
    $post = $this->wp->getPost($id);
    if (!$post instanceof \WP_Post || $post->post_status !== 'publish') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return null;
    }
    return $post;
  }

  public function getPage(string $settingKey, string $defaultPostName): ?\WP_Post {
    if (array_key_exists($settingKey, $this->pages)) {
      return $this->pages[$settingKey];
    }
    $page = $this->getPublishedPage($this->settings->get($settingKey));
    if (!$page) {
      $default = Pages::getMailPoetPage($defaultPostName);
      $page = $default instanceof \WP_Post ? $default : null;
    }
    $this->pages[$settingKey] = $page;
    return $page;
  }

  public function resetCache(): void {
    $this->pages = [];
  }

  public function maybeRepairPages(): void {
    if (!$this->wp->currentUserCan('manage_options') || $this->wp->wpDoingAjax()) {
      return;
    }
    try {
      $this->repairPages();
    } catch (\Throwable $e) {
      // Repairing pages must never break loading of the admin.
      if (function_exists('error_log')) {
        // phpcs:disable QITStandard.PHP.DebugCode.DebugFunctionFound
        error_log('[MailPoet] Repairing MailPoet pages failed: ' . (string)$e); // phpcs:ignore Squiz.PHP.DiscouragedFunctions
        // phpcs:enable QITStandard.PHP.DebugCode.DebugFunctionFound
      }
    }
  }

  public function repairPages(): void {
    if (!$this->getStaleKeys()) {
      return;
    }
    if (!$this->acquireLock()) {
      return;
    }
    try {
      $this->fetchSettings();
      $staleKeys = $this->getStaleKeys();
      if (!$staleKeys) {
        return;
      }

      $referencedIds = $this->getReferencedPageIds($staleKeys);
      $pageIds = [];
      foreach (array_unique(array_values($staleKeys)) as $kind) {
        $pageId = $this->obtainDefaultPage($kind, $referencedIds);
        if ($pageId) {
          $pageIds[$kind] = $pageId;
        }
      }
      if (!$pageIds) {
        return;
      }

      $this->fetchSettings();
      foreach ($this->getStaleKeys() as $key => $kind) {
        if (isset($pageIds[$kind])) {
          $this->settings->set($key, $pageIds[$kind]);
        }
      }
      $this->resetCache();
    } finally {
      $this->wp->deleteOption(self::LOCK_OPTION);
    }
  }

  /**
   * @return array<string, string> stale setting key => page kind
   */
  private function getStaleKeys(): array {
    $stale = [];
    foreach (self::REPAIRABLE_KEYS as $key => $kind) {
      $id = (int)$this->settings->get($key);
      if ($id <= 0) {
        if ($key !== self::RE_ENGAGEMENT_KEY) {
          $stale[$key] = $kind;
        }
        continue;
      }
      $post = $this->wp->getPost($id);
      if (!$post instanceof \WP_Post || $post->post_status === 'trash') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $stale[$key] = $kind;
      }
    }
    return $stale;
  }

  /**
   * @param array<string, string> $staleKeys
   * @return int[]
   */
  private function getReferencedPageIds(array $staleKeys): array {
    $ids = [];
    foreach (array_keys(self::REPAIRABLE_KEYS) as $key) {
      $id = (int)$this->settings->get($key);
      if (!isset($staleKeys[$key]) && $id > 0) {
        $ids[] = $id;
      }
    }
    return $ids;
  }

  private function fetchSettings(): void {
    $this->settings->fetch('subscription');
    $this->settings->fetch('reEngagement');
  }

  private function acquireLock(): bool {
    if ($this->wp->addOption(self::LOCK_OPTION, time(), false)) {
      return true;
    }
    $lockedAt = (int)$this->wp->getOption(self::LOCK_OPTION, 0);
    if ($lockedAt > time() - self::LOCK_TTL) {
      return false;
    }
    $this->wp->updateOption(self::LOCK_OPTION, time());
    return true;
  }

  /**
   * @param int[] $referencedIds
   */
  private function obtainDefaultPage(string $postName, array $referencedIds): ?int {
    $published = Pages::getMailPoetPage($postName);
    if ($published instanceof \WP_Post) {
      return (int)$published->ID;
    }

    $candidates = $this->wp->getPosts([
      'post_type' => 'mailpoet_page',
      'post_name__in' => [$postName, $postName . '__trashed'],
      'post_status' => ['draft', 'pending', 'private', 'future', 'trash'],
      'orderby' => 'date',
      'order' => 'DESC',
      'numberposts' => -1,
    ]);
    foreach ($candidates as $candidate) {
      $id = (int)$candidate->ID;
      if (
        in_array($id, $referencedIds, true)
        || strpos((string)$candidate->post_content, '[mailpoet_page]') === false // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      ) {
        continue;
      }
      if ((int)$this->wp->wpUpdatePost(['ID' => $id, 'post_status' => 'publish']) > 0) {
        return $id;
      }
    }

    $created = Pages::createMailPoetPage($postName, false);
    return $created ? (int)$created : null;
  }
}
