<?php declare(strict_types = 1);

namespace MailPoet\Test\Form\Block;

use MailPoet\Entities\SegmentEntity;
use MailPoet\Form\Block\BlockRendererHelper;
use MailPoet\Form\Block\Segment;
use MailPoet\Form\BlockWrapperRenderer;
use MailPoet\Segments\SegmentsRepository;
use MailPoet\Test\Form\HtmlParser;
use MailPoet\WP\Functions as WPFunctions;
use PHPUnit\Framework\MockObject\MockObject;

require_once __DIR__ . '/../HtmlParser.php';

class SegmentTest extends \MailPoetUnitTest {
  /** @var Segment */
  private $segment;

  /** @var MockObject & WPFunctions */
  private $wpMock;

  /** @var MockObject & BlockRendererHelper */
  private $rendererHelperMock;

  /** @var MockObject & BlockWrapperRenderer */
  private $wrapperMock;

  /** @var MockObject & SegmentsRepository */
  private $segmentsRepositoryMock;

  /** @var HtmlParser */
  private $htmlParser;

  private $block = [
    'type' => 'segment',
    'name' => 'Segments',
    'id' => 'segment',
    'unique' => '1',
    'static' => '0',
    'params' => [
      'label' => 'Select lists',
      'values' => [[
        'name' => 'Old ignored value',
        'id' => '1',
        'is_checked' => '1',
      ], [
        'id' => '2',
      ]],
    ],
    'position' => '1',
  ];

  public function _before() {
    parent::_before();
    $this->wpMock = $this->createMock(WPFunctions::class);
    $this->wpMock->method('escAttr')->willReturnCallback(function($value) {
      return htmlspecialchars((string)$value, ENT_QUOTES);
    });
    $this->wpMock->method('escHtml')->willReturnCallback(function($value) {
      return htmlspecialchars((string)$value, ENT_QUOTES);
    });
    $uniqueId = 0;
    $this->wpMock->method('wpUniqueId')->willReturnCallback(function($prefix) use (&$uniqueId) {
      $uniqueId++;
      return $prefix . $uniqueId;
    });
    $this->wrapperMock = $this->createMock(BlockWrapperRenderer::class);
    $this->wrapperMock->method('render')->will($this->returnArgument(1));
    $this->rendererHelperMock = $this->createMock(BlockRendererHelper::class);
    $this->segmentsRepositoryMock = $this->createMock(SegmentsRepository::class);
    $this->segment = new Segment($this->rendererHelperMock, $this->wrapperMock, $this->wpMock, $this->segmentsRepositoryMock);
    $this->htmlParser = new HtmlParser();
  }

  public function testItShouldRenderSegments() {
    $this->rendererHelperMock->expects($this->once())->method('renderLegend')->willReturn('<legend></legend>');
    $this->rendererHelperMock->expects($this->once())->method('getInputValidation')->willReturn('validation="1"');
    $this->rendererHelperMock->expects($this->once())->method('getFieldName')->willReturn('Segments');
    $this->segmentsRepositoryMock->expects($this->once())->method('findByIds')->willReturn([
      $this->createSegmentMock(1, 'List 1'),
      $this->createSegmentMock(2, 'List 2'),
    ]);

    $html = $this->segment->render($this->block, []);

    $checkbox1 = $this->htmlParser->getElementByXpath($html, "//label[@class='mailpoet_checkbox_label']", 0);
    $checkbox2 = $this->htmlParser->getElementByXpath($html, "//label[@class='mailpoet_checkbox_label']", 1);
    verify($checkbox1->textContent)->equals(' List 1');
    verify($checkbox2->textContent)->equals(' List 2');

    $checkbox1Input = $this->htmlParser->getChildElement($checkbox1, 'input');
    $checkbox2Input = $this->htmlParser->getChildElement($checkbox2, 'input');
    verify($this->htmlParser->getAttribute($checkbox1Input, 'value')->value)->equals(1);
    verify($this->htmlParser->getAttribute($checkbox2Input, 'value')->value)->equals(2);
    verify($this->htmlParser->getAttribute($checkbox1Input, 'name')->value)->equals('data[Segments][]');
    verify($this->htmlParser->getAttribute($checkbox2Input, 'name')->value)->equals('data[Segments][]');
    verify($this->htmlParser->getAttribute($checkbox1Input, 'checked')->value)->equals('checked');
  }

  public function testItShouldRenderErrorContainer(): void {
    $this->rendererHelperMock->expects($this->once())->method('renderLegend')->willReturn('<legend></legend>');
    $this->rendererHelperMock->expects($this->once())->method('getFieldName')->willReturn('Segments');
    $this->segmentsRepositoryMock->expects($this->once())->method('findByIds')->willReturn([
      $this->createSegmentMock(1, 'List 1'),
      $this->createSegmentMock(2, 'List 2'),
    ]);
    $this->rendererHelperMock->expects($this->once())->method('renderErrorsContainer')->willReturn('<span class="mailpoet_error_segment_1"></span>');

    $html = $this->segment->render($this->block, []);

    $errorContainer = $this->htmlParser->getElementByXpath($html, "//span[@class='mailpoet_error_segment_1']");
    verify($errorContainer)->notEmpty();
    verify($errorContainer->nodeName)->equals('span');
  }

  public function testItRendersManageSubscriptionChoices(): void {
    $block = $this->block;
    $block['id'] = 'segments';
    $block['params']['display_mode'] = 'manage_subscription_choices';
    $block['params']['description'] = 'Choose lists';
    $block['params']['values'] = [[
      'id' => '12',
      'name' => 'List <one>',
      'public_description' => 'Public <description>',
      'is_checked' => true,
    ], [
      'id' => '34',
      'name' => 'List two',
      'public_description' => '',
      'is_checked' => false,
    ]];

    $this->rendererHelperMock->expects($this->once())->method('renderLegend')->willReturn('<legend class="mailpoet_segment_label">Your lists</legend>');
    $this->rendererHelperMock->expects($this->once())->method('renderErrorsContainer')->willReturn('');
    $this->segmentsRepositoryMock->expects($this->never())->method('findByIds');

    $html = $this->segment->render($block, []);

    $section = $this->htmlParser->getElementByXpath($html, "//fieldset[@data-automation-id='manage_subscription_lists']");
    verify($section)->notEmpty();

    $row = $this->htmlParser->getElementByXpath($html, "//div[@data-automation-id='manage_subscription_list_12']");
    verify($row->textContent)->stringContainsString('List <one>');
    verify($row->textContent)->stringContainsString('Public <description>');

    $yesInput = $this->htmlParser->getElementByXpath($html, "//input[@data-automation-id='manage_subscription_list_12_yes']");
    $noInput = $this->htmlParser->getElementByXpath($html, "//input[@data-automation-id='manage_subscription_list_12_no']");
    verify($this->htmlParser->getAttribute($yesInput, 'type')->value)->equals('radio');
    verify($this->htmlParser->getAttribute($yesInput, 'name')->value)->equals('data[segment_choices][12]');
    verify($this->htmlParser->getAttribute($yesInput, 'value')->value)->equals('subscribed');
    verify($this->htmlParser->getAttribute($yesInput, 'checked')->value)->equals('checked');
    verify($this->htmlParser->getAttribute($noInput, 'value')->value)->equals('unsubscribed');

    $secondNoInput = $this->htmlParser->getElementByXpath($html, "//input[@data-automation-id='manage_subscription_list_34_no']");
    verify($this->htmlParser->getAttribute($secondNoInput, 'checked')->value)->equals('checked');
    verify($html)->stringContainsString('List &lt;one&gt;');
    verify($html)->stringContainsString('Public &lt;description&gt;');
  }

  public function testItOmitsManageSubscriptionChoicesWithoutOptions(): void {
    $block = $this->block;
    $block['params']['display_mode'] = 'manage_subscription_choices';
    $block['params']['values'] = [];

    $this->rendererHelperMock->expects($this->never())->method('renderLegend');
    $this->segmentsRepositoryMock->expects($this->never())->method('findByIds');

    verify($this->segment->render($block, []))->equals('');
  }

  /**
   * @return MockObject & SegmentEntity
   */
  private function createSegmentMock(int $id, string $name) {
    $mock = $this->createMock(SegmentEntity::class);
    $mock->method('getId')->willReturn($id);
    $mock->method('getName')->willReturn($name);
    return $mock;
  }
}
