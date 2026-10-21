<?php declare(strict_types = 1);

namespace integration\Captcha;

use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Captcha\Validator\CaptchaValidator;
use MailPoet\Captcha\Validator\ValidationError;
use MailPoet\Config\Populator;
use MailPoet\Router\Endpoints\Captcha as CaptchaEndpoint;
use MailPoet\Router\Router;
use MailPoet\Settings\MailPoetPageResolver;
use MailPoet\Settings\Pages;
use MailPoet\Settings\SettingsController;

class CaptchaUrlFactoryTest extends \MailPoetTest {
  private bool $hooksWereRegistered = false;

  private CaptchaUrlFactory $urlFactory;
  private MailPoetPageResolver $resolver;
  private SettingsController $settings;

  /** @var array<int, array{status: string, name: string}> */
  private $snapshot = [];

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->resolver = $this->diContainer->get(MailPoetPageResolver::class);
    $this->hooksWereRegistered = (bool)has_action('trashed_post', [$this->resolver, 'handlePageChange']);
    $this->removePageChangeHooks();
    foreach ($this->allPages() as $page) {
      $this->snapshot[(int)$page->ID] = ['status' => $page->post_status, 'name' => $page->post_name]; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    }

    // Prepare the settings
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();
    $this->settings->resetCache();
    $this->resolver->resetCache();
    $this->urlFactory = $this->diContainer->get(CaptchaUrlFactory::class);
  }

  private function removePageChangeHooks(): void {
    remove_action('trashed_post', [$this->resolver, 'handlePageChange']);
    remove_action('after_delete_post', [$this->resolver, 'handlePageChange']);
    remove_action('transition_post_status', [$this->resolver, 'handleStatusTransition']);
  }

  public function _after() {
    unset($GLOBALS['post']);
    foreach ($this->allPages() as $page) {
      if (!isset($this->snapshot[(int)$page->ID])) {
        wp_delete_post((int)$page->ID, true);
      }
    }
    foreach ($this->snapshot as $id => $data) {
      if (get_post($id)) {
        wp_update_post(['ID' => $id, 'post_status' => $data['status'], 'post_name' => $data['name']]);
        continue;
      }
      Pages::createMailPoetPage(preg_replace('/__trashed$/', '', $data['name']));
    }
    $this->snapshot = [];
    $this->settings->resetCache();
    $this->resolver->resetCache();
    if ($this->hooksWereRegistered) {
      $this->resolver->registerPageChangeHooks();
    }
    parent::_after();
  }

  public function testItReturnsCaptchaRenderUrl() {
    $url = $this->urlFactory->getCaptchaUrlForMPForm('abc');

    verify($url)->notNull();
    verify($url)->stringContainsString(Router::NAME);
    verify($url)->stringContainsString('mailpoet_page=' . Pages::PAGE_CAPTCHA);
    verify($url)->stringContainsString('action=' . CaptchaEndpoint::ACTION_RENDER);
    verify($url)->stringContainsString('endpoint=' . CaptchaEndpoint::ENDPOINT);
    verify($url)->stringContainsString('data=');
  }

  public function testItReturnsCaptchaImageUrl() {
    $url = $this->urlFactory->getCaptchaImageUrl('abc');

    verify($url)->notNull();
    verify($url)->stringContainsString(Router::NAME);
    verify($url)->stringContainsString('mailpoet_page=' . Pages::PAGE_CAPTCHA);
    verify($url)->stringContainsString('action=' . CaptchaEndpoint::ACTION_IMAGE);
    verify($url)->stringContainsString('endpoint=' . CaptchaEndpoint::ENDPOINT);
    verify($url)->stringContainsString('data=');
  }

  public function testItReturnsCaptchaAudioUrl() {
    $url = $this->urlFactory->getCaptchaAudioUrl('abc');

    verify($url)->notNull();
    verify($url)->stringContainsString(Router::NAME);
    verify($url)->stringContainsString('mailpoet_page=' . Pages::PAGE_CAPTCHA);
    verify($url)->stringContainsString('action=' . CaptchaEndpoint::ACTION_AUDIO);
    verify($url)->stringContainsString('endpoint=' . CaptchaEndpoint::ENDPOINT);
    verify($url)->stringContainsString('data=');
  }

  public function testItReturnsCaptchaPreviewUrl() {
    $url = $this->urlFactory->getCaptchaPreviewUrl();

    verify($url)->notNull();
    verify($url)->stringContainsString(Router::NAME);
    verify($url)->stringContainsString('mailpoet_page=' . Pages::PAGE_CAPTCHA);
    verify($url)->stringContainsString('action=' . CaptchaEndpoint::ACTION_RENDER);
    verify($url)->stringContainsString('endpoint=' . CaptchaEndpoint::ENDPOINT);
    verify($url)->stringContainsString('data=');
  }

  public function testItReturnsPreviewUrlWhenNoCaptchaPageSet() {
    $this->settings->set('subscription.pages.captcha', null);
    $this->resolver->resetCache();

    $url = $this->urlFactory->getCaptchaPreviewUrl();

    $this->assertIsString($url);
    $this->assertStringContainsString((string)get_permalink(Pages::getMailPoetPage(Pages::PAGE_CAPTCHA)), $url);
    $this->assertStringContainsString('action=' . CaptchaEndpoint::ACTION_RENDER, $url);
  }

  public function testPreviewUrlFallsBackToHomeUrlWhenNoCaptchaPageExists() {
    $this->removeAllPagesWithStaleSetting();

    $url = $this->urlFactory->getCaptchaPreviewUrl();

    $this->assertIsString($url);
    $this->assertStringStartsWith(home_url('/'), $url);
    $this->assertStringContainsString('endpoint=' . CaptchaEndpoint::ENDPOINT, $url);
  }

  public function testPreviewUrlKeepsConcretePost() {
    $id = $this->createPage('concrete-captcha', 'draft');
    $post = get_post($id);
    $this->assertInstanceOf(\WP_Post::class, $post);

    $url = $this->urlFactory->getCaptchaPreviewUrl($post);

    $this->assertStringContainsString((string)get_permalink($post), $url);
  }

  public function testUrlsFallBackToHomeUrlWhenNoCaptchaPageExists() {
    $this->removeAllPagesWithStaleSetting();

    $urls = [
      $this->urlFactory->getCaptchaImageUrl('x'),
      $this->urlFactory->getCaptchaAudioUrl('x'),
      $this->urlFactory->getCaptchaUrlForMPForm('x'),
      $this->urlFactory->getCaptchaUrl(['captcha_session_id' => 'x']),
    ];
    foreach ($urls as $url) {
      $this->assertIsString($url);
      $this->assertStringStartsWith(home_url('/'), $url);
      $this->assertStringContainsString('endpoint=' . CaptchaEndpoint::ENDPOINT, $url);
    }
  }

  public function testUrlsUseSubscriptionsPageWhenNoCaptchaPageExists() {
    foreach ($this->allPages() as $page) {
      if (strpos((string)$page->post_name, Pages::PAGE_CAPTCHA) === 0) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        wp_delete_post((int)$page->ID, true);
      }
    }
    $this->settings->set('subscription.pages.captcha', 999999);
    $this->resolver->resetCache();
    $subscriptions = Pages::getMailPoetPage(Pages::PAGE_SUBSCRIPTIONS);
    $this->assertInstanceOf(\WP_Post::class, $subscriptions);

    $url = $this->urlFactory->getCaptchaUrlForMPForm('x');

    $this->assertStringStartsWith((string)get_permalink($subscriptions), $url);
    $this->assertStringContainsString('endpoint=' . CaptchaEndpoint::ENDPOINT, $url);
  }

  public function testUrlsIgnoreGlobalPostWhenNoCaptchaPageExists() {
    $this->removeAllPagesWithStaleSetting();
    $unrelated = $this->createPage('unrelated-page');
    $GLOBALS['post'] = get_post($unrelated);

    $url = $this->urlFactory->getCaptchaImageUrl('x');

    $this->assertStringStartsWith(home_url('/'), $url);
    $this->assertStringNotContainsString('unrelated-page', $url);
  }

  public function testItUsesDefaultCaptchaPageWhenConfiguredPageIsTrashed() {
    $trashed = $this->createPage('trashed-captcha');
    wp_trash_post($trashed);
    $this->settings->set('subscription.pages.captcha', $trashed);
    $this->resolver->resetCache();

    $url = $this->urlFactory->getCaptchaImageUrl('x');

    $this->assertStringContainsString((string)get_permalink(Pages::getMailPoetPage(Pages::PAGE_CAPTCHA)), $url);
    $this->assertStringNotContainsString('trashed-captcha', $url);
  }

  public function testEmptyCaptchaValidationDoesNotFatalWhenNoCaptchaPageExists() {
    $this->removeAllPagesWithStaleSetting();
    $validator = $this->diContainer->get(CaptchaValidator::class);

    try {
      $validator->validateChallenge(['captcha_session_id' => 'abc']);
      $this->fail('Expected ValidationError');
    } catch (ValidationError $e) {
      $this->assertSame('Please fill in the CAPTCHA.', $e->getMessage());
    }
  }

  private function removeAllPagesWithStaleSetting(): void {
    foreach ($this->allPages() as $page) {
      wp_delete_post((int)$page->ID, true);
    }
    $this->settings->set('subscription.pages.captcha', 999999);
    $this->resolver->resetCache();
  }

  private function createPage(string $name, string $status = 'publish'): int {
    return (int)wp_insert_post([
      'post_title' => $name,
      'post_name' => $name,
      'post_status' => $status,
      'post_type' => 'page',
    ]);
  }

  /** @return \WP_Post[] */
  private function allPages(): array {
    return get_posts([
      'post_type' => 'mailpoet_page',
      'post_status' => ['publish', 'draft', 'pending', 'private', 'future', 'trash'],
      'numberposts' => -1,
    ]);
  }
}
