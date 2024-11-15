<?php declare(strict_types = 1);

namespace integration\API\JSON\v1;

use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\v1\Captcha;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\Populator;

class CaptchaTest extends \MailPoetTest {
  public function _before() {
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();

    parent::_before();
  }

  public function testItCanRenderCaptcha(): void {
    $captchaSession = $this->diContainer->get(CaptchaSession::class);
    $urlFactory = $this->diContainer->get(CaptchaUrlFactory::class);

    $captcha = new Captcha($captchaSession, $urlFactory);
    $response = $captcha->render();

    verify($response->status)->equals(Response::REDIRECT);
    verify($response->location)->stringContainsString('mailpoet_router&endpoint=captcha&action=render&data=');
  }
}
