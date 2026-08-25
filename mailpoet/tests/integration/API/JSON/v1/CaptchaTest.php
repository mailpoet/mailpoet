<?php declare(strict_types = 1);

namespace integration\API\JSON\v1;

use MailPoet\API\JSON\Response;
use MailPoet\API\JSON\v1\Captcha;
use MailPoet\Captcha\CaptchaSession;
use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\Populator;
use MailPoet\WP\Functions as WPFunctions;

class CaptchaTest extends \MailPoetTest {
  public function _before() {
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();

    parent::_before();
  }

  public function testItCanRenderCaptcha(): void {
    $response = $this->createCaptchaEndpoint()->render();

    verify($response->status)->equals(Response::REDIRECT);
    verify($response->location)->stringContainsString('mailpoet_router&endpoint=captcha&action=render&data=');
  }

  public function testItKeepsCaptchaPagePermalinkOnDifferentHost(): void {
    $wp = $this->diContainer->get(WPFunctions::class);
    $permalinkOnOtherHost = function () {
      return 'https://captcha.example.com/captcha-page/';
    };
    $wp->addFilter('post_type_link', $permalinkOnOtherHost);
    try {
      $response = $this->createCaptchaEndpoint()->render();
    } finally {
      $wp->removeFilter('post_type_link', $permalinkOnOtherHost);
    }

    verify($response->status)->equals(Response::REDIRECT);
    verify($response->location)->stringContainsString('https://captcha.example.com/captcha-page/');
  }

  private function createCaptchaEndpoint(): Captcha {
    return new Captcha(
      $this->diContainer->get(CaptchaSession::class),
      $this->diContainer->get(CaptchaUrlFactory::class),
      $this->diContainer->get(WPFunctions::class)
    );
  }
}
