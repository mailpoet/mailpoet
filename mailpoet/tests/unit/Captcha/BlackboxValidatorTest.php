<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use Codeception\Stub;
use Codeception\Stub\Expected;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Services\Bridge;
use MailPoet\Services\Bridge\BlackboxVerifyException;
use MailPoetVendor\Monolog\Logger;

class BlackboxValidatorTest extends \MailPoetUnitTest {
  public function testItReturnsTheDecisionFromTheBridge() {
    $bridge = Stub::make(
      Bridge::class,
      [
        'verifyBlackbox' => Expected::once(function($sessionId, $context) {
          verify($sessionId)->equals('bb-session');
          verify($context)->equals(['action' => 'subscribe']);
          return ['decision' => 'challenge', 'risk_score' => 0.71];
        }),
      ],
      $this
    );
    $testee = new BlackboxValidator($bridge, $this->makeLoggerFactoryStub());
    verify($testee->verify('bb-session', ['action' => 'subscribe']))->equals('challenge');
  }

  public function testItReturnsErrorAndLogsWhenTheBridgeThrows() {
    $bridge = Stub::make(
      Bridge::class,
      [
        'verifyBlackbox' => function() {
          throw BlackboxVerifyException::create()->withCode(500)->withMessage('boom');
        },
      ],
      $this
    );
    $logger = Stub::makeEmpty(Logger::class, ['error' => Expected::once()], $this);
    $loggerFactory = Stub::makeEmpty(LoggerFactory::class, ['getLogger' => $logger], $this);
    $testee = new BlackboxValidator($bridge, $loggerFactory);
    verify($testee->verify('bb-session'))->equals(BlackboxValidator::DECISION_ERROR);
  }

  public function testItReturnsErrorWhenTheBridgeOmitsADecision() {
    $bridge = Stub::make(
      Bridge::class,
      ['verifyBlackbox' => function() { return [];
      },
      ],
      $this
    );
    $testee = new BlackboxValidator($bridge, $this->makeLoggerFactoryStub());
    verify($testee->verify(null))->equals(BlackboxValidator::DECISION_ERROR);
  }

  public function testItTakesTheNoSessionPathWhenSessionIdIsNull() {
    $bridge = Stub::make(
      Bridge::class,
      [
        'verifyBlackbox' => Expected::once(function($sessionId) {
          verify($sessionId)->null();
          return ['decision' => 'allow'];
        }),
      ],
      $this
    );
    $testee = new BlackboxValidator($bridge, $this->makeLoggerFactoryStub());
    verify($testee->verify(null))->equals(BlackboxValidator::DECISION_ALLOW);
  }

  private function makeLoggerFactoryStub(): LoggerFactory {
    return Stub::makeEmpty(LoggerFactory::class, ['getLogger' => Expected::never()], $this);
  }
}
