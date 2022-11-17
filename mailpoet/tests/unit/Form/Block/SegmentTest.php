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
    $this->wpMock->method('escAttr')->will($this->returnArgument(0));
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
    $this->segmentsRepositoryMock->expects($this->once())->method('findBy')->willReturn([
      $this->createSegmentMock(1, 'List 1'),
      $this->createSegmentMock(2, 'List 2'),
    ]);

    $html = $this->segment->render($this->block, []);

    $checkbox1 = $this->htmlParser->getElementByXpath($html, "//label[@class='mailpoet_checkbox_label']", 0);
    $checkbox2 = $this->htmlParser->getElementByXpath($html, "//label[@class='mailpoet_checkbox_label']", 1);
    expect($checkbox1->textContent)->equals(' List 1');
    expect($checkbox2->textContent)->equals(' List 2');

    $checkbox1Input = $this->htmlParser->getChildElement($checkbox1, 'input');
    $checkbox2Input = $this->htmlParser->getChildElement($checkbox2, 'input');
    expect($this->htmlParser->getAttribute($checkbox1Input, 'value')->value)->equals(1);
    expect($this->htmlParser->getAttribute($checkbox2Input, 'value')->value)->equals(2);
    expect($this->htmlParser->getAttribute($checkbox1Input, 'name')->value)->equals('data[Segments][]');
    expect($this->htmlParser->getAttribute($checkbox2Input, 'name')->value)->equals('data[Segments][]');
    expect($this->htmlParser->getAttribute($checkbox1Input, 'checked')->value)->equals('checked');
  }

  public function testItShouldRenderErrorContainerWithFormId(): void {
    $this->rendererHelperMock->expects($this->once())->method('renderLegend')->willReturn('<legend></legend>');
    $this->rendererHelperMock->expects($this->once())->method('getInputValidation')->willReturn('validation="1"');
    $this->rendererHelperMock->expects($this->once())->method('getFieldName')->willReturn('Segments');
    $this->segmentsRepositoryMock->expects($this->once())->method('findBy')->willReturn([
      $this->createSegmentMock(1, 'List 1'),
      $this->createSegmentMock(2, 'List 2'),
    ]);

    $html = $this->segment->render($this->block, [], 1);

    $errorContainer = $this->htmlParser->getElementByXpath($html, "//span[@class='mailpoet_error_segment_1']");
    expect($errorContainer)->notEmpty();
    expect($errorContainer->nodeName)->equals('span');
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
