<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON;

use Codeception\Stub;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\WP\Functions as WPFunctions;

class ErrorResponseTest extends \MailPoetUnitTest {
  public function testItSanitizesSqlErrorsWhenReturningResponse() {
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
    $errors = [
      'valid error',
      'SQLSTATE[22001]: Some SQL error',
      'another valid error',
    ];
    $errorResponse = new ErrorResponse($errors);
    expect($errorResponse->getData())->equals(
      [
        'errors' => [
          [
            'error' => 0,
            'message' => 'valid error',
          ],
          [
            'error' => 1,
            'message' => 'An unknown error occurred.',
          ],
          [
            'error' => 2,
            'message' => 'another valid error',
          ],
        ],
      ]
    );
  }

  public function _after() {
    parent::_after();
    WPFunctions::set(new WPFunctions);
  }
}
