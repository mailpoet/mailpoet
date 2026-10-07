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

  public function _after() {
    $this->diContainer->get(CaptchaSession::class)->reset(self::SESSION_ID);
    parent::_after();
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
    $formRepository = $this->diContainer->get(FormsRepository::class);
    $form = new FormEntity('captcha-render-test-form');

    $form->setBody([
      [
        'id' => 'email',
        'type' => 'text',
      ],
      [
        'type' => 'submit',
      ],
    ]);

    $form->setId(1);
    $formRepository->persist($form);
    $formRepository->flush();

    $sessionId = self::SESSION_ID;
    $expectedLabel = 'Register';
    $expectedActionUrl = '/wp-login.php?action=register';
    $userLogin = 'example';
    $userEmail = 'example@domain.com';
    $this->diContainer->get(CaptchaSession::class)->setFormData($sessionId, ['form_id' => $form->getId()]);
    $data = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
      'referrer_form_url' => $expectedActionUrl,
      // WP form specific data
      'wp-submit' => $expectedLabel,
      'user_login' => $userLogin,
      'user_email' => $userEmail,
    ];

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render($data);

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
    $formRepository = $this->diContainer->get(FormsRepository::class);
    $form = new FormEntity('captcha-render-test-form');

    $form->setBody([
      [
        'id' => 'email',
        'type' => 'text',
      ],
      [
        'type' => 'submit',
      ],
    ]);

    $form->setId(1);
    $formRepository->persist($form);
    $formRepository->flush();

    $sessionId = self::SESSION_ID;
    $expectedLabel = 'Register';
    $expectedActionUrl = 'https://example.com/?page_id=11';
    $userLogin = 'example';
    $userEmail = 'example@domain.com';
    $this->diContainer->get(CaptchaSession::class)->setFormData($sessionId, ['form_id' => $form->getId()]);
    $data = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WC_FORM,
      'referrer_form_url' => $expectedActionUrl,
      // WC form specific data
      'register' => $expectedLabel,
      'email' => $userLogin,
      'password' => $userEmail,
    ];

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render($data);

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
    $sessionId = self::SESSION_ID;
    $this->diContainer->get(CaptchaSession::class)->setFormData($sessionId, ['stash' => 'seed']);
    $actionUrl = 'https://example.com/?param=value&other=test';
    $fieldName = 'field"name';
    $fieldValue = 'value"&test';

    $data = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
      'referrer_form_url' => $actionUrl,
      'wp-submit' => 'Register',
      $fieldName => $fieldValue,
    ];

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render($data);

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
    $sessionId = self::SESSION_ID;
    $this->diContainer->get(CaptchaSession::class)->setFormData($sessionId, ['stash' => 'seed']);

    $maliciousUrl = 'https://example.com/register?param=value"onload=alert(1)&other=test';

    $data = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => CaptchaUrlFactory::REFERER_WP_FORM,
      'referrer_form_url' => $maliciousUrl,
      'wp-submit' => 'Register',
      'user_login' => 'testuser',
    ];

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render($data);

    $this->assertStringContainsString('action="https://example.com/register?param=valueonload=alert(1)&#038;other=test"', $result);
    $this->assertStringNotContainsString('param=value"onload', $result);
    $this->assertStringNotContainsString('action="https://example.com/register?param=value"onload', $result);
    $this->assertStringNotContainsString('name="referrer_form_url"', $result);
    $this->assertStringContainsString('name="user_login" value="testuser"', $result);
  }

  public function testItValidatesReferrerFormTypes(): void {
    $sessionId = self::SESSION_ID;
    $this->diContainer->get(CaptchaSession::class)->setFormData($sessionId, ['stash' => 'seed']);

    // Test with invalid referrer_form - should return false
    $invalidData = [
      'captcha_session_id' => $sessionId,
      'referrer_form' => 'invalid_type',
      'referrer_form_url' => 'https://example.com',
    ];

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->render($invalidData);

    // Should return false for invalid referrer_form
    $this->assertFalse($result);

    // Test with valid referrer_form types
    $validTypes = [
      CaptchaUrlFactory::REFERER_WP_FORM,
      CaptchaUrlFactory::REFERER_WC_FORM,
    ];

    foreach ($validTypes as $validType) {
      $submitKey = ($validType === CaptchaUrlFactory::REFERER_WC_FORM) ? 'register' : 'wp-submit';
      $validData = [
        'captcha_session_id' => $sessionId,
        'referrer_form' => $validType,
        'referrer_form_url' => 'https://example.com',
        $submitKey => 'Register',
      ];

      $result = $testee->render($validData);
      $this->assertIsString($result);
      $this->assertStringContainsString('<form', $result);
    }
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
