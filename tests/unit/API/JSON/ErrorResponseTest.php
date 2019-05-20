<?php

namespace MailPoet\Test\API\JSON;

use Codeception\Stub;
use MailPoet\API\JSON\ErrorResponse;
use MailPoet\WP\Functions as WPFunctions;

class ErrorResponseTest extends \MailPoetUnitTest {
  function testItSanitizesSqlErrorsWhenReturningResponse() {
    WPFunctions::set(Stub::make(new WPFunctions, [
      '__' => function ($value) {
        return $value;
      },
    ]));
    $errors = array(
      'valid error',
      'SQLSTATE[22001]: Some SQL error',
      'another valid error'
    );
    $error_response = new ErrorResponse($errors);
    expect($error_response->getData())->equals(
      array(
        'errors' => array(
          array(
            'error' => 0,
            'message' => 'valid error'
          ),
          array(
            'error' => 1,
            'message' => 'An unknown error occurred.'
          ),
          array(
            'error' => 2,
            'message' => 'another valid error'
          )
        )
      )
    );
  }

  function _after() {
    WPFunctions::set(new WPFunctions);
  }
}