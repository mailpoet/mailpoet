<?php declare(strict_types = 1);

namespace MailPoet\Test\Captcha;

use MailPoet\Captcha\CaptchaFormRenderer;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\Populator;
use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;

class CaptchaFormRendererTest extends \MailPoetTest {
  public function _before() {
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();

    parent::_before();
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

    $sessionId = '123';
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

    $sessionId = '123';
    $expectedLabel = 'Register';
    $expectedActionUrl = '/wp-login.php?action=register';
    $userLogin = 'example';
    $userEmail = 'example@domain.com';
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

    $sessionId = '123';
    $expectedLabel = 'Register';
    $expectedActionUrl = 'https://example.com/?page_id=11';
    $userLogin = 'example';
    $userEmail = 'example@domain.com';
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

    $sessionId = '123';
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
}
