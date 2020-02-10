<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Base;
use MailPoet\Form\Block\Submit;
use MailPoet\Test\Form\HtmlParser;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class SubmitTest extends \MailPoetUnitTest {
  /** @var Submit */
  private $submit;

  /** @var MockObject|Base */
  private $baseMock;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'type' => 'submit',
    'name' => 'Submit',
    'id' => 'submit',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'Submit label',
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->baseMock = $this->createMock(Base::class);
    $this->submit = new Submit($this->baseMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderSubmit() {
    $this->baseMock->expects($this->once())->method('getFieldLabel')->willReturn('Submit label');
    $html = $this->submit->render($this->block);
    $input = $this->htmlParser->getElementByXpath($html, '//input');
    $type = $this->htmlParser->getAttribute($input, 'type');
    $value = $this->htmlParser->getAttribute($input, 'value');
    expect($type->value)->equals('submit');
    expect($value->value)->equals('Submit label');
  }
}
