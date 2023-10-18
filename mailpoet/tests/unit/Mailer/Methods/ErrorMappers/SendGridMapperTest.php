<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\SendGridMapper;

class SendGridMapperTest extends \MailPoetUnitTest {

  /** @var SendGridMapper*/
  private $mapper;

  /** @var array */
  private $response = [];

  public function _before() {
    parent::_before();
    $this->mapper = new SendGridMapper();
    $this->response = [
      'errors' => [
        'Some message',
      ],
    ];
  }

  public function testGetProperError() {
    $error = $this->mapper->getErrorFromResponse($this->response, 'john@rambo.com');
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->equals('Some message');
    verify($error->getSubscriberErrors()[0]->getEmail())->equals('john@rambo.com');
  }

  public function testGetSoftErrorForInvalidEmail() {
    $this->response['errors'][0] = 'Invalid email address ,,@';
    $error = $this->mapper->getErrorFromResponse($this->response, ',,@');
    verify($error->getLevel())->equals(MailerError::LEVEL_SOFT);
  }
}
