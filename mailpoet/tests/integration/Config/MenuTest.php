<?php declare(strict_types = 1);

namespace MailPoet\Test\Config;

use Codeception\Util\Stub;
use MailPoet\Config\Menu;
use MailPoet\Config\ServicesChecker;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Mailer\Mailer;
use MailPoet\Services\Bridge;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter;

class MenuTest extends \MailPoetTest {
  public function testItReturnsTrueIfCurrentPageBelongsToMailpoet() {
    $result = Menu::isOnMailPoetAdminPage(null, 'somepage');
    verify($result)->false();
    $result = Menu::isOnMailPoetAdminPage(null, 'mailpoet-newsletters');
    verify($result)->true();
  }

  public function testItRespectsExclusionsWhenCheckingMPPages() {
    $exclude = ['mailpoet-welcome'];
    $result = Menu::isOnMailPoetAdminPage($exclude, 'mailpoet-welcome');
    verify($result)->false();
    $result = Menu::isOnMailPoetAdminPage($exclude, 'mailpoet-newsletters');
    verify($result)->true();
  }

  public function testItWorksWithRequestDataWhenCheckingMPPages() {
    $_REQUEST['page'] = 'mailpoet-newsletters';
    $result = Menu::isOnMailPoetAdminPage();
    verify($result)->true();

    $_REQUEST['page'] = 'blah';
    $result = Menu::isOnMailPoetAdminPage();
    verify($result)->false();

    unset($_REQUEST['page']);
    $result = Menu::isOnMailPoetAdminPage();
    verify($result)->false();
  }

  public function testItChecksPremiumKey() {
    $menu = $this->diContainer->get(Menu::class);

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $checker = Stub::make(
      new ServicesChecker(),
      ['isPremiumKeyValid' => true],
      $this
    );
    $menu->checkPremiumKey($checker);
    verify($menu->premiumKeyValid)->true();

    $checker = Stub::make(
      new ServicesChecker(),
      ['isPremiumKeyValid' => false],
      $this
    );
    $menu->checkPremiumKey($checker);
    verify($menu->premiumKeyValid)->false();
  }

  public function testItChecksMSSKeyOnMailPoetPage() {
    $menu = $this->diContainer->get(Menu::class);

    $_REQUEST['page'] = 'mailpoet-newsletters';
    $checker = Stub::make(
      new ServicesChecker(),
      ['isMailPoetAPIKeyExpiring' => true],
      $this
    );
    $menu->checkMSSKey($checker);
    verify($menu->mssKeyExpiring)->true();

    $checker = Stub::make(
      new ServicesChecker(),
      ['isMailPoetAPIKeyExpiring' => false],
      $this
    );
    $menu->checkMSSKey($checker);
    verify($menu->mssKeyExpiring)->false();

    unset($_REQUEST['page']);
  }

  public function testItPassesDisplayNoticeFalseWhenNotOnMailPoetPageOrPluginsPage() {
    $menu = $this->diContainer->get(Menu::class);

    unset($_REQUEST['page']);
    $_SERVER['SCRIPT_NAME'] = '/wp-admin/index.php';

    $displayErrorNoticeArgument = null;
    $checker = Stub::make(
      new ServicesChecker(),
      [
        'isMailPoetAPIKeyExpiring' => function ($displayErrorNotice) use (&$displayErrorNoticeArgument) {
          $displayErrorNoticeArgument = $displayErrorNotice;
          return false;
        },
      ],
      $this
    );
    $menu->checkMSSKey($checker);
    verify($displayErrorNoticeArgument)->false();
  }

  public function testItRendersExpiringMSSNoticeOnMailPoetAdminPage() {
    $settings = SettingsController::getInstance();
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, [
      'method' => 'MailPoet',
      'mailpoet_api_key' => 'some_key',
    ]);
    $settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_EXPIRING,
      'data' => ['expire_at' => date('c')],
    ]);
    unset($_SERVER['SCRIPT_NAME']);
    $_REQUEST['page'] = 'mailpoet-newsletters';

    $menu = $this->diContainer->get(Menu::class);
    $output = $this->renderAdminNotices(function () use ($menu) {
      $menu->checkMSSKey();
    });

    unset($_REQUEST['page']);
    verify($output)->stringContainsString('Your MailPoet sending plan expires on');
  }

  public function testItDoesNotRenderExpiringMSSNoticeOffMailPoetAdminPage() {
    $settings = SettingsController::getInstance();
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, [
      'method' => 'MailPoet',
      'mailpoet_api_key' => 'some_key',
    ]);
    $settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_EXPIRING,
      'data' => ['expire_at' => date('c')],
    ]);
    unset($_REQUEST['page']);
    $_SERVER['SCRIPT_NAME'] = '/wp-admin/index.php';

    $menu = $this->diContainer->get(Menu::class);
    $output = $this->renderAdminNotices(function () use ($menu) {
      $menu->checkMSSKey();
    });

    unset($_SERVER['SCRIPT_NAME']);
    verify($output)->equals('');
  }

  public function testItDoesNotRenderPurchaseKeyNoticeFromEarlyMSSCheckWhenKeyInvalid() {
    $settings = SettingsController::getInstance();
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, [
      'method' => 'MailPoet',
      'mailpoet_api_key' => 'some_key',
    ]);
    $settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_INVALID,
    ]);
    unset($_SERVER['SCRIPT_NAME']);
    $_REQUEST['page'] = 'mailpoet-newsletters';

    $menu = $this->diContainer->get(Menu::class);
    $output = $this->renderAdminNotices(function () use ($menu) {
      $menu->checkMSSKey();
    });

    unset($_REQUEST['page']);
    verify($output)->equals('');
  }

  public function testItDoesNotRenderExpiringMSSNoticeWhenMSSIsNotSendingMethod() {
    $settings = SettingsController::getInstance();
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, [
      'method' => 'PHPMail',
    ]);
    $settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_EXPIRING,
      'data' => ['expire_at' => date('c')],
    ]);
    unset($_SERVER['SCRIPT_NAME']);
    $_REQUEST['page'] = 'mailpoet-newsletters';

    $menu = $this->diContainer->get(Menu::class);
    $output = $this->renderAdminNotices(function () use ($menu) {
      $menu->checkMSSKey();
    });

    unset($_REQUEST['page']);
    verify($output)->equals('');
  }

  public function testItRendersExpiringMSSNoticeViaInitOnMailPoetAdminPage() {
    $settings = SettingsController::getInstance();
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, [
      'method' => 'MailPoet',
      'mailpoet_api_key' => 'some_key',
    ]);
    $settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_EXPIRING,
      'data' => ['expire_at' => date('c')],
    ]);
    unset($_SERVER['SCRIPT_NAME']);
    $_REQUEST['page'] = 'mailpoet-newsletters';

    $menu = $this->diContainer->get(Menu::class);
    $output = $this->renderAdminNotices(function () use ($menu) {
      $menu->init();
    });
    $this->cleanUpMenuInitHooks($menu);

    unset($_REQUEST['page']);
    verify($output)->stringContainsString('Your MailPoet sending plan expires on');
  }

  public function testItRendersExpiringMSSNoticeViaInitOnPluginsPage() {
    $settings = SettingsController::getInstance();
    $settings->set(Mailer::MAILER_CONFIG_SETTING_NAME, [
      'method' => 'MailPoet',
      'mailpoet_api_key' => 'some_key',
    ]);
    $settings->set(Bridge::API_KEY_STATE_SETTING_NAME, [
      'state' => Bridge::KEY_EXPIRING,
      'data' => ['expire_at' => date('c')],
    ]);
    unset($_REQUEST['page']);
    $_SERVER['SCRIPT_NAME'] = '/wp-admin/plugins.php';

    $menu = $this->diContainer->get(Menu::class);
    $output = $this->renderAdminNotices(function () use ($menu) {
      $menu->init();
    });
    $this->cleanUpMenuInitHooks($menu);

    unset($_SERVER['SCRIPT_NAME']);
    verify($output)->stringContainsString('Your MailPoet sending plan expires on');
  }

  // init() also hooks admin_init/admin_menu/parent_file callbacks; remove them so
  // repeated calls across tests in this process don't pile up duplicate hooks.
  private function cleanUpMenuInitHooks(Menu $menu): void {
    remove_action('admin_init', [$menu, 'maybeRenderAutomationPreviewEmbed'], 1);
    remove_action('admin_init', [$menu, 'maybeRenderAutomationFlowEmbed'], 1);
    remove_action('admin_menu', [$menu, 'setup']);
    remove_filter('parent_file', [$menu, 'highlightNestedMailPoetSubmenus']);
  }

  private function renderAdminNotices(callable $trigger): string {
    remove_all_actions('admin_notices');
    $trigger();
    ob_start();
    do_action('admin_notices');
    return (string)ob_get_clean();
  }

  public function testItHighlightsAutomationsWhenEditingAutomationEmail() {
    $this->assertEmailEditorMenuPage(NewsletterEntity::TYPE_AUTOMATION, Menu::AUTOMATIONS_PAGE_SLUG);
  }

  public function testItHighlightsEmailsWhenEditingStandardEmail() {
    $this->assertEmailEditorMenuPage(NewsletterEntity::TYPE_STANDARD, Menu::EMAILS_PAGE_SLUG);
  }

  private function assertEmailEditorMenuPage(string $newsletterType, string $expectedMenuPageSlug): void {
    global $plugin_page, $submenu_file;

    $postId = wp_insert_post([
      'post_title' => 'MailPoet email',
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'private',
    ]);
    verify($postId)->greaterThan(0);

    (new Newsletter())
      ->withType($newsletterType)
      ->withWpPostId($postId)
      ->create();

    $previousPost = $_GET['post'] ?? null;
    $previousAction = $_GET['action'] ?? null;
    $previousPluginPage = $plugin_page ?? null;
    $previousSubmenuFile = $submenu_file ?? null;
    $_GET['post'] = (string)$postId;
    $_GET['action'] = 'edit';

    $menu = $this->diContainer->get(Menu::class);
    $emailEditorProperty = new \ReflectionProperty(Menu::class, 'emailEditor');
    $emailEditorProperty->setAccessible(true);
    $previousEmailEditor = $emailEditorProperty->getValue($menu);

    try {
      $emailEditor = Stub::makeEmpty(EmailEditor::class, ['isEditorPage' => true], $this);
      $emailEditorProperty->setValue($menu, $emailEditor);
      $parentFile = $menu->highlightNestedMailPoetSubmenus(
        'edit.php?post_type=' . EmailEditor::MAILPOET_EMAIL_POST_TYPE
      );

      verify($parentFile)->equals($expectedMenuPageSlug);
      verify($plugin_page)->equals($expectedMenuPageSlug);
      verify($submenu_file)->equals($expectedMenuPageSlug);
    } finally {
      if ($previousPost === null) {
        unset($_GET['post']);
      } else {
        $_GET['post'] = $previousPost;
      }
      if ($previousAction === null) {
        unset($_GET['action']);
      } else {
        $_GET['action'] = $previousAction;
      }
      $plugin_page = $previousPluginPage;
      $submenu_file = $previousSubmenuFile;
      $emailEditorProperty->setValue($menu, $previousEmailEditor);
    }
  }
}
