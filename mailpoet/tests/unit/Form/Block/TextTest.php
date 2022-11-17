<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Text;
use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class TextTest extends \MailPoetUnitTest {
  /** @var Text */
  private $text;

  /** @var MockObject & BlockRendererHelper */
  private $rendererHelperMock;

  /** @var MockObject & BlockStylesRenderer */
  private $stylesRendererMock;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

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
    $this->stylesRendererMock = $this->createMock(BlockStylesRenderer::class);
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->text = new Text($this->rendererHelperMock, $this->stylesRendererMock, $this->wrapperMock, $wpMock);
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
    $this->stylesRendererMock->expects($this->once())->method('renderForTextInput')->willReturn('border-radius: 10px;');

    $html = $this->text->render($this->block, []);
    $input = $this->htmlParser->getElementByXpath($html, '//input');
    $name = $this->htmlParser->getAttribute($input, 'name');
    $type = $this->htmlParser->getAttribute($input, 'type');
    $validation = $this->htmlParser->getAttribute($input, 'validation');
    $value = $this->htmlParser->getAttribute($input, 'value');
    $modifiers = $this->htmlParser->getAttribute($input, 'modifiers');
    $class = $this->htmlParser->getAttribute($input, 'class');
    $style = $this->htmlParser->getAttribute($input, 'style');
    expect($name->value)->equals('data[Field name]');
    expect($type->value)->equals('text');
    expect($validation->value)->equals('1');
    expect($value->value)->equals('val');
    expect($modifiers->value)->equals('mod');
    expect($class->value)->equals('mailpoet_text');
    expect($style->value)->equals('border-radius: 10px;');
  }
}
