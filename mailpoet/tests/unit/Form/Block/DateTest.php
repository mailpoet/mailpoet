<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Date;
use MailPoet\Form\BlockStylesRenderer;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class DateTest extends \MailPoetUnitTest {
  /** @var Date */
  private $date;

  /** @var MockObject & BlockRendererHelper */
  private $baseMock;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

  /** @var MockObject & BlockStylesRenderer */
  private $blockStylesRenderer;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'type' => 'date',
    'name' => 'Custom date',
    'id' => '1',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'Date label',
      'date_format' => 'MM/YYYY',
      'date_type' => 'year_month',
      'is_default_today' => '1',
      'required' => '',
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->blockStylesRenderer = $this->createMock(BlockStylesRenderer::class);
    $this->blockStylesRenderer->method('renderForSelect')->willReturn('');
    $this->baseMock = $this->createMock(BlockRendererHelper::class);
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $wpMock = $this->createMock(WPFunctions::class);
    $wpMock->method('escAttr')->will($this->returnArgument(0));
    $this->date = new Date($this->baseMock, $this->blockStylesRenderer, $this->wrapperMock, $wpMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderDateInput() {
    $this->baseMock->expects($this->once())->method('renderLabel')->willReturn('<label></label>');
    $this->baseMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->baseMock->expects($this->any())->method('getInputValidation')->willReturn(' validation="1" ');

    $html = $this->date->render($this->block, []);
    $mothsSelect = $this->htmlParser->getElementByXpath($html, "//select", 0);
    $yearsSelect = $this->htmlParser->getElementByXpath($html, "//select", 1);
    expect($mothsSelect->childNodes->length)->equals(13); // Months + placeholder
    expect($yearsSelect->childNodes->length)->equals(101 + 1); // Years + placeholder

    $date = Carbon::now();
    $currentMonth = $date->format('F');
    $currentYear = $date->format('Y');

    $selectedMonth = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 0);
    expect($selectedMonth->textContent)->equals($currentMonth);
    $selectedYear = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 1);
    expect($selectedYear->textContent)->equals($currentYear);
  }

  public function testItShouldRenderYearMonthDayDateFormat() {
    $this->baseMock->expects($this->once())->method('renderLabel')->willReturn('<label></label>');
    $this->baseMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->baseMock->expects($this->any())->method('getInputValidation')->willReturn(' validation="1" ');

    $block = $this->block;
    $block['params']['date_type'] = 'year_month_day';
    $block['params']['date_format'] = 'MM/DD/YYYY';

    $html = $this->date->render($block, []);
    $mothsSelect = $this->htmlParser->getElementByXpath($html, "//select", 0);
    $daysSelect = $this->htmlParser->getElementByXpath($html, "//select", 1);
    $yearsSelect = $this->htmlParser->getElementByXpath($html, "//select", 2);
    expect($mothsSelect->childNodes->length)->equals(13); // Months + placeholder
    expect($daysSelect->childNodes->length)->equals(32); // Days + placeholder
    expect($yearsSelect->childNodes->length)->equals(101 + 1); // Years + placeholder

    $date = Carbon::now();
    $currentMonth = $date->format('F');
    $currentYear = $date->format('Y');
    $currentDay = $date->format('j');

    $selectedMonth = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 0);
    expect($selectedMonth->textContent)->equals($currentMonth);
    $selectedDay = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 1);
    expect($selectedDay->textContent)->equals($currentDay);
    $selectedYear = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 2);
    expect($selectedYear->textContent)->equals($currentYear);
  }

  public function testItShouldAddValue() {
    $this->baseMock->expects($this->once())->method('renderLabel')->willReturn('<label></label>');
    $this->baseMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->baseMock->expects($this->any())->method('getInputValidation')->willReturn(' validation="1" ');

    $block = $this->block;
    $block['params']['date_type'] = 'year_month_day';
    $block['params']['date_format'] = 'MM/DD/YYYY';
    $block['params']['is_default_today'] = '';
    $block['params']['value'] = '2009-02-09 00:00:00';

    $html = $this->date->render($block, []);

    $selectedMonth = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 0);
    expect($selectedMonth->textContent)->equals('February');
    $selectedDay = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 1);
    expect($selectedDay->textContent)->equals('9');
    $selectedYear = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 2);
    expect($selectedYear->textContent)->equals('2009');
  }

  public function testItShouldRenderErrorContainerWithFormId() {
    $this->baseMock->expects($this->once())->method('renderLabel')->willReturn('<label></label>');
    $this->baseMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->baseMock->expects($this->any())->method('getInputValidation')->willReturn(' validation="1" ');

    $html = $this->date->render($this->block, [], 44);

    $errorContainer = $this->htmlParser->getElementByXpath($html, "//span[@class='mailpoet_error_1_44']");
    expect($errorContainer)->notEmpty();
    expect($errorContainer->nodeName)->equals('span');
  }
}
