<?php declare(strict_types = 1);

namespace MailPoet\Doctrine;

use MailPoetVendor\Doctrine\DBAL\Exception\InvalidFieldNameException;
use MailPoetVendor\Doctrine\DBAL\Exception\TableNotFoundException;

/**
 * The guard has to do two things, and each is asserted separately because
 * getting only the first is what made the tracking_allowed fix look complete
 * when the response body was still being corrupted.
 */
class SchemaGuardTest extends \MailPoetTest {
  /** @var SchemaGuard */
  private $guard;

  public function _before() {
    parent::_before();
    $this->guard = $this->diContainer->get(SchemaGuard::class);
  }

  public function testItReturnsTheReadResultWhenTheSchemaIsFine() {
    $result = $this->guard->readOr(function () {
      return ['a' => 1];
    }, []);

    verify($result)->equals(['a' => 1]);
  }

  public function testItFallsBackWhenAColumnIsMissing() {
    $result = $this->guard->readOr(function () {
      throw $this->makeInvalidFieldNameException();
    }, ['fallback']);

    verify($result)->equals(['fallback']);
  }

  public function testItFallsBackWhenATableIsMissing() {
    $result = $this->guard->readOr(function () {
      throw $this->makeTableNotFoundException();
    }, 0);

    verify($result)->equals(0);
  }

  /**
   * The half that is easy to miss. wpdb prints the failed query before Doctrine
   * raises it, so a caught exception still leaves "WordPress database error"
   * HTML in the response body.
   */
  public function testItPrintsNothingWhileTheSchemaIsNotReady() {
    global $wpdb;
    $showErrors = $wpdb->show_errors(true);
    $table = $wpdb->prefix . 'mailpoet_newsletters';

    try {
      ob_start();
      $this->guard->readOr(function () use ($table) {
        // A real failing query, so wpdb genuinely tries to print.
        return $this->entityManager->getConnection()
          ->executeQuery("SELECT column_that_does_not_exist FROM {$table}")
          ->fetchAllAssociative();
      }, []);
      $printed = (string)ob_get_clean();

      verify($printed)->equals('');
    } finally {
      $wpdb->show_errors($showErrors);
    }
  }

  /** A genuine fault must still surface rather than being turned into a fallback. */
  public function testItDoesNotSwallowOtherExceptions() {
    $this->expectException(\RuntimeException::class);
    $this->guard->readOr(function () {
      throw new \RuntimeException('something else');
    }, []);
  }

  /** Suppression is scoped to the read, so nothing after it loses its errors. */
  public function testItRestoresThePreviousSuppressionSetting() {
    global $wpdb;
    $before = $this->currentSuppression();

    $wpdb->suppress_errors(false);
    $this->guard->readOr(function () {
      throw $this->makeInvalidFieldNameException();
    }, []);
    verify($this->currentSuppression())->false();

    $wpdb->suppress_errors(true);
    $this->guard->readOr(function () {
      return null;
    }, null);
    verify($this->currentSuppression())->true();

    $wpdb->suppress_errors($before);
  }

  /** Reads the flag without touching the snake_case property directly. */
  private function currentSuppression(): bool {
    global $wpdb;
    $value = (bool)$wpdb->suppress_errors(false);
    $wpdb->suppress_errors($value);
    return $value;
  }

  private function makeInvalidFieldNameException(): InvalidFieldNameException {
    $reflection = new \ReflectionClass(InvalidFieldNameException::class);
    $exception = $reflection->newInstanceWithoutConstructor();
    $this->assertInstanceOf(InvalidFieldNameException::class, $exception);
    return $exception;
  }

  private function makeTableNotFoundException(): TableNotFoundException {
    $reflection = new \ReflectionClass(TableNotFoundException::class);
    $exception = $reflection->newInstanceWithoutConstructor();
    $this->assertInstanceOf(TableNotFoundException::class, $exception);
    return $exception;
  }
}
