<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Submit;
use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class SubmitTest extends \MailPoetUnitTest {
  /** @var Submit */
  private $submit;

  /** @var MockObject & BlockRendererHelper */
  private $rendererHelperMock;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

  /** @var MockObject & BlockStylesRenderer */
  private $stylesRendererMock;

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
    $this->rendererHelperMock = $this->createMock(BlockRendererHelper::class);
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $this->stylesRendererMock = $this->createMock(BlockStylesRenderer::class);
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->submit = new Submit($this->rendererHelperMock, $this->wrapperMock, $this->stylesRendererMock, $wpMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderSubmit() {
    $this->rendererHelperMock->expects($this->once())->method('getFieldLabel')->willReturn('Submit label');
    $this->stylesRendererMock->expects($this->once())->method('renderForButton')->willReturn('border-radius: 10px;');
    $html = $this->submit->render($this->block, []);
    $input = $this->htmlParser->getElementByXpath($html, '//input');
    $type = $this->htmlParser->getAttribute($input, 'type');
    $value = $this->htmlParser->getAttribute($input, 'value');
    $style = $this->htmlParser->getAttribute($input, 'style');
    expect($type->value)->equals('submit');
    expect($value->value)->equals('Submit label');
    expect($style->value)->equals('border-radius: 10px;');
  }
}
