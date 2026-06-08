<?php declare(strict_types = 1);

namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\AmazonSESMapper;
use SimpleXMLElement;

class AmazonSESMapperTest extends \MailPoetUnitTest {

  /** @var AmazonSESMapper*/
  private $mapper;

  /** @var array */
  private $responseData = [];

  public function _before() {
    parent::_before();
    $this->mapper = new AmazonSESMapper();
    $this->responseData = [
      'Error' => [
        'Type' => 'Sender',
        'Code' => 'ConfigurationSetDoesNotExist',
        'Message' => 'Some message',
      ],
      'RequestId' => '01ca93ec-b5a3-11e8-bff8-49dd5ddf8019',
    ];
  }

  public function testGetProperError() {
    $response = $this->buildXmlResponseFromArray($this->responseData, new SimpleXMLElement('<response/>'));
    $error = $this->mapper->getErrorFromResponse($response, 'john@rambo.com');
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
    verify($error->getMessage())->equals('Some message');
    verify($error->getSubscriberErrors()[0]->getEmail())->equals('john@rambo.com');
  }

  public function testGetSoftErrorForRejectedMessage() {
    $this->responseData['Error']['Code'] = 'MessageRejected';
    $response = $this->buildXmlResponseFromArray($this->responseData, new SimpleXMLElement('<response/>'));
    $error = $this->mapper->getErrorFromResponse($response, 'john@rambo.com');
    verify($error->getLevel())->equals(MailerError::LEVEL_SOFT);
  }

  public function testGetUnknownErrorWhenResponseMessageIsMissing() {
    unset($this->responseData['Error']['Message']);
    $response = $this->buildXmlResponseFromArray($this->responseData, new SimpleXMLElement('<response/>'));
    $error = $this->mapper->getErrorFromResponse($response, 'john@rambo.com');
    verify($error->getMessage())->equals('AmazonSES has returned an unknown error.');
  }

  public function testGetUnknownErrorWhenResponseErrorIsMissing() {
    $error = $this->mapper->getErrorFromResponse(new SimpleXMLElement('<response/>'), 'john@rambo.com');
    verify($error->getMessage())->equals('AmazonSES has returned an unknown error.');
  }

  public function testGetHardErrorWhenResponseCodeIsMissing() {
    unset($this->responseData['Error']['Code']);
    $response = $this->buildXmlResponseFromArray($this->responseData, new SimpleXMLElement('<response/>'));
    $error = $this->mapper->getErrorFromResponse($response, 'john@rambo.com');
    verify($error->getLevel())->equals(MailerError::LEVEL_HARD);
  }

  /**
   * @return SimpleXMLElement
   */
  private function buildXmlResponseFromArray($responseData, SimpleXMLElement $xml) {
    foreach ($responseData as $tag => $value) {
      if (is_array($value)) {
        $this->buildXmlResponseFromArray($value, $xml->addChild($tag));
      } else {
        $xml->addChild($tag, $value);
      }
    }
    return $xml;
  }
}
