<?php declare(strict_types = 1);

namespace MailPoet\Test\Settings;

use Codeception\Stub;
use MailPoet\Config\Populator;
use MailPoet\Entities\LogEntity;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Logging\LogRepository;
use MailPoet\Settings\MailPoetPageResolver;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\Settings\SettingsRepository;
use MailPoet\WP\Functions as WPFunctions;

class MailPoetPageResolverTest extends \MailPoetTest {
  private const LOCK = 'mailpoet_pages_repair_lock';
  private const STATUSES = ['publish', 'draft', 'pending', 'private', 'future', 'trash'];

  private MailPoetPageResolver $resolver;
  private SettingsController $settings;

  /** @var array<int, array{status: string, name: string, content: string}> */
  private array $snapshot = [];

  /** @var int[] */
  private array $userIds = [];

  private bool $hooksWereRegistered = false;

  public function _before() {
    parent::_before();
    require_once ABSPATH . 'wp-admin/includes/user.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
    require_once ABSPATH . 'wp-admin/includes/screen.php';
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->resolver = $this->diContainer->get(MailPoetPageResolver::class);
    foreach ($this->allPages() as $page) {
      $this->snapshot[(int)$page->ID] = ['status' => $page->post_status, 'name' => $page->post_name, 'content' => $page->post_content]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }
    $this->diContainer->get(Populator::class)->up();
    foreach ($this->allPages() as $page) {
      wp_update_post(['ID' => $page->ID, 'post_content' => '[mailpoet_page]']);
    }
    $this->settings->resetCache();
    $this->resolver->resetCache();
    delete_option(self::LOCK);
    $this->hooksWereRegistered = (bool)has_action('trashed_post', [$this->resolver, 'handlePageChange']);
    $this->removePageChangeHooks();
  }

  public function _after() {
    $this->removePageChangeHooks();
    delete_option(self::LOCK);
    wp_set_current_user(0);
    foreach ($this->userIds as $userId) {
      wp_delete_user($userId);
    }
    $this->userIds = [];
    $GLOBALS['current_screen'] = null;
    unset($GLOBALS['post']);
    foreach ($this->allPages() as $page) {
      if (!isset($this->snapshot[(int)$page->ID])) {
        wp_delete_post((int)$page->ID, true);
      }
    }
    foreach ($this->snapshot as $id => $data) {
      if (get_post($id)) {
        wp_update_post(['ID' => $id, 'post_status' => $data['status'], 'post_name' => $data['name'], 'post_content' => $data['content']]);
        continue;
      }
      Pages::createMailPoetPage(preg_replace('/__trashed$/', '', $data['name']));
    }
    $this->resolver->resetCache();
    $this->settings->resetCache();
    if ($this->hooksWereRegistered) {
      $this->resolver->registerPageChangeHooks();
    }
    parent::_after();
  }

  public function testGetPublishedPageReturnsPublishedPost() {
    $id = $this->createPage('resolver-published');
    $page = $this->resolver->getPublishedPage($id);
    $this->assertInstanceOf(\WP_Post::class, $page);
    $this->assertSame($id, (int)$page->ID);
  }

  public function testGetPublishedPageReturnsNullForNonPublishedOrMissing() {
    $published = $this->createPage('resolver-control');
    $this->assertNotNull($this->resolver->getPublishedPage($published));
    $draft = $this->createPage('resolver-draft', 'draft');
    $private = $this->createPage('resolver-private', 'private');
    $trashed = $this->createPage('resolver-trashed');
    wp_trash_post($trashed);
    $deleted = $this->createPage('resolver-deleted');
    wp_delete_post($deleted, true);

    foreach ([$draft, $private, $trashed, $deleted, 0, '', null] as $value) {
      $this->assertNull($this->resolver->getPublishedPage($value), var_export($value, true));
    }
  }

  public function testGetPublishedPageDoesNotReturnGlobalPostForZero() {
    $control = $this->createPage('resolver-global');
    $this->assertNotNull($this->resolver->getPublishedPage($control));
    $GLOBALS['post'] = get_post($control);
    $this->assertNull($this->resolver->getPublishedPage(0));
    $this->assertNull($this->resolver->getPublishedPage(null));
  }

  public function testGetPageReturnsConfiguredPublishedPage() {
    $id = $this->createPage('resolver-configured');
    $this->settings->set('subscription.pages.manage', $id);
    $page = $this->resolver->getPage('subscription.pages.manage', Pages::PAGE_SUBSCRIPTIONS);
    $this->assertInstanceOf(\WP_Post::class, $page);
    $this->assertSame($id, (int)$page->ID);
  }

  public function testGetPageFallsBackToDefaultWhenConfiguredIsTrashed() {
    $id = $this->createPage('resolver-configured');
    wp_trash_post($id);
    $this->settings->set('subscription.pages.manage', $id);
    $page = $this->resolver->getPage('subscription.pages.manage', Pages::PAGE_SUBSCRIPTIONS);
    $this->assertInstanceOf(\WP_Post::class, $page);
    $this->assertSame(Pages::PAGE_SUBSCRIPTIONS, $page->post_name); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testGetPageReturnsNullWhenNothingExists() {
    $this->assertNotNull($this->resolver->getPage('subscription.pages.captcha', Pages::PAGE_CAPTCHA));
    $this->deleteAllPages();
    $this->settings->set('subscription.pages.captcha', 999999);
    $this->resolver->resetCache();
    $this->assertNull($this->resolver->getPage('subscription.pages.captcha', Pages::PAGE_CAPTCHA));
  }

  public function testGetPageFallsBackToSubscriptionsPageWhenCaptchaPageIsMissing() {
    $this->deletePagesBySlug(Pages::PAGE_CAPTCHA);
    $this->settings->set('subscription.pages.captcha', 999999);
    $this->resolver->resetCache();
    $page = $this->resolver->getPage('subscription.pages.captcha', Pages::PAGE_CAPTCHA);
    $this->assertInstanceOf(\WP_Post::class, $page);
    $this->assertSame(Pages::PAGE_SUBSCRIPTIONS, $page->post_name); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testGetPageFallsBackToCaptchaPageWhenSubscriptionsPageIsMissing() {
    $this->deletePagesBySlug(Pages::PAGE_SUBSCRIPTIONS);
    $this->settings->set('subscription.pages.manage', 999999);
    $this->resolver->resetCache();
    $page = $this->resolver->getPage('subscription.pages.manage', Pages::PAGE_SUBSCRIPTIONS);
    $this->assertInstanceOf(\WP_Post::class, $page);
    $this->assertSame(Pages::PAGE_CAPTCHA, $page->post_name); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testGetPageDoesNotUseOtherKindWhenOwnKindIsPublished() {
    $page = $this->resolver->getPage('subscription.pages.captcha', Pages::PAGE_CAPTCHA);
    $this->assertInstanceOf(\WP_Post::class, $page);
    $this->assertSame(Pages::PAGE_CAPTCHA, $page->post_name); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
  }

  public function testGetPageSkipsUnpublishedOtherKindPage() {
    $this->deletePagesBySlug(Pages::PAGE_CAPTCHA);
    foreach ($this->pagesBySlug(Pages::PAGE_SUBSCRIPTIONS) as $page) {
      wp_update_post(['ID' => $page->ID, 'post_status' => 'draft']);
    }
    $this->settings->set('subscription.pages.captcha', 999999);
    $this->resolver->resetCache();
    $this->assertNull($this->resolver->getPage('subscription.pages.captcha', Pages::PAGE_CAPTCHA));
  }

  public function testGetPageIsMemoizedUntilCacheReset() {
    $id = $this->createPage('resolver-memo');
    $this->settings->set('subscription.pages.manage', $id);
    $first = $this->resolver->getPage('subscription.pages.manage', Pages::PAGE_SUBSCRIPTIONS);
    $this->assertInstanceOf(\WP_Post::class, $first);
    wp_delete_post($id, true);
    $second = $this->resolver->getPage('subscription.pages.manage', Pages::PAGE_SUBSCRIPTIONS);
    $this->assertInstanceOf(\WP_Post::class, $second);
    $this->assertSame($id, (int)$second->ID);
    $this->resolver->resetCache();
    $third = $this->resolver->getPage('subscription.pages.manage', Pages::PAGE_SUBSCRIPTIONS);
    $this->assertInstanceOf(\WP_Post::class, $third);
    $this->assertNotSame($id, (int)$third->ID);
  }

  public function testGetPageNeverWrites() {
    $this->deleteAllPages();
    $this->settings->set('subscription.pages.captcha', 999999);
    $this->resolver->getPage('subscription.pages.captcha', Pages::PAGE_CAPTCHA);
    $this->resolver->repairPages();
    $this->assertGreaterThan(0, count($this->allPages()));
    $this->deleteAllPages();
    $this->settings->set('subscription.pages.captcha', 999999);
    $this->resolver->resetCache();
    $this->resolver->getPage('subscription.pages.captcha', Pages::PAGE_CAPTCHA);
    $this->assertCount(0, $this->allPages());
    $this->assertSame(999999, (int)$this->settings->fetch('subscription.pages.captcha'));
  }

  public function testRepairPointsDeletedConfiguredPageToDefaultWithoutCreatingPages() {
    $id = $this->createPage('resolver-configured');
    $this->settings->set('subscription.pages.manage', $id);
    wp_delete_post($id, true);
    $before = count($this->allPages());
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;

    $this->resolver->repairPages();

    $this->assertSame($defaultId, (int)$this->settings->fetch('subscription.pages.manage'));
    $this->assertCount($before, $this->allPages());
  }

  public function testRepairCreatesOnePagePerKindWhenAllPagesAreGone() {
    $this->deleteAllPages();
    $this->staleAllKeys();

    $this->resolver->repairPages();
    $this->resolver->repairPages();

    $this->assertCount(1, $this->pagesBySlug(Pages::PAGE_SUBSCRIPTIONS));
    $this->assertCount(1, $this->pagesBySlug(Pages::PAGE_CAPTCHA));
    $subscriptionsId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    $captchaId = (int)Pages::getMailPoetPage(Pages::PAGE_CAPTCHA)->ID;
    foreach (['confirmation', 'confirm_unsubscribe', 'manage', 'unsubscribe'] as $key) {
      $this->assertSame($subscriptionsId, (int)$this->settings->fetch('subscription.pages.' . $key));
    }
    $this->assertSame($captchaId, (int)$this->settings->fetch('subscription.pages.captcha'));
    $this->assertSame(0, (int)$this->settings->fetch('reEngagement.page'));
    $this->assertFalse(get_option(self::LOCK));
  }

  public function testRepairRepublishesTrashedDefaultPage() {
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    wp_trash_post($defaultId);
    $this->settings->set('subscription.pages.manage', $defaultId);
    $before = count($this->allPages());

    $this->resolver->repairPages();

    $this->assertSame('publish', get_post_status($defaultId));
    $this->assertSame(Pages::PAGE_SUBSCRIPTIONS, get_post_field('post_name', $defaultId));
    $this->assertCount($before, $this->allPages());
    $this->assertSame($defaultId, (int)$this->settings->fetch('subscription.pages.manage'));
  }

  public function testRepairClearsTrashMetadataWhenRepublishingTrashedPage() {
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    wp_trash_post($defaultId);
    $this->assertNotEmpty(get_post_meta($defaultId, '_wp_trash_meta_status', true));
    $this->assertNotEmpty(get_post_meta($defaultId, '_wp_trash_meta_time', true));

    $this->resolver->repairPages();

    $this->assertSame('publish', get_post_status($defaultId));
    $this->assertEmpty(get_post_meta($defaultId, '_wp_trash_meta_status', true));
    $this->assertEmpty(get_post_meta($defaultId, '_wp_trash_meta_time', true));
  }

  public function testRepairCreatesNewPageWhenSlugHolderHasNoShortcode() {
    $this->deleteAllPages();
    wp_insert_post([
      'post_type' => 'mailpoet_page',
      'post_status' => 'draft',
      'post_name' => Pages::PAGE_SUBSCRIPTIONS,
      'post_content' => 'No shortcode here',
      'post_title' => 'Draft',
    ]);
    $this->staleAllKeys();

    $this->resolver->repairPages();
    $count = count($this->allPages());
    $this->resolver->repairPages();

    $page = $this->resolver->getPublishedPage($this->settings->fetch('subscription.pages.manage'));
    $this->assertNotNull($page);
    $this->assertStringContainsString('[mailpoet_page]', $page->post_content); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $this->assertSame($count, count($this->allPages()));
  }

  public function testRepairDoesNotPublishPrivateDefaultPageWithCustomContent() {
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    $content = 'Secret text [mailpoet_page]';
    wp_update_post(['ID' => $defaultId, 'post_status' => 'private', 'post_content' => $content]);
    $this->settings->set('subscription.pages.manage', $defaultId);

    $this->resolver->repairPages();
    $count = count($this->allPages());
    $this->resolver->repairPages();

    $this->assertSame('private', get_post_status($defaultId));
    $this->assertSame($content, get_post_field('post_content', $defaultId));
    $this->assertCleanPublishedReplacement($defaultId, 'subscription.pages.manage');
    $this->assertCount($count, $this->allPages());
  }

  public function testRepairDoesNotRestoreTrashedDefaultPageWithCustomContent() {
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    $content = 'Secret text [mailpoet_page]';
    wp_update_post(['ID' => $defaultId, 'post_content' => $content]);
    wp_trash_post($defaultId);
    $this->settings->set('subscription.pages.manage', $defaultId);

    $this->resolver->repairPages();
    $count = count($this->allPages());
    $this->resolver->repairPages();

    $this->assertSame('trash', get_post_status($defaultId));
    $this->assertSame($content, get_post_field('post_content', $defaultId));
    $this->assertCleanPublishedReplacement($defaultId, 'subscription.pages.manage');
    $this->assertCount($count, $this->allPages());
  }

  public function testRepairLeavesPrivateAndDraftConfiguredPagesUntouched() {
    $private = $this->createPage('resolver-private', 'private', 'page');
    $draft = $this->createPage('resolver-draft', 'draft', 'page');
    $this->settings->set('subscription.pages.manage', $private);
    $this->settings->set('subscription.pages.unsubscribe', $draft);
    $this->settings->set('subscription.pages.captcha', 0);

    $this->resolver->repairPages();

    $this->assertSame($private, (int)$this->settings->fetch('subscription.pages.manage'));
    $this->assertSame($draft, (int)$this->settings->fetch('subscription.pages.unsubscribe'));
    $this->assertGreaterThan(0, (int)$this->settings->fetch('subscription.pages.captcha'));
  }

  public function testRepairRepublishesUnpublishedDefaultSubscriptionsPage() {
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    wp_update_post(['ID' => $defaultId, 'post_status' => 'draft']);
    $before = count($this->allPages());
    $keys = ['confirmation', 'confirm_unsubscribe', 'manage', 'unsubscribe'];
    foreach ($keys as $key) {
      $this->settings->set('subscription.pages.' . $key, $defaultId);
    }

    $this->resolver->repairPages();

    $this->assertSame('publish', get_post_status($defaultId));
    $this->assertCount($before, $this->allPages());
    foreach ($keys as $key) {
      $this->assertSame($defaultId, (int)$this->settings->fetch('subscription.pages.' . $key));
    }
  }

  /** @dataProvider blockWrappedShortcodeProvider */
  public function testRepairRepublishesDefaultPageWithBlockWrappedShortcode(string $content) {
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    wp_update_post(['ID' => $defaultId, 'post_status' => 'draft', 'post_content' => $content]);
    $before = count($this->allPages());
    $this->settings->set('subscription.pages.manage', $defaultId);

    $this->resolver->repairPages();

    $this->assertSame('publish', get_post_status($defaultId));
    $this->assertCount($before, $this->allPages());
    $this->assertSame($defaultId, (int)$this->settings->fetch('subscription.pages.manage'));
  }

  public function blockWrappedShortcodeProvider(): array {
    return [
      'shortcode block' => ["<!-- wp:shortcode -->\n[mailpoet_page]\n<!-- /wp:shortcode -->"],
      'paragraph block' => ["<!-- wp:paragraph -->\n<p>[mailpoet_page]</p>\n<!-- /wp:paragraph -->"],
    ];
  }

  public function testRepairRepublishesUnpublishedDefaultCaptchaPage() {
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_CAPTCHA)->ID;
    wp_update_post(['ID' => $defaultId, 'post_status' => 'private']);
    $before = count($this->allPages());
    $this->settings->set('subscription.pages.captcha', $defaultId);

    $this->resolver->repairPages();

    $this->assertSame('publish', get_post_status($defaultId));
    $this->assertCount($before, $this->allPages());
    $this->assertSame($defaultId, (int)$this->settings->fetch('subscription.pages.captcha'));
  }

  public function testRepairKeepsEmptyReEngagementPageEmptyAndRepairsStaleOne() {
    $this->staleAllKeys();
    $this->resolver->repairPages();
    $this->assertSame(0, (int)$this->settings->fetch('reEngagement.page'));

    $id = $this->createPage('resolver-reengagement');
    $this->settings->set('reEngagement.page', $id);
    wp_delete_post($id, true);
    $this->resolver->repairPages();
    $this->assertSame((int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID, (int)$this->settings->fetch('reEngagement.page'));
  }

  public function testRepairWritesNothingWhenPageCannotBeCreated() {
    $this->deleteAllPages();
    $this->staleAllKeys();
    add_filter('wp_insert_post_empty_content', '__return_true');
    try {
      $this->resolver->repairPages();
    } finally {
      remove_filter('wp_insert_post_empty_content', '__return_true');
    }
    $this->assertSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));
    $this->assertSame(999999, (int)$this->settings->fetch('subscription.pages.captcha'));
    $this->assertFalse(get_option(self::LOCK));

    $this->resolver->repairPages();
    $this->assertNotSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));
  }

  public function testRepairKeepsSettingsChangedWhilePagesAreCreated() {
    $this->deleteAllPages();
    $this->staleAllKeys();
    $this->settings->get('subscription.pages.manage');
    $repository = $this->diContainer->get(SettingsRepository::class);
    $changed = false;
    $callback = function () use (&$changed, $repository) {
      if ($changed) {
        return;
      }
      $changed = true;
      $entity = $repository->findOneByName('subscription');
      $this->assertNotNull($entity);
      $value = (array)$entity->getValue();
      $value['concurrent_marker'] = 'kept';
      $repository->createOrUpdateByName('subscription', $value);
    };
    add_action('wp_insert_post', $callback);
    try {
      $this->resolver->repairPages();
    } finally {
      remove_action('wp_insert_post', $callback);
    }

    $this->settings->resetCache();
    $this->assertTrue($changed);
    $this->assertSame('kept', $this->settings->get('subscription.concurrent_marker'));
    $this->assertGreaterThan(0, (int)$this->settings->get('subscription.pages.manage'));
  }

  public function testRepairDoesNothingWhenLockIsFresh() {
    $this->staleAllKeys();
    add_option(self::LOCK, time(), '', false);

    $this->resolver->repairPages();

    $this->assertSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));

    delete_option(self::LOCK);
    $this->resolver->repairPages();
    $this->assertNotSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));
  }

  public function testRepairTakesOverStaleLockAndReleasesIt() {
    $this->staleAllKeys();
    add_option(self::LOCK, time() - 120, '', false);

    $this->resolver->repairPages();

    $this->assertGreaterThan(0, (int)$this->settings->fetch('subscription.pages.manage'));
    $this->assertFalse(get_option(self::LOCK));
  }

  public function testRepairDoesNotTakeOverStaleLockChangedByCompetitor() {
    global $wpdb;
    $this->deleteAllPages();
    $this->staleAllKeys();
    add_option(self::LOCK, (string)(time() - 120) . ':stale', '', false);
    $competitorValue = time() . ':competitor';
    $reads = 0;
    // The first read happens inside add_option(); the competitor strikes after the stale read that follows it.
    $callback = function ($value) use ($wpdb, $competitorValue, &$reads) {
      if (++$reads === 2) {
        $wpdb->update($wpdb->options, ['option_value' => $competitorValue], ['option_name' => self::LOCK]);
      }
      return $value;
    };
    add_filter('option_' . self::LOCK, $callback);
    try {
      $this->resolver->repairPages();
    } finally {
      remove_filter('option_' . self::LOCK, $callback);
    }

    $this->assertCount(0, $this->allPages());
    $this->assertSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));
    wp_cache_delete(self::LOCK, 'options');
    $this->assertSame($competitorValue, get_option(self::LOCK));
  }

  public function testRepairDoesNotReleaseLockTakenOverByAnotherHolder() {
    global $wpdb;
    $this->deleteAllPages();
    $this->staleAllKeys();
    $otherValue = time() . ':other-holder';
    $callback = function () use ($wpdb, $otherValue) {
      $wpdb->update($wpdb->options, ['option_value' => $otherValue], ['option_name' => self::LOCK]);
    };
    add_action('wp_insert_post', $callback);
    try {
      $this->resolver->repairPages();
    } finally {
      remove_action('wp_insert_post', $callback);
    }

    wp_cache_delete(self::LOCK, 'options');
    $this->assertSame($otherValue, get_option(self::LOCK));
  }

  public function testCreateMailPoetPageCanKeepHooks() {
    /** @var \ArrayObject<int, int> $calls */
    $calls = new \ArrayObject();
    $callback = function () use ($calls) {
      $calls[] = 1;
    };
    add_action('save_post', $callback);
    try {
      Pages::createMailPoetPage('resolver-keep-hooks', false);
      $this->assertGreaterThan(0, count($calls));
      $calls->exchangeArray([]);
      Pages::createMailPoetPage('resolver-strip-hooks');
      $this->assertCount(0, $calls);
    } finally {
      remove_action('save_post', $callback);
    }
  }

  public function testTrashingDefaultSubscriptionsPageRepairsItInSameRequest() {
    $this->enablePageChangeHooks();
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    $this->settings->set('subscription.pages.manage', $defaultId);
    wp_set_current_user(0);

    wp_trash_post($defaultId);

    $this->assertSame('publish', get_post_status($defaultId));
    $this->assertSame($defaultId, (int)$this->settings->fetch('subscription.pages.manage'));
    $this->assertCount(1, $this->pagesBySlug(Pages::PAGE_SUBSCRIPTIONS));
    $this->assertFalse(get_option(self::LOCK));
  }

  public function testDeletingConfiguredCaptchaPageRepairsSettingInSameRequest() {
    $this->enablePageChangeHooks();
    $id = $this->createPage('resolver-captcha');
    $this->settings->set('subscription.pages.captcha', $id);
    $captchaId = (int)Pages::getMailPoetPage(Pages::PAGE_CAPTCHA)->ID;

    wp_delete_post($id, true);

    $this->assertSame($captchaId, (int)$this->settings->fetch('subscription.pages.captcha'));
  }

  public function testDeletingOnlyDefaultPageCreatesNewOne() {
    $this->enablePageChangeHooks();
    $this->deletePagesBySlug(Pages::PAGE_CAPTCHA);
    $this->settings->set('subscription.pages.captcha', 999999);
    $oldId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;
    $this->settings->set('subscription.pages.manage', $oldId);

    wp_delete_post($oldId, true);

    $newId = (int)$this->settings->fetch('subscription.pages.manage');
    $this->assertNotSame($oldId, $newId);
    $this->assertSame('publish', get_post_status($newId));
  }

  public function testUnpublishingDefaultMailPoetPageRepublishesIt() {
    $this->enablePageChangeHooks();
    $defaultId = (int)Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS)->ID;

    wp_update_post(['ID' => $defaultId, 'post_status' => 'draft']);

    $this->assertSame('publish', get_post_status($defaultId));
    $this->assertCount(1, $this->pagesBySlug(Pages::PAGE_SUBSCRIPTIONS));
  }

  public function testTrashingUnpublishedConfiguredMailPoetPageRepairsSetting() {
    $id = $this->createPage('resolver-draft-captcha', 'draft');
    $this->settings->set('subscription.pages.captcha', $id);
    $captchaId = (int)Pages::getMailPoetPage(Pages::PAGE_CAPTCHA)->ID;
    $this->enablePageChangeHooks();

    wp_trash_post($id);

    $this->assertSame($captchaId, (int)$this->settings->fetch('subscription.pages.captcha'));
  }

  public function testUnrelatedPostChangesDoNotTriggerRepair() {
    $this->enablePageChangeHooks();
    $this->staleAllKeys();
    $before = count($this->allPages());
    $trashed = (int)wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Unrelated']);
    $deleted = (int)wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Unrelated 2']);
    $drafted = (int)wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Unrelated 3']);

    wp_trash_post($trashed);
    wp_delete_post($deleted, true);
    wp_update_post(['ID' => $drafted, 'post_status' => 'draft']);

    $this->assertSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));
    $this->assertCount($before, $this->allPages());
    $this->assertFalse(get_option(self::LOCK));
    wp_delete_post($trashed, true);
    wp_delete_post($drafted, true);
  }

  public function testDraftingRegularChosenPageLeavesSettingUntouched() {
    $this->enablePageChangeHooks();
    $id = $this->createPage('resolver-regular', 'publish', 'page');
    $this->settings->set('subscription.pages.confirmation', $id);
    $before = count($this->allPages());

    wp_update_post(['ID' => $id, 'post_status' => 'draft']);

    $this->assertSame($id, (int)$this->settings->fetch('subscription.pages.confirmation'));
    $this->assertSame('draft', get_post_status($id));
    $this->assertCount($before, $this->allPages());
    wp_delete_post($id, true);
  }

  public function testPageChangeRepairDoesNotRecurseOrDuplicatePages() {
    $this->enablePageChangeHooks();
    $this->deleteAllPages();
    $this->staleAllKeys();
    $id = $this->createPage('resolver-configured');
    $this->settings->set('subscription.pages.manage', $id);

    wp_trash_post($id);
    wp_trash_post($id);

    $this->assertCount(1, $this->pagesBySlug(Pages::PAGE_SUBSCRIPTIONS));
    $this->assertCount(1, $this->pagesBySlug(Pages::PAGE_CAPTCHA));
    $this->assertFalse(get_option(self::LOCK));
  }

  public function testMaybeRepairPagesSkipsUsersWithoutCapability() {
    $this->staleAllKeys();
    wp_set_current_user($this->createUser('subscriber'));

    $this->resolver->maybeRepairPages();

    $this->assertSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));

    wp_set_current_user($this->createUser('administrator'));
    $this->resolver->maybeRepairPages();
    $this->assertNotSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));
  }

  public function testMaybeRepairPagesRepairsForAdministrators() {
    $this->staleAllKeys();
    wp_set_current_user($this->createUser('administrator'));

    $this->resolver->maybeRepairPages();

    $this->assertNotSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));
  }

  public function testMaybeRepairPagesSkipsAjaxRequests() {
    $this->staleAllKeys();
    $wp = Stub::make(new WPFunctions(), [
      'currentUserCan' => true,
      'wpDoingAjax' => true,
    ]);
    $resolver = new MailPoetPageResolver($wp, $this->settings, $this->diContainer->get(LoggerFactory::class));

    $resolver->maybeRepairPages();

    $this->assertSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));

    $notAjax = Stub::make(new WPFunctions(), ['currentUserCan' => true, 'wpDoingAjax' => false]);
    (new MailPoetPageResolver($notAjax, $this->settings, $this->diContainer->get(LoggerFactory::class)))->maybeRepairPages();
    $this->assertNotSame(999999, (int)$this->settings->fetch('subscription.pages.manage'));
  }

  public function testMaybeRepairPagesLogsFailureToMailPoetLog() {
    $resolver = $this->makeFailingResolver($this->diContainer->get(LoggerFactory::class));

    $resolver->maybeRepairPages();

    $log = $this->diContainer->get(LogRepository::class)->findOneBy(['name' => LoggerFactory::TOPIC_PAGES, 'level' => 400]);
    $this->assertInstanceOf(LogEntity::class, $log);
    $this->assertStringContainsString('boom', (string)$log->getMessage());
    $this->assertSame(__FILE__, $log->getContext()['file'] ?? null);
  }

  public function testMaybeRepairPagesDoesNotThrowWhenLoggingFails() {
    $loggerFactory = Stub::make(LoggerFactory::class, [
      'getLogger' => function () {
        throw new \RuntimeException('logger down');
      },
    ]);

    $this->expectNotToPerformAssertions();
    $this->makeFailingResolver($loggerFactory)->maybeRepairPages();
  }

  private function makeFailingResolver(LoggerFactory $loggerFactory): MailPoetPageResolver {
    $resolver = $this->getMockBuilder(MailPoetPageResolver::class)
      ->setConstructorArgs([Stub::make(new WPFunctions(), ['currentUserCan' => true, 'wpDoingAjax' => false]), $this->settings, $loggerFactory])
      ->onlyMethods(['repairPages'])
      ->getMock();
    $resolver->expects($this->once())->method('repairPages')->willThrowException(new \RuntimeException('boom'));
    return $resolver;
  }

  private function enablePageChangeHooks(): void {
    $this->resolver->registerPageChangeHooks();
  }

  private function removePageChangeHooks(): void {
    remove_action('trashed_post', [$this->resolver, 'handlePageChange']);
    remove_action('after_delete_post', [$this->resolver, 'handlePageChange']);
    remove_action('transition_post_status', [$this->resolver, 'handleStatusTransition']);
  }

  private function createUser(string $role): int {
    $id = wp_insert_user([
      'user_login' => 'resolver_' . $role . '_' . uniqid(),
      'user_pass' => wp_generate_password(),
      'user_email' => uniqid() . '@example.com',
      'role' => $role,
    ]);
    $this->assertIsInt($id);
    $this->userIds[] = $id;
    return $id;
  }

  private function createPage(string $slug, string $status = 'publish', string $postType = 'mailpoet_page'): int {
    return (int)wp_insert_post([
      'post_type' => $postType,
      'post_status' => $status,
      'post_name' => $slug,
      'post_content' => '[mailpoet_page]',
      'post_title' => 'Test page',
    ]);
  }

  private function assertCleanPublishedReplacement(int $oldId, string $settingKey): void {
    $newId = (int)$this->settings->fetch($settingKey);
    $this->assertNotSame($oldId, $newId);
    $this->assertSame('publish', get_post_status($newId));
    $this->assertSame('[mailpoet_page]', get_post_field('post_content', $newId));
  }

  private function staleAllKeys(): void {
    foreach (['confirmation', 'confirm_unsubscribe', 'manage', 'unsubscribe', 'captcha'] as $key) {
      $this->settings->set('subscription.pages.' . $key, 999999);
    }
    $this->settings->set('reEngagement.page', '');
  }

  private function deletePagesBySlug(string $slug): void {
    foreach ($this->pagesBySlug($slug) as $page) {
      wp_delete_post((int)$page->ID, true);
    }
  }

  private function deleteAllPages(): void {
    foreach ($this->allPages() as $page) {
      wp_delete_post((int)$page->ID, true);
    }
  }

  /** @return \WP_Post[] */
  private function allPages(): array {
    return get_posts([
      'post_type' => 'mailpoet_page',
      'post_status' => self::STATUSES,
      'numberposts' => -1,
    ]);
  }

  /** @return \WP_Post[] */
  private function pagesBySlug(string $slug): array {
    return get_posts([
      'post_type' => 'mailpoet_page',
      'post_status' => self::STATUSES,
      'post_name__in' => [$slug, $slug . '__trashed'],
      'numberposts' => -1,
    ]);
  }
}
