<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaFormRenderer;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\Populator;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;

class CaptchaFormRendererTest extends \MailPoetTest {
  const SESSION_ID = 'abcd1234abcd1234abcd1234abcd1234';

  public function _before() {
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();

    parent::_before();
  }

  /** @var string[] */
  private array $createdSessionIds = [];

  public function _after() {
    $session = $this->diContainer->get(CaptchaSession::class);
    $session->reset(self::SESSION_ID);
    foreach ($this->createdSessionIds as $id) {
      $session->reset($id);
    }
    unset($_GET['user_login'], $_GET['password']);
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
      'referrer_form_url' => 'https://example.com/?param=value&other=test',
      'wp-submit' => 'Register',
      $fieldName => $fieldValue,
    ]);

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
    ]);

    $this->assertStringContainsString('action="https://example.com/?param=value&#038;other=test"', $result);
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
      'referrer_form_url' => 'https://example.com/register?param=value"onload=alert(1)&other=test',
      'wp-submit' => 'Register',
      'user_login' => 'testuser',
    ]);

    $result = $this->diContainer->get(CaptchaFormRenderer::class)->render([
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
    ]);

    $this->assertStringContainsString('action="https://example.com/register?param=valueonload=alert(1)&#038;other=test"', $result);
    $this->assertStringNotContainsString('param=value"onload', $result);
    $this->assertStringNotContainsString('action="https://example.com/register?param=value"onload', $result);
    $this->assertStringNotContainsString('name="referrer_form_url"', $result);
    $this->assertStringContainsString('name="user_login" value="testuser"', $result);
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
