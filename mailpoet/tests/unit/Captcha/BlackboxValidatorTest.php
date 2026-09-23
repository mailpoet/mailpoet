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
      ['verifyBlackbox' => Expected::once(function() { return [];
      }),
      ],
      $this
    );
    $this->withRemoteAddr('203.0.113.1', function() use ($bridge) {
      $testee = new BlackboxValidator($bridge, $this->makeLoggerFactoryStub());
      verify($testee->verify(null))->equals(BlackboxValidator::DECISION_ERROR);
    });
  }

  public function testItTakesTheNoSessionPathWhenSessionIdIsNull() {
    $bridge = Stub::make(
      Bridge::class,
      [
        'verifyBlackbox' => Expected::once(function($sessionId, $context, $visitorIp) {
          verify($sessionId)->null();
          verify($visitorIp)->equals('203.0.113.1');
          return ['decision' => 'allow'];
        }),
      ],
      $this
    );
    $this->withRemoteAddr('203.0.113.1', function() use ($bridge) {
      $testee = new BlackboxValidator($bridge, $this->makeLoggerFactoryStub());
      verify($testee->verify(null))->equals(BlackboxValidator::DECISION_ALLOW);
    });
  }

  public function testItSkipsTheCallAndFailsOpenWhenThereIsNoSessionAndNoIp() {
    $bridge = Stub::make(Bridge::class, ['verifyBlackbox' => Expected::never()], $this);
    $this->withRemoteAddr(null, function() use ($bridge) {
      $testee = new BlackboxValidator($bridge, $this->makeLoggerFactoryStub());
      verify($testee->verify(null))->equals(BlackboxValidator::DECISION_ERROR);
    });
  }

  private function withRemoteAddr(?string $ip, callable $callback): void {
    $hadRemoteAddr = array_key_exists('REMOTE_ADDR', $_SERVER);
    $previousRemoteAddr = $hadRemoteAddr ? $_SERVER['REMOTE_ADDR'] : null;
    if ($ip !== null) {
      $_SERVER['REMOTE_ADDR'] = $ip;
    } else {
      unset($_SERVER['REMOTE_ADDR']);
    }
    try {
      $callback();
    } finally {
      if ($hadRemoteAddr) {
        $_SERVER['REMOTE_ADDR'] = $previousRemoteAddr;
      } else {
        unset($_SERVER['REMOTE_ADDR']);
      }
    }
  }

  private function makeLoggerFactoryStub(): LoggerFactory {
    return Stub::makeEmpty(LoggerFactory::class, ['getLogger' => Expected::never()], $this);
  }
}
