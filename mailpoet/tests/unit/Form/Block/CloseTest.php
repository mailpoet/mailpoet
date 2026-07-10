<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Close;
use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class CloseTest extends \MailPoetUnitTest {
  /** @var Close */
  private $close;

  /** @var MockObject & BlockRendererHelper */
  private $rendererHelperMock;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

  /** @var MockObject & BlockStylesRenderer */
  private $stylesRendererMock;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'type' => 'close',
    'name' => 'Close',
    'id' => 'close',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'No thanks',
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
    $this->close = new Close($this->rendererHelperMock, $this->wrapperMock, $this->stylesRendererMock, $wpMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderCloseButton() {
    $this->rendererHelperMock->expects($this->once())->method('getFieldLabel')->willReturn('No thanks');
    $this->stylesRendererMock->expects($this->once())->method('renderForButton')->willReturn('border-radius: 10px;');
    $html = $this->close->render($this->block, []);
    $button = $this->htmlParser->getElementByXpath($html, '//button');
    $type = $this->htmlParser->getAttribute($button, 'type');
    $class = $this->htmlParser->getAttribute($button, 'class');
    $style = $this->htmlParser->getAttribute($button, 'style');
    verify($type->value)->equals('button');
    verify($class->value)->stringContainsString('mailpoet_form_close');
    verify($style->value)->equals('border-radius: 10px;');
    verify($button->textContent)->equals('No thanks');
  }
}
