<?php declare(strict_types = 1);

namespace integration\Captcha;

use MailPoet\Captcha\CaptchaUrlFactory;
use MailPoet\Config\Populator;
use MailPoet\Router\Endpoints\Captcha as CaptchaEndpoint;
use MailPoet\Router\Router;
use MailPoet\Settings\Pages;

class CaptchaUrlFactoryTest extends \MailPoetTest {

  private CaptchaUrlFactory $urlFactory;

  public function _before() {
    parent::_before();
    $this->urlFactory = $this->diContainer->get(CaptchaUrlFactory::class);

    // Prepare the settings
    $populator = $this->diContainer->get(Populator::class);
    $populator->up();
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
    $url = $this->urlFactory->getCaptchaImageUrl(100, 200, 'abc');

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
}
