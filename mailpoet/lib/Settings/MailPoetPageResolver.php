<?php declare(strict_types = 1);

namespace MailPoet\Settings;

use MailPoet\Logging\LoggerFactory;
use MailPoet\Util\Security;
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

  // Both default pages host any MailPoet router endpoint through [mailpoet_page].
  private const OTHER_KIND = [
    Pages::PAGE_SUBSCRIPTIONS => Pages::PAGE_CAPTCHA,
    Pages::PAGE_CAPTCHA => Pages::PAGE_SUBSCRIPTIONS,
  ];

  private WPFunctions $wp;

  private SettingsController $settings;

  private LoggerFactory $loggerFactory;

  /** @var array<string, \WP_Post|null> */
  private array $pages = [];

  public function __construct(
    WPFunctions $wp,
    SettingsController $settings,
    LoggerFactory $loggerFactory
  ) {
    $this->wp = $wp;
    $this->settings = $settings;
    $this->loggerFactory = $loggerFactory;
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
    foreach ([$defaultPostName, self::OTHER_KIND[$defaultPostName] ?? null] as $postName) {
      if ($page || $postName === null) {
        break;
      }
      $default = Pages::getMailPoetPage($postName);
      $page = $default instanceof \WP_Post ? $default : null;
    }
    $this->pages[$settingKey] = $page;
    return $page;
  }

  /**
   * @param \WP_Post|int|null $post
   */
  public function getPermalinkOrHome($post): string {
    $url = $post ? $this->wp->getPermalink($post) : false;
    return is_string($url) && $url !== '' ? $url : $this->wp->homeUrl('/');
  }

  public function resetCache(): void {
    $this->pages = [];
  }

  public function registerPageChangeHooks(): void {
    $this->wp->addAction('trashed_post', [$this, 'handlePageChange']);
    $this->wp->addAction('after_delete_post', [$this, 'handlePageChange'], 10, 2);
    $this->wp->addAction('transition_post_status', [$this, 'handleStatusTransition'], 10, 3);
  }

  public function maybeRepairPages(): void {
    if (!$this->wp->currentUserCan('manage_options') || $this->wp->wpDoingAjax()) {
      return;
    }
    $this->safelyRepairPages();
  }

  /**
   * @param int|string $postId
   * @param \WP_Post|null $post
   */
  public function handlePageChange($postId, $post = null): void {
    $postId = (int)$postId;
    if (!$this->isRelevantPost($postId, $post)) {
      return;
    }
    $this->safelyRepairPages();
  }

  /**
   * @param mixed $post
   */
  public function handleStatusTransition(string $newStatus, string $oldStatus, $post): void {
    if ($newStatus === 'publish' || !$post instanceof \WP_Post) {
      return;
    }
    $this->handlePageChange((int)$post->ID, $post);
  }

  private function isRelevantPost(int $postId, ?\WP_Post $post): bool {
    if ($postId <= 0) {
      return false;
    }
    foreach (array_keys(self::REPAIRABLE_KEYS) as $key) {
      if ((int)$this->settings->get($key) === $postId) {
        return true;
      }
    }
    $post = $post ?? $this->wp->getPost($postId);
    return $post instanceof \WP_Post && $post->post_type === 'mailpoet_page'; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  private function safelyRepairPages(): void {
    try {
      $this->repairPages();
    } catch (\Throwable $e) {
      // Repairing pages must never break loading of the admin, not even when logging fails.
      try {
        $this->loggerFactory->getLogger(LoggerFactory::TOPIC_PAGES)->error(
          'Repairing MailPoet pages failed: ' . $e->getMessage(),
          ['exception' => get_class($e), 'file' => $e->getFile(), 'line' => $e->getLine()]
        );
      } catch (\Throwable $loggingError) {
        return;
      }
    }
  }

  public function repairPages(): void {
    if (!$this->getStaleKeys()) {
      return;
    }
    $lock = $this->acquireLock();
    if ($lock === null) {
      return;
    }
    try {
      $this->fetchSettings();
      $staleKeys = $this->getStaleKeys();
      if (!$staleKeys) {
        return;
      }

      $pageIds = [];
      foreach (array_unique(array_values($staleKeys)) as $kind) {
        $pageId = $this->obtainDefaultPage($kind);
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
      $this->releaseLock($lock);
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
      if ($this->isStalePost($post)) {
        $stale[$key] = $kind;
      }
    }
    return $stale;
  }

  /**
   * @param \WP_Post|mixed $post
   */
  private function isStalePost($post): bool {
    if (!$post instanceof \WP_Post) {
      return true;
    }
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    return $post->post_status === 'trash'
      || ($post->post_type === 'mailpoet_page' && $post->post_status !== 'publish');
    // phpcs:enable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  private function fetchSettings(): void {
    $this->settings->fetch('subscription');
    $this->settings->fetch('reEngagement');
  }

  private function acquireLock(): ?string {
    $token = time() . ':' . Security::generateRandomString(12);
    if ($this->wp->addOption(self::LOCK_OPTION, $token, false)) {
      return $token;
    }
    $this->clearLockCache();
    $current = $this->wp->getOption(self::LOCK_OPTION, '');
    $lockedAt = (int)$current;
    if (!is_string($current) || $current === '' || $lockedAt > time() - self::LOCK_TTL) {
      return null;
    }
    return $this->swapLock($current, $token) ? $token : null;
  }

  private function releaseLock(string $token): void {
    global $wpdb;
    $wpdb->query($wpdb->prepare(
      "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
      self::LOCK_OPTION,
      $token
    ));
    $this->clearLockCache();
  }

  private function swapLock(string $expected, string $token): bool {
    global $wpdb;
    $changed = $wpdb->query($wpdb->prepare(
      "UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
      $token,
      self::LOCK_OPTION,
      $expected
    ));
    $this->clearLockCache();
    return $changed === 1;
  }

  private function clearLockCache(): void {
    $this->wp->wpCacheDelete(self::LOCK_OPTION, 'options');
    $this->wp->wpCacheDelete('notoptions', 'options');
    $this->wp->wpCacheDelete('alloptions', 'options');
  }

  private function obtainDefaultPage(string $postName): ?int {
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
      if (!$this->isUntouchedContent((string)$candidate->post_content)) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        continue;
      }
      if ((int)$this->wp->wpUpdatePost(['ID' => $id, 'post_status' => 'publish']) > 0) {
        $this->wp->deletePostMeta($id, '_wp_trash_meta_status');
        $this->wp->deletePostMeta($id, '_wp_trash_meta_time');
        return $id;
      }
    }

    $created = Pages::createMailPoetPage($postName, false);
    return $created ? (int)$created : null;
  }

  private function isUntouchedContent(string $content): bool {
    // Strips only block delimiters (<!-- wp:name {json} -->, <!-- /wp:name -->, <!-- wp:name /-->) and <p> tags; other comments are custom content
    $stripped = (string)preg_replace('/<!--\s+\/?wp:[a-z][a-z0-9_-]*(?:\/[a-z][a-z0-9_-]*)?(?:\s+\{.*?\})?\s*\/?-->|<\/?p>/s', '', $content);
    return trim($stripped) === '[mailpoet_page]';
  }
}
