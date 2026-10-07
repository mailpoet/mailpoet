<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use Codeception\Stub;
use MailPoet\Captcha\CaptchaConstants;
use MailPoet\Captcha\CaptchaFormRenderer;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\Populator;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaFormRendererTest extends \MailPoetTest {
  const SESSION_ID = 'abcd1234abcd1234abcd1234abcd1234';

  public function _before() {
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();

    parent::_before();
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set(CaptchaConstants::TYPE_SETTING_NAME, CaptchaConstants::TYPE_BUILTIN);
    $settings->set(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, true);
  }

  /** @var string[] */
  private array $createdSessionIds = [];

  /** @var mixed */
  private $previousMyAccountPageId = false;

  private int $createdPageId = 0;

  public function _after() {
    $session = $this->diContainer->get(CaptchaSession::class);
    $session->reset(self::SESSION_ID);
    foreach ($this->createdSessionIds as $id) {
      $session->reset($id);
    }
    unset($_GET['user_login'], $_GET['password']);
    if ($this->createdPageId) {
      wp_delete_post($this->createdPageId, true);
      $this->createdPageId = 0;
      if ($this->previousMyAccountPageId === null) {
        delete_option('woocommerce_myaccount_page_id');
      } else {
        update_option('woocommerce_myaccount_page_id', $this->previousMyAccountPageId);
      }
      $this->previousMyAccountPageId = false;
    }
    $settings = $this->diContainer->get(SettingsController::class);
    $settings->set('captcha', []);
    $settings->set(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, false);
    parent::_after();
  }

  /**
   * Stores register form fields in a session with a fresh ID. The renderer caches
   * its output per session ID within a request, so tests never share an ID.
   */
  private function seedRegisterStash(string $referrer, array $fields): string {
    $session = $this->diContainer->get(CaptchaSession::class);
    $id = $session->generateSessionId();
    $this->createdSessionIds[] = $id;
    $session->setFormData($id, array_merge(['referrer_form' => $referrer], $fields));
    return $id;
  }

  /**
   * A copy of the shared renderer with an empty per-request cache, as on a later request.
   */
  private function createFreshRenderer(): CaptchaFormRenderer {
    $renderer = clone $this->diContainer->get(CaptchaFormRenderer::class);
    $cache = new \ReflectionProperty(CaptchaFormRenderer::class, 'renderedForms');
    $cache->setAccessible(true);
    $cache->setValue($renderer, []);
    return $renderer;
  }

  public function testItRendersInSubscriptionForm() {
    $formRepository = $this->diContainer->get(FormsRepository::class);
    $form = new FormEntity('captcha-render-test-form');

    $expectedLabel = 'EXPECTED_LABEL';
    $form->setBody([
      [
        'id' => 'email',
        'type' => 'text',
      ],
      [
        'type' => 'submit',
        'params' => [
          'label' => $expectedLabel,
        ],
      ],
    ]);

    $successColor = '#00ff00';
    $errorColor = '#ff0000';
    $form->setSettings([
      'success_message' => 'tada!',
      'success_validation_color' => $successColor,
      'error_validation_color' => $errorColor,
    ]);

    $form->setId(1);
    $formRepository->persist($form);
    $formRepository->flush();

    $sessionId = self::SESSION_ID;
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $captchaSession->setFormData($sessionId, ['form_id' => $form->getId()]);

    $data = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_MP_FORM,
    ];

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render($data);

    $this->assertStringContainsString('type="submit" class="mailpoet_submit" value="' . $expectedLabel . '"', $result);

    // Distinctive hidden fields
    $this->assertStringContainsString('name="data[captcha_session_id]" value="' . $sessionId . '"', $result);
    $this->assertStringContainsString('name="data[form_id]" value="' . $form->getId(), $result);

    // After submit elements
    $this->assertStringContainsString('class="mailpoet_validate_success"', $result);
    $this->assertStringContainsString('class="mailpoet_validate_error"', $result);

    // Form style elements
    $this->assertStringContainsString('<style>', $result);
    $this->assertStringContainsString('.mailpoet_validate_success {color: ' . $successColor . '}', $result);
    $this->assertStringContainsString('.mailpoet_validate_error {color: ' . $errorColor . '}', $result);
  }

  public function testItRendersInWPRegisterForm() {
    $expectedLabel = 'Register';
    $expectedActionUrl = '/wp-login.php?action=register';
    $userLogin = 'example';
    $userEmail = 'example@domain.com';
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, [
      'referrer_form_url' => $expectedActionUrl,
      'wp-submit' => $expectedLabel,
      'user_login' => $userLogin,
      'user_email' => $userEmail,
    ]);

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
    ]);

    // Action URL
    $this->assertStringContainsString('<form method="POST" action="' . $expectedActionUrl . '"', $result);

    // Submit button
    $this->assertStringContainsString('type="submit" class="mailpoet_submit" value="' . $expectedLabel . '"', $result);

    // Hidden fields
    $this->assertStringContainsString('name="data[captcha_session_id]" value="' . $sessionId . '"', $result);
    $this->assertStringContainsString('name="user_login" value="' . $userLogin . '"', $result);
    $this->assertStringContainsString('name="user_email" value="' . $userEmail . '"', $result);
  }

  public function testItRendersInWCRegisterForm() {
    $expectedLabel = 'Register';
    $expectedActionUrl = '/?page_id=11';
    $userLogin = 'example';
    $userEmail = 'example@domain.com';
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WC_FORM, [
      'referrer_form_url' => $expectedActionUrl,
      'register' => $expectedLabel,
      'email' => $userLogin,
      'password' => $userEmail,
    ]);

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WC_FORM,
    ]);

    // Action URL
    $this->assertStringContainsString('<form method="POST" action="' . $expectedActionUrl . '"', $result);

    // Submit button
    $this->assertStringContainsString('type="submit" class="mailpoet_submit" value="' . $expectedLabel . '"', $result);

    // Hidden fields
    $this->assertStringContainsString('name="data[captcha_session_id]" value="' . $sessionId . '"', $result);
    $this->assertStringContainsString('name="email" value="' . $userLogin . '"', $result);
    $this->assertStringContainsString('name="password" value="' . $userEmail . '"', $result);
  }

  public function testItHandlesMissingFormLabel() {
    $formRepository = $this->diContainer->get(FormsRepository::class);

    $form = new FormEntity('captcha-render-test-form');
    $form->setBody([
      [
        'type' => 'text',
        'id' => 'email',
      ],
      [
        'type' => 'submit',
        'params' => [
          'label' => '', // empty label
        ],
      ],
    ]);

    $form->setSettings([
      'success_message' => 'tada!',
    ]);

    $form->setId(1);
    $formRepository->persist($form);
    $formRepository->flush();

    $sessionId = self::SESSION_ID;
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $captchaSession->setFormData($sessionId, ['form_id' => $form->getId()]);

    $data = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_MP_FORM,
    ];

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render($data);
    $this->assertStringContainsString('value="Subscribe"', $result);
  }

  public function testItEscapesHtmlAttributesInHiddenFields(): void {
    $fieldName = 'field"name';
    $fieldValue = 'value"&test';
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, [
      'referrer_form_url' => home_url('/?param=value&other=test'),
      'wp-submit' => 'Register',
      $fieldName => $fieldValue,
    ]);

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
    ]);

    $this->assertStringContainsString('action="' . home_url('/') . '?param=value&#038;other=test"', $result);
    $this->assertStringContainsString('name="field&quot;name"', $result);
    $this->assertStringContainsString('value="value&quot;&amp;test"', $result);
  }

  public function testItEscapesSuccessAndErrorMessages(): void {
    $formRepository = $this->diContainer->get(FormsRepository::class);
    $form = new FormEntity('captcha-render-test-form');

    $successMessage = 'Success! <script>alert("test")</script>';
    $form->setBody([
      [
        'id' => 'email',
        'type' => 'text',
      ],
      [
        'type' => 'submit',
        'params' => [
          'label' => 'Subscribe',
        ],
      ],
    ]);

    $form->setSettings([
      'success_message' => $successMessage,
    ]);

    $form->setId(1);
    $formRepository->persist($form);
    $formRepository->flush();

    $sessionId = self::SESSION_ID;
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $captchaSession->setFormData($sessionId, ['form_id' => $form->getId()]);

    $data = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_MP_FORM,
    ];

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render($data);

    $this->assertStringContainsString('Success! &lt;script&gt;alert(&quot;test&quot;)&lt;/script&gt;', $result);
    $this->assertStringNotContainsString('<script>alert("test")</script>', $result);
  }

  public function testItEscapesReferrerFormUrlProperly(): void {
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, [
      'referrer_form_url' => home_url('/register?param=value"onload=alert(1)&other=test'),
      'wp-submit' => 'Register',
      'user_login' => 'testuser',
    ]);

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
    ]);

    $this->assertStringContainsString('action="' . home_url('/register') . '?param=valueonload=alert(1)&#038;other=test"', $result);
    $this->assertStringNotContainsString('param=value"onload', $result);
    $this->assertStringNotContainsString('name="referrer_form_url"', $result);
    $this->assertStringContainsString('name="user_login" value="testuser"', $result);
  }

  private function renderRegisterFormAction(string $referrer, string $url): string {
    $submitKey = $referrer === CaptchaUrlFactory::REFERER_WC_FORM ? 'register' : 'wp-submit';
    $sessionId = $this->seedRegisterStash($referrer, ['referrer_form_url' => $url, $submitKey => 'Register']);
    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => $referrer,
    ]);
    $this->assertIsString($result);
    $this->assertSame(1, preg_match('/<form method="POST" action="([^"]*)"/', $result, $matches));
    return $matches[1];
  }

  private function getWcFallbackUrl(): string {
    if (!class_exists('WooCommerce') || !function_exists('wc_get_page_permalink')) {
      $this->markTestSkipped('WooCommerce is not available.');
    }
    $this->previousMyAccountPageId = get_option('woocommerce_myaccount_page_id', null);
    $pageId = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Captcha test account', 'post_name' => 'captcha-test-account']);
    $this->createdPageId = (int)$pageId;
    update_option('woocommerce_myaccount_page_id', $pageId);
    $permalink = wc_get_page_permalink('myaccount');
    $this->assertStringContainsString('captcha-test-account', $permalink);
    return $permalink;
  }

  public function testItUsesTheRegistrationUrlWhenTheReferrerUrlIsOffSite(): void {
    $action = $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WP_FORM, 'https://other-site.example/register');
    $this->assertSame(esc_url(wp_registration_url()), $action);
  }

  public function testItUsesTheRegistrationUrlWhenTheReferrerUrlIsProtocolRelative(): void {
    $action = $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WP_FORM, '//other-site.example/x');
    $this->assertSame(esc_url(wp_registration_url()), $action);
  }

  public function testItUsesTheRegistrationUrlWhenTheReferrerHostIsAllowedForRedirectsButIsNotTheSiteHost(): void {
    $allowHost = function ($hosts) {
      $hosts[] = 'allowed.example';
      return $hosts;
    };
    add_filter('allowed_redirect_hosts', $allowHost);
    try {
      $action = $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WP_FORM, 'https://allowed.example/register');
    } finally {
      remove_filter('allowed_redirect_hosts', $allowHost);
    }
    $this->assertSame(esc_url(wp_registration_url()), $action);
  }

  public function testItUsesTheMyAccountUrlWhenTheWooCommerceReferrerUrlIsOffSite(): void {
    $expected = $this->getWcFallbackUrl();
    $action = $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WC_FORM, 'https://other-site.example/my-account/');
    $this->assertSame(esc_url($expected), $action);
  }

  public function testItUsesTheMyAccountUrlWhenTheWooCommerceReferrerUrlIsProtocolRelative(): void {
    $expected = $this->getWcFallbackUrl();
    $action = $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WC_FORM, '//other-site.example/my-account/');
    $this->assertSame(esc_url($expected), $action);
  }

  public function testItKeepsAReferrerUrlOnTheSiteHost(): void {
    $url = home_url('/custom-register/?a=1');
    $action = $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WP_FORM, $url);
    $this->assertSame(esc_url($url), $action);
  }

  public function testItKeepsAReferrerUrlWhenTheSiteHostIsConfiguredWithDifferentLetterCase(): void {
    $host = (string)wp_parse_url(home_url(), PHP_URL_HOST);
    $mixedCaseHomeUrl = str_replace($host, ucfirst($host), home_url());
    $mixedCaseHome = function () use ($mixedCaseHomeUrl) {
      return $mixedCaseHomeUrl;
    };
    $url = home_url('/custom-register/');
    add_filter('pre_option_home', $mixedCaseHome);
    try {
      $action = $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WP_FORM, $url);
    } finally {
      remove_filter('pre_option_home', $mixedCaseHome);
    }
    $this->assertSame(esc_url($url), $action);
  }

  public function testItKeepsARootRelativeReferrerUrl(): void {
    $action = $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WC_FORM, '/my-account/');
    $this->assertSame('/my-account/', $action);
  }

  private function renderRegisterFormActionWithHome(string $home, string $url): string {
    $homeFilter = function () use ($home) {
      return $home;
    };
    add_filter('pre_option_home', $homeFilter);
    try {
      return $this->renderRegisterFormAction(CaptchaUrlFactory::REFERER_WP_FORM, $url);
    } finally {
      remove_filter('pre_option_home', $homeFilter);
    }
  }

  /**
   * @dataProvider dataForReferrerUrlsOnAnotherOrigin
   */
  public function testItUsesTheRegistrationUrlWhenTheReferrerUrlDiffersInSchemeOrPort(string $home, string $url): void {
    $action = $this->renderRegisterFormActionWithHome($home, $url);
    $this->assertSame(esc_url(wp_registration_url()), $action);
  }

  public function dataForReferrerUrlsOnAnotherOrigin(): array {
    return [
      'http on an https site' => ['https://shop.example', 'http://shop.example/register/'],
      'https on an http site' => ['http://shop.example', 'https://shop.example/register/'],
      'http with the https port' => ['https://shop.example', 'http://shop.example:443/register/'],
      'https with the http port' => ['http://shop.example', 'https://shop.example:80/register/'],
      'other port' => ['https://shop.example', 'https://shop.example:8443/register/'],
      'port on a site without one' => ['http://shop.example', 'http://shop.example:8080/register/'],
      'no port on a site with one' => ['http://shop.example:8080', 'http://shop.example/register/'],
      'other port on a site with one' => ['http://shop.example:8080', 'http://shop.example:9090/register/'],
    ];
  }

  /**
   * @dataProvider dataForReferrerUrlsOnTheSameOrigin
   */
  public function testItKeepsAReferrerUrlWithTheSameSchemeHostAndPort(string $home, string $url): void {
    $action = $this->renderRegisterFormActionWithHome($home, $url);
    $this->assertSame(esc_url($url), $action);
  }

  public function dataForReferrerUrlsOnTheSameOrigin(): array {
    return [
      'same origin' => ['https://shop.example', 'https://shop.example/register/'],
      'same custom port' => ['https://shop.example:8443', 'https://shop.example:8443/register/'],
      'explicit default https port' => ['https://shop.example', 'https://shop.example:443/register/'],
      'implicit default https port' => ['https://shop.example:443', 'https://shop.example/register/'],
      'explicit default http port' => ['http://shop.example', 'http://shop.example:80/register/'],
      'implicit default http port' => ['http://shop.example:80', 'http://shop.example/register/'],
    ];
  }

  public function testItShowsTheRegistrationUrlInTheUsedMessageWhenTheStoredActionUrlIsOffSite(): void {
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, ['wp-submit' => 'Register']);
    $session = $this->diContainer->get(CaptchaSession::class);
    $session->setFormData($sessionId, [
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
      'action_url' => 'https://other-site.example/register',
      'rendered' => true,
    ]);

    $result = $this->createFreshRenderer()->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
    ]);

    $this->assertStringContainsString('This CAPTCHA page has already been used.', $result);
    $this->assertStringContainsString('href="' . esc_url(wp_registration_url()) . '"', $result);
    $this->assertStringNotContainsString('other-site.example', $result);
  }

  public function testItKeepsAStoredActionUrlOnTheSiteInTheUsedMessage(): void {
    $url = home_url('/custom-register/');
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, ['wp-submit' => 'Register']);
    $this->diContainer->get(CaptchaSession::class)->setFormData($sessionId, [
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
      'action_url' => $url,
      'rendered' => true,
    ]);

    $result = $this->createFreshRenderer()->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
    ]);

    $this->assertStringContainsString('href="' . esc_url($url) . '"', $result);
  }

  public function testItDoesNotRenderTheRegisterFormWhenRegistrationCaptchaIsOff(): void {
    $this->diContainer->get(SettingsController::class)->set(CaptchaConstants::ON_REGISTER_FORMS_SETTING_NAME, false);
    foreach ([CaptchaUrlFactory::REFERER_WP_FORM, CaptchaUrlFactory::REFERER_WC_FORM] as $referrer) {
      $submitKey = $referrer === CaptchaUrlFactory::REFERER_WC_FORM ? 'register' : 'wp-submit';
      $sessionId = $this->seedRegisterStash($referrer, ['referrer_form_url' => '/my-account/', $submitKey => 'Register']);
      $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
        'captcha_session_id' => $sessionId,
        'referrer_form' => $referrer,
      ]);
      $this->assertFalse($result);
    }
  }

  /**
   * @param array<string, mixed> $wpOverrides
   */
  private function renderRegisterFormActionWithWpOverrides(array $wpOverrides, string $url): string {
    $wp = Stub::make(WPFunctions::class, $wpOverrides);
    $renderer = $this->createFreshRenderer();
    $property = new \ReflectionProperty(CaptchaFormRenderer::class, 'wp');
    $property->setAccessible(true);
    $property->setValue($renderer, $wp);
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, ['referrer_form_url' => $url, 'wp-submit' => 'Register']);
    $result = $renderer->render(['captcha_session_id' => $sessionId, 'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM]);
    $this->assertIsString($result);
    $this->assertSame(1, preg_match('/<form method="POST" action="([^"]*)"/', $result, $matches));
    return $matches[1];
  }

  public function testItUsesTheRegistrationUrlWhenTheValidatedReferrerUrlIsEmpty(): void {
    $action = $this->renderRegisterFormActionWithWpOverrides(['wpValidateRedirect' => ''], '/custom-register/');
    $this->assertSame(esc_url(wp_registration_url()), $action);
  }

  public function testItUsesTheRegistrationUrlWhenAHostlessReferrerUrlStartsWithTwoSlashes(): void {
    $action = $this->renderRegisterFormActionWithWpOverrides(
      ['wpValidateRedirect' => '//other-site.example/x', 'wpParseUrl' => false],
      '//other-site.example/x'
    );
    $this->assertSame(esc_url(wp_registration_url()), $action);
  }

  public function testItUsesTheRegistrationUrlWhenAHostlessReferrerUrlDoesNotStartWithASlash(): void {
    $action = $this->renderRegisterFormActionWithWpOverrides(
      ['wpValidateRedirect' => 'other-site.example/x', 'wpParseUrl' => false],
      'other-site.example/x'
    );
    $this->assertSame(esc_url(wp_registration_url()), $action);
  }

  public function testItValidatesReferrerFormTypes(): void {
    $testee = $this->diContainer->get(CaptchaFormRenderer::class);

    $invalidId = $this->seedRegisterStash('invalid_type', ['referrer_form_url' => 'https://example.com']);
    $this->assertFalse($testee->render([
      'captcha_session_id' => $invalidId,
      'referrer_form' => 'invalid_type',
    ]));

    $validTypes = [
      CaptchaUrlFactory::REFERER_WP_FORM,
      CaptchaUrlFactory::REFERER_WC_FORM,
    ];
    foreach ($validTypes as $validType) {
      $submitKey = ($validType === CaptchaUrlFactory::REFERER_WC_FORM) ? 'register' : 'wp-submit';
      $sessionId = $this->seedRegisterStash($validType, [
        'referrer_form_url' => 'https://example.com',
        $submitKey => 'Register',
      ]);

      $result = $testee->render(['captcha_session_id' => $sessionId, 'referrer_form' => $validType]);
      $this->assertIsString($result);
      $this->assertStringContainsString('<form', $result);
    }
  }

  public function testItIgnoresRequestValuesWhenRenderingTheRegisterForm(): void {
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, [
      'referrer_form_url' => '/wp-login.php?action=register',
      'wp-submit' => 'Register',
      'user_login' => 'stashed_login',
    ]);
    $_GET['user_login'] = 'query_login';

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
      'user_email' => 'data_email@example.com',
      'referrer_form_url' => 'https://elsewhere.example/',
    ]);

    $this->assertStringContainsString('name="user_login" value="stashed_login"', $result);
    $this->assertStringNotContainsString('query_login', $result);
    $this->assertStringNotContainsString('data_email@example.com', $result);
    $this->assertStringNotContainsString('elsewhere.example', $result);
  }

  public function testItDoesNotRenderStashedFieldsForAnotherReferrer(): void {
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, [
      'referrer_form_url' => '/wp-login.php?action=register',
      'wp-submit' => 'Register',
      'user_login' => 'stashed_login',
    ]);

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WC_FORM,
    ]);

    $this->assertFalse($result);
  }

  public function testItDoesNotRenderRegisterFormForMissingStash(): void {
    $session = $this->diContainer->get(CaptchaSession::class);
    $sessionId = $session->generateSessionId();
    $this->createdSessionIds[] = $sessionId;
    $session->setFormData($sessionId, ['form_id' => 1]);

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
    ]);

    $this->assertFalse($result);
  }

  public function testItReturnsTheSameRegisterFormWhenRenderedTwiceInOneRequest(): void {
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, [
      'referrer_form_url' => '/wp-login.php?action=register',
      'wp-submit' => 'Register',
      'user_login' => 'stashed_login',
    ]);
    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $data = ['captcha_session_id' => $sessionId, 'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM];

    $first = $testee->render($data);
    $second = $testee->render($data);

    $this->assertStringContainsString('name="user_login" value="stashed_login"', $first);
    $this->assertSame($first, $second);
  }

  public function testItKeepsOnlyTheSessionDataAfterRenderingTheRegisterForm(): void {
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WC_FORM, [
      'referrer_form_url' => '/my-account/',
      'register' => 'Register',
      'email' => 'jane@example.com',
      'password' => 'secret-value',
    ]);
    $session = $this->diContainer->get(CaptchaSession::class);

    $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WC_FORM,
    ]);

    $this->assertSame(
      ['referrer_form' => CaptchaUrlFactory::REFERER_WC_FORM, 'action_url' => '/my-account/', 'rendered' => true],
      $session->getFormData($sessionId)
    );
    // The image, audio and refresh requests still find the session and its phrase.
    $this->assertTrue($session->exists($sessionId));
    $this->assertNotFalse($session->getCaptchaHash($sessionId));
  }

  public function testItShowsALinkBackToTheRegisterFormWhenTheFieldsWereAlreadyRendered(): void {
    $sessionId = $this->seedRegisterStash(CaptchaUrlFactory::REFERER_WP_FORM, [
      'referrer_form_url' => '/wp-login.php?action=register',
      'wp-submit' => 'Register',
      'user_login' => 'stashed_login',
      'user_email' => 'jane@example.com',
    ]);
    $data = ['captcha_session_id' => $sessionId, 'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM];
    $this->createFreshRenderer()->render($data);

    $result = $this->createFreshRenderer()->render($data);

    $this->assertStringContainsString('This CAPTCHA page has already been used. Go back to the registration form to try again.', $result);
    $this->assertStringContainsString('href="/wp-login.php?action=register"', $result);
    $this->assertStringNotContainsString('stashed_login', $result);
    $this->assertStringNotContainsString('jane@example.com', $result);
    $this->assertStringNotContainsString('<form', $result);
  }

  public function testItDoesNotRenderOrCreateStateForUnknownSession(): void {
    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $referrers = [
      CaptchaUrlFactory::REFERER_WP_FORM,
      CaptchaUrlFactory::REFERER_WC_FORM,
    ];
    foreach ($referrers as $referrer) {
      $result = $testee->render([
        'captcha_session_id' => self::SESSION_ID,
        'referrer_form' => $referrer,
        'referrer_form_url' => 'https://example.com',
        'wp-submit' => 'Register',
        'register' => 'Register',
      ]);
      $this->assertFalse($result);
      $this->assertFalse(get_transient('MAILPOET_' . self::SESSION_ID . '_hash'));
    }
  }

  public function testItDoesNotRenderSubscriptionFormForUnknownSession(): void {
    $formRepository = $this->diContainer->get(FormsRepository::class);
    $form = new FormEntity('captcha-render-test-form');
    $form->setBody([['id' => 'email', 'type' => 'text'], ['type' => 'submit', 'params' => ['label' => 'Subscribe']]]);
    $formRepository->persist($form);
    $formRepository->flush();

    $_GET['mailpoet_error'] = (string)$form->getId();
    try {
      $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
        'captcha_session_id' => self::SESSION_ID,
        'referrer_form' => CaptchaUrlFactory::REFERER_MP_FORM,
      ]);
    } finally {
      unset($_GET['mailpoet_error']);
    }
    $this->assertFalse($result);
    $this->assertFalse(get_transient('MAILPOET_' . self::SESSION_ID . '_hash'));
  }

  public function testItDoesNotRenderForMalformedSessionId(): void {
    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render([
      'captcha_session_id' => 'test-session',
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
      'referrer_form_url' => 'https://example.com',
      'wp-submit' => 'Register',
    ]);
    $this->assertFalse($result);
    $this->assertFalse(get_transient('MAILPOET_test-session_hash'));
  }
}
