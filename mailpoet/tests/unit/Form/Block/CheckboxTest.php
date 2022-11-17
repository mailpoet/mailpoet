<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Checkbox;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class CheckboxTest extends \MailPoetUnitTest {
  /** @var Checkbox */
  private $checkbox;

  /** @var MockObject & BlockRendererHelper */
  private $rendererHelperMock;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'type' => 'checkbox',
    'name' => 'Custom checkbox',
    'id' => '1',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'Input label',
      'required' => '',
      'hide_label' => '',
      'values' => [[
        'value' => 'Checkbox label',
        'is_checked' => '1',
      ]],
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->rendererHelperMock = $this->createMock(BlockRendererHelper::class);
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $wpMock->method('escHtml')->will($this->returnArgument(0));
    $this->checkbox = new Checkbox($this->rendererHelperMock, $this->wrapperMock, $wpMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderCheckbox() {
    $this->rendererHelperMock->expects($this->once())->method('renderLegend')->willReturn('<legend></legend>');
    $this->rendererHelperMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->rendererHelperMock->expects($this->once())->method('getInputValidation')->willReturn('validation="1"');
    $this->rendererHelperMock->expects($this->once())->method('getFieldValue')->willReturn('1');
    $html = $this->checkbox->render($this->block, []);
    $checkboxLabel = $this->htmlParser->getElementByXpath($html, "//label[@class='mailpoet_checkbox_label']");
    expect($checkboxLabel->nodeValue)->equals(' Checkbox label');
    $checkbox = $this->htmlParser->getChildElement($checkboxLabel, 'input');
    $checked = $this->htmlParser->getAttribute($checkbox, 'checked');
    expect($checked->value)->equals('checked');
  }

  public function testItShouldRenderErrorContainerWithFormId() {
    $this->rendererHelperMock->expects($this->once())->method('renderLegend')->willReturn('<legend></legend>');
    $this->rendererHelperMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->rendererHelperMock->expects($this->once())->method('getInputValidation')->willReturn('validation="1"');
    $this->rendererHelperMock->expects($this->once())->method('getFieldValue')->willReturn('1');

    $html = $this->checkbox->render($this->block, [], 213);

    $errorContainer = $this->htmlParser->getElementByXpath($html, "//span[@class='mailpoet_error_1_213']");
    expect($errorContainer)->notEmpty();
    expect($errorContainer->nodeName)->equals('span');
  }
}
