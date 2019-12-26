<?php

namespace MailPoet\Test\Mailer\Methods\ErrorMappers;

use MailPoet\Mailer\MailerError;
use MailPoet\Mailer\Methods\ErrorMappers\AmazonSESMapper;
use SimpleXMLElement;

class AmazonSESMapperTest extends \MailPoetUnitTest {

  /** @var AmazonSESMapper*/
  private $mapper;

  /** @var array */
  private $response_data = [];

  public function _before() {
    parent::_before();
    $this->mapper = new AmazonSESMapper();
    $this->response_data = [
      'Error' => [
        'Type' => 'Sender',
        'Code' => 'ConfigurationSetDoesNotExist',
        'Message' => 'Some message',
      ],
      'RequestId' => '01ca93ec-b5a3-11e8-bff8-49dd5ddf8019',
    ];
  }

  public function testGetProperError() {
    $response = $this->buildXmlResponseFromArray($this->response_data, new SimpleXMLElement('<response/>'));
    $error = $this->mapper->getErrorFromResponse($response, 'john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_HARD);
    expect($error->getMessage())->equals('Some message');
    expect($error->getSubscriberErrors()[0]->getEmail())->equals('john@rambo.com');
  }

  public function testGetSoftErrorForRejectedMessage() {
    $this->response_data['Error']['Code'] = 'MessageRejected';
    $response = $this->buildXmlResponseFromArray($this->response_data, new SimpleXMLElement('<response/>'));
    $error = $this->mapper->getErrorFromResponse($response, 'john@rambo.com');
    expect($error->getLevel())->equals(MailerError::LEVEL_SOFT);
  }

  /**
   * @return SimpleXMLElement
   */
  private function buildXmlResponseFromArray($response_data, SimpleXMLElement $xml) {
    foreach ($response_data as $tag => $value) {
      if (is_array($value)) {
        $this->buildXmlResponseFromArray($value, $xml->addChild($tag));
      } else {
        $xml->addChild($tag, $value);
      }
    }
    return $xml;
  }
}
