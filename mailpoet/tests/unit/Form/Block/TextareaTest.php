<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Textarea;
use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class TextareaTest extends \MailPoetUnitTest {
  /** @var Textarea */
  private $textarea;

  /** @var MockObject & BlockRendererHelper */
  private $rendererHelperMock;

  /** @var MockObject & BlockStylesRenderer */
  private $stylesRendererMock;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

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
    $this->rendererHelperMock = $this->createMock(BlockRendererHelper::class);
    $this->stylesRendererMock = $this->createMock(BlockStylesRenderer::class);
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->textarea = new Textarea($this->rendererHelperMock, $this->stylesRendererMock, $this->wrapperMock, $wpMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderTextarea() {
    $this->rendererHelperMock->expects($this->once())->method('renderLabel')->willReturn('<label></label>');
    $this->rendererHelperMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->rendererHelperMock->expects($this->once())->method('renderInputPlaceholder')->willReturn('');
    $this->rendererHelperMock->expects($this->once())->method('getInputValidation')->willReturn(' validation="1" ');
    $this->rendererHelperMock->expects($this->once())->method('getInputModifiers')->willReturn(' modifiers="mod" ');
    $this->rendererHelperMock->expects($this->once())->method('getFieldValue')->willReturn('val');
    $this->stylesRendererMock->expects($this->once())->method('renderForTextInput')->willReturn('border-radius: 10px;');

    $html = $this->textarea->render($this->block, []);
    $textarea = $this->htmlParser->getElementByXpath($html, '//textarea');
    $name = $this->htmlParser->getAttribute($textarea, 'name');
    $validation = $this->htmlParser->getAttribute($textarea, 'validation');
    $modifiers = $this->htmlParser->getAttribute($textarea, 'modifiers');
    $class = $this->htmlParser->getAttribute($textarea, 'class');
    $style = $this->htmlParser->getAttribute($textarea, 'style');
    expect($textarea->textContent)->equals('val');
    expect($name->value)->equals('data[Field name]');
    expect($validation->value)->equals('1');
    expect($class->value)->equals('mailpoet_textarea');
    expect($modifiers->value)->equals('mod');
    expect($style->value)->equals('border-radius: 10px;');
  }
}
