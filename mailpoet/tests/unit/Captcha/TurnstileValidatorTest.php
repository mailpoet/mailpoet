<?php declare(strict_types = 1);

namespace unit\Captcha;

use Codeception\Stub;
use MailPoet\Captcha\TurnstileValidator;
use MailPoet\Settings\SettingsController;
use MailPoet\WP\Functions as WPFunctions;

class TurnstileValidatorTest extends \MailPoetUnitTest {
  const RES_TOKEN = 'someToken';

  public function testItValidatesTokenWithRemoteIp() {
    $captchaSettings = [
      'turnstile_secret_token' => 'turnstile_secret_token',
    ];
    $response = json_encode(['success' => true]);
    $remoteAddr = '203.0.113.1';
    $hadRemoteAddr = array_key_exists('REMOTE_ADDR', $_SERVER);
    $previousRemoteAddr = $hadRemoteAddr ? $_SERVER['REMOTE_ADDR'] : null;
    $_SERVER['REMOTE_ADDR'] = $remoteAddr;
    try {
      $settings = $this->makeSettingsStub($captchaSettings);
      $wp = $this->makeWpForSuccessfulTurnstileValidation($captchaSettings, $response, $remoteAddr);
      $testee = new TurnstileValidator($wp, $settings);
      verify($testee->validate(self::RES_TOKEN))->null();
    } finally {
      if ($hadRemoteAddr) {
        $_SERVER['REMOTE_ADDR'] = $previousRemoteAddr;
      } else {
        unset($_SERVER['REMOTE_ADDR']);
      }
    }
  }

  public function testItValidatesTokenWithoutRemoteIp() {
    $captchaSettings = [
      'turnstile_secret_token' => 'turnstile_secret_token',
    ];
    $response = json_encode(['success' => true]);
    $hadRemoteAddr = array_key_exists('REMOTE_ADDR', $_SERVER);
    $previousRemoteAddr = $hadRemoteAddr ? $_SERVER['REMOTE_ADDR'] : null;
    unset($_SERVER['REMOTE_ADDR']);
    try {
      $settings = $this->makeSettingsStub($captchaSettings);
      $wp = $this->makeWpForSuccessfulTurnstileValidation($captchaSettings, $response, null);
      $testee = new TurnstileValidator($wp, $settings);
      verify($testee->validate(self::RES_TOKEN))->null();
    } finally {
      if ($hadRemoteAddr) {
        $_SERVER['REMOTE_ADDR'] = $previousRemoteAddr;
      }
    }
  }

  private function makeSettingsStub(array $captchaSettings): SettingsController {
    return Stub::make(
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
  }

  private function makeWpForSuccessfulTurnstileValidation(
    array $captchaSettings,
    string $encodedResponse,
    ?string $expectedRemoteIp
  ): WPFunctions {
    return Stub::make(
      WPFunctions::class,
      [
        'isWpError' => false,
        'wpRemotePost' => function ($url, $args) use ($captchaSettings, $encodedResponse, $expectedRemoteIp) {
          verify($url)->equals('https://challenges.cloudflare.com/turnstile/v0/siteverify');
          verify($args['body']['secret'])->equals($captchaSettings['turnstile_secret_token']);
          verify($args['body']['response'])->equals(self::RES_TOKEN);
          verify($args['timeout'])->equals(5);
          if ($expectedRemoteIp !== null) {
            verify($args['body']['remoteip'])->equals($expectedRemoteIp);
          } else {
            verify(isset($args['body']['remoteip']))->false();
          }
          return $encodedResponse;
        },
        'wpRemoteRetrieveBody' => function ($data) use ($encodedResponse) {
          verify($data)->equals($encodedResponse);
          return $encodedResponse;
        },
      ],
      $this
    );
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
