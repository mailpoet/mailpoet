<?php declare(strict_types = 1);

namespace MailPoet\API\JSON;

use MailPoet\AccessDeniedException;
use MailPoet\ConflictException;
use MailPoet\Exception;
use MailPoet\InvalidStateException;
use MailPoet\NotFoundException;
use MailPoet\RuntimeException;
use MailPoet\UnexpectedValueException;
use MailPoet\WP\Functions as WPFunctions;

class ErrorHandlerTest extends \MailPoetUnitTest {
  public function testItCovertsToBadRequest() {
    $this->runErrorHandlerTest(new UnexpectedValueException(), 400);
  }

  public function testItCovertsToForbidden() {
    $this->runErrorHandlerTest(new AccessDeniedException(), 403);
  }

  public function testItCovertsToNotFound() {
    $this->runErrorHandlerTest(new NotFoundException(), 404);
  }

  public function testItCovertsToConflict() {
    $this->runErrorHandlerTest(new ConflictException(), 409);
  }

  public function testItCovertsToServerError() {
    $this->runErrorHandlerTest(new RuntimeException(), 500);
    $this->runErrorHandlerTest(new InvalidStateException(), 500);
  }

  private function runErrorHandlerTest(Exception $exception, int $expectedCode) {
    $errorHandler = new ErrorHandler();
    $response = $errorHandler->convertToResponse($exception->withErrors([
      'key' => 'value',
    ]));

    expect($response)->isInstanceOf(ErrorResponse::class);
    expect($response->status)->equals($expectedCode);
    expect($response->errors)->equals([['error' => 'key', 'message' => 'value']]);
  }
}
