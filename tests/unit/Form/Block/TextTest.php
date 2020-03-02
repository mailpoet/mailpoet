<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Text;
use MailPoet\Test\Form\HtmlParser;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class TextTest extends \MailPoetUnitTest {
  /** @var Text */
  private $text;

  /** @var MockObject & BlockRendererHelper */
  private $rendererHelperMock;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'type' => 'text',
    'name' => 'Custom text',
    'id' => '1',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'Input label',
      'required' => '',
      'hide_label' => '',
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->rendererHelperMock = $this->createMock(BlockRendererHelper::class);
    $this->text = new Text($this->rendererHelperMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderTextInput() {
    $this->rendererHelperMock->expects($this->once())->method('renderLabel')->willReturn('<label></label>');
    $this->rendererHelperMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->rendererHelperMock->expects($this->once())->method('getFieldLabel')->willReturn('Input label');
    $this->rendererHelperMock->expects($this->once())->method('getInputValidation')->willReturn(' validation="1" ');
    $this->rendererHelperMock->expects($this->once())->method('getFieldValue')->willReturn('val');
    $this->rendererHelperMock->expects($this->once())->method('renderInputPlaceholder')->willReturn('');
    $this->rendererHelperMock->expects($this->once())->method('getInputModifiers')->willReturn(' modifiers="mod" ');

    $html = $this->text->render($this->block, []);
    $input = $this->htmlParser->getElementByXpath($html, '//input');
    $name = $this->htmlParser->getAttribute($input, 'name');
    $type = $this->htmlParser->getAttribute($input, 'type');
    $validation = $this->htmlParser->getAttribute($input, 'validation');
    $value = $this->htmlParser->getAttribute($input, 'value');
    $modifiers = $this->htmlParser->getAttribute($input, 'modifiers');
    $class = $this->htmlParser->getAttribute($input, 'class');
    expect($name->value)->equals('data[Field name]');
    expect($type->value)->equals('text');
    expect($validation->value)->equals('1');
    expect($value->value)->equals('val');
    expect($modifiers->value)->equals('mod');
    expect($class->value)->equals('mailpoet_text');
  }
}
