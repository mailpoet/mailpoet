<?php declare(strict_types = 1);

namespace MailPoet\Test\Settings;

use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class PagesTest extends \MailPoetTest {

  private SettingsController $settings;
  private WPFunctions $wp;

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->wp = $this->diContainer->get(WPFunctions::class);
  }

  public function testGetPageDataIncludesCaptchaUrl() {
    // Create a test page
    $postId = Pages::createMailPoetPage('test-page');
    $post = $this->wp->getPost($postId);

    // Set up captcha page setting
    $captchaPostId = Pages::createMailPoetPage('captcha-page');
    $this->settings->set('subscription.pages.captcha', $captchaPostId);

    $pageData = Pages::getPageData($post);

    verify($pageData)->isArray();
    verify($pageData['id'])->equals($postId);
    verify($pageData['title'])->equals(Pages::PAGE_TITLE);
    verify($pageData['url'])->isArray();
    verify($pageData['url']['captcha'])->notNull();
  }

  public function testGetPageDataIncludesAllRequiredUrls() {
    // Create a test page
    $postId = Pages::createMailPoetPage('test-page');
    $post = $this->wp->getPost($postId);

    // Set up captcha page setting
    $captchaPostId = Pages::createMailPoetPage('captcha-page');
    $this->settings->set('subscription.pages.captcha', $captchaPostId);

    $pageData = Pages::getPageData($post);

    verify($pageData['url'])->isArray();
    verify($pageData['url'])->arrayHasKey('unsubscribe');
    verify($pageData['url'])->arrayHasKey('manage');
    verify($pageData['url'])->arrayHasKey('confirm');
    verify($pageData['url'])->arrayHasKey('confirm_unsubscribe');
    verify($pageData['url'])->arrayHasKey('re_engagement');
    verify($pageData['url'])->arrayHasKey('captcha');
  }
}
