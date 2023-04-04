<?php declare(strict_types = 1);

namespace MailPoet\Test\Subscription;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;
use MailPoet\Subscription\Captcha\CaptchaSession;
use MailPoet\Subscription\CaptchaFormRenderer;

class CaptchaFormRendererTest extends \MailPoetTest {
  public function testCaptchaSubmitTextIsConfigurable() {

    $expectedLabel = 'EXPECTED_LABEL';
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
          'label' => $expectedLabel,
        ],
      ],
    ]);
    $form->setSettings([
      'success_message' => 'tada!',
    ]);
    $form->setId(1);
    $formRepository->persist($form);
    $formRepository->flush();
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $captchaSession->init();
    $captchaSession->setFormData(['form_id' => $form->getId()]);

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->getCaptchaPageContent($captchaSession->getId());
    $this->assertStringContainsString('value="' . $expectedLabel . '"', $result);
  }

  public function testCaptchaSubmitTextHasDefault() {

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
          'label' => '',
        ],
      ],
    ]);
    $form->setSettings([
      'success_message' => 'tada!',
    ]);
    $form->setId(1);
    $formRepository->persist($form);
    $formRepository->flush();
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $captchaSession->init();
    $captchaSession->setFormData(['form_id' => $form->getId()]);

    $testee = $this->diContainer->get(CaptchaFormRenderer::class);
    $result = $testee->getCaptchaPageContent($captchaSession->getId());
    $this->assertStringContainsString('value="Subscribe"', $result);
  }

  public function _before() {
    parent::_before();
  }
}
