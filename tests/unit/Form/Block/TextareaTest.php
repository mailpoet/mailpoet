<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Base;
use MailPoet\Form\Block\Textarea;
use MailPoet\Test\Form\HtmlParser;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class TextareaTest extends \MailPoetUnitTest {
  /** @var Textarea */
  private $textarea;

  /** @var MockObject|Base */
  private $baseMock;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'type' => 'textarea',
    'name' => 'Custom textarea',
    'id' => '1',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'Input label',
      'required' => '',
      'hide_label' => '',
      'lines' => '4',
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->baseMock = $this->createMock(Base::class);
    $this->textarea = new Textarea($this->baseMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderTextarea() {
    $this->baseMock->expects($this->once())->method('renderLabel')->willReturn('<label></label>');
    $this->baseMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->baseMock->expects($this->once())->method('renderInputPlaceholder')->willReturn('');
    $this->baseMock->expects($this->once())->method('getInputValidation')->willReturn(' validation="1" ');
    $this->baseMock->expects($this->once())->method('getInputModifiers')->willReturn(' modifiers="mod" ');
    $this->baseMock->expects($this->once())->method('getFieldValue')->willReturn('val');

    $html = $this->textarea->render($this->block);
    $textarea = $this->htmlParser->getElementByXpath($html, '//textarea');
    $name = $this->htmlParser->getAttribute($textarea, 'name');
    $validation = $this->htmlParser->getAttribute($textarea, 'validation');
    $modifiers = $this->htmlParser->getAttribute($textarea, 'modifiers');
    $class = $this->htmlParser->getAttribute($textarea, 'class');
    expect($textarea->textContent)->equals('val');
    expect($name->value)->equals('data[Field name]');
    expect($validation->value)->equals('1');
    expect($class->value)->equals('mailpoet_textarea');
    expect($modifiers->value)->equals('mod');
  }
}
