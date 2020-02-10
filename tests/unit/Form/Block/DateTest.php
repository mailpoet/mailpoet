<?php

namespace MailPoet\Test\Form\Block;

use MailPoet\Form\Block\Base;
use MailPoet\Form\Block\Date;
use MailPoet\Test\Form\HtmlParser;
use MailPoetVendor\Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class DateTest extends \MailPoetUnitTest {
  /** @var Date */
  private $date;

  /** @var MockObject|Base */
  private $baseMock;

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
    $this->baseMock = $this->createMock(Base::class);
    $this->date = new Date($this->baseMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderDateInput() {
    $this->baseMock->expects($this->once())->method('renderLabel')->willReturn('<label></label>');
    $this->baseMock->expects($this->once())->method('getFieldName')->willReturn('Field name');
    $this->baseMock->expects($this->any())->method('getInputValidation')->willReturn(' validation="1" ');

    $html = $this->date->render($this->block);
    $mothsSelect = $this->htmlParser->getElementByXpath($html, "//select", 0);
    $yearsSelect = $this->htmlParser->getElementByXpath($html, "//select", 1);
    expect(count($mothsSelect->childNodes))->equals(13); // Months + placeholder
    expect(count($yearsSelect->childNodes))->equals(101 + 1); // Years + placeholder

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

    $html = $this->date->render($block);
    $mothsSelect = $this->htmlParser->getElementByXpath($html, "//select", 0);
    $daysSelect = $this->htmlParser->getElementByXpath($html, "//select", 1);
    $yearsSelect = $this->htmlParser->getElementByXpath($html, "//select", 2);
    expect(count($mothsSelect->childNodes))->equals(13); // Months + placeholder
    expect(count($daysSelect->childNodes))->equals(32); // Days + placeholder
    expect(count($yearsSelect->childNodes))->equals(101 + 1); // Years + placeholder

    $date = Carbon::now();
    $currentMonth = $date->format('F');
    $currentYear = $date->format('Y');
    $currentDay = $date->format('d');

    $selectedMonth = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 0);
    expect($selectedMonth->textContent)->equals($currentMonth);
    $selectedDay = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 1);
    expect($selectedDay->textContent)->equals($currentDay);
    $selectedYear = $this->htmlParser->getElementByXpath($html, "//option[@selected='selected']", 2);
    expect($selectedYear->textContent)->equals($currentYear);
  }
}
