<?php declare(strict_types = 1);

namespace unit\Captcha;

use Codeception\Stub;
use MailPoet\Captcha\TurnstileValidator;
use MailPoet\Settings\SettingsController;
use MailPoet\Util\Helpers;
use MailPoet\WP\Functions as WPFunctions;

class TurnstileValidatorTest extends \MailPoetUnitTest {
  const RES_TOKEN = 'someToken';

  public function testItValidatesToken() {
    $captchaSettings = [
      'turnstile_secret_token' => 'turnstile_secret_token',
    ];

    $response = json_encode(['success' => true]);
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) use ($captchaSettings) {
          if ($key === 'captcha') {
            return $captchaSettings;
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => false,
        'wpRemotePost' => function ($url, $args) use ($captchaSettings, $response) {
          verify($url)->equals('https://challenges.cloudflare.com/turnstile/v0/siteverify');
          verify($args['body']['secret'])->equals($captchaSettings['turnstile_secret_token']);
          verify($args['body']['response'])->equals(self::RES_TOKEN);
          verify($args['timeout'])->equals(5);
          $expectedIp = Helpers::getIP();
          if (is_string($expectedIp) && $expectedIp !== '') {
            verify($args['body']['remoteip'])->equals($expectedIp);
          } else {
            verify(isset($args['body']['remoteip']))->false();
          }
          return $response;
        },
        'wpRemoteRetrieveBody' => function ($data) use ($response) {
          verify($data)->equals($response);
          return $response;
        },
      ],
      $this
    );

    $testee = new TurnstileValidator($wp, $settings);
    verify($testee->validate(self::RES_TOKEN))->null();
  }

  public function testItFailsIfTokenIsMissing() {
    $settings = Stub::make(SettingsController::class);
    $wp = Stub::make(WPFunctions::class);
    $testee = new TurnstileValidator($wp, $settings);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Please check the CAPTCHA.');
    $testee->validate('');
  }

  public function testItFailsIfTokenIsInvalid() {
    $response = json_encode(['success' => false]);
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) {
          if ($key === 'captcha') {
            return ['turnstile_secret_token' => 'turnstile_secret_token'];
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => false,
        'wpRemotePost' => function () use ($response) {
          return $response;
        },
        'wpRemoteRetrieveBody' => function () use ($response) {
          return $response;
        },
      ],
      $this
    );

    $testee = new TurnstileValidator($wp, $settings);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Invalid CAPTCHA. Try again.');
    $testee->validate('anyValue');
  }

  public function testItFailsOnJsonError() {
    $response = 'invalidJson';
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) {
          if ($key === 'captcha') {
            return ['turnstile_secret_token' => 'turnstile_secret_token'];
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => false,
        'wpRemotePost' => function () use ($response) {
          return $response;
        },
        'wpRemoteRetrieveBody' => function () use ($response) {
          return $response;
        },
      ],
      $this
    );

    $testee = new TurnstileValidator($wp, $settings);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Error while validating the CAPTCHA.');
    $testee->validate('anyValue');
  }

  public function testItFailsOnHttpError() {
    $response = new \stdClass();
    $settings = Stub::make(
      SettingsController::class,
      [
        'get' => function ($key) {
          if ($key === 'captcha') {
            return ['turnstile_secret_token' => 'turnstile_secret_token'];
          }
        },
      ],
      $this
    );

    $wp = Stub::make(
      WPFunctions::class,
      [
        'isWpError' => true,
        'wpRemotePost' => function () use ($response) {
          return $response;
        },
      ],
      $this
    );

    $testee = new TurnstileValidator($wp, $settings);

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Error while validating the CAPTCHA.');
    $testee->validate('anyValue');
  }
}
