<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;

class TopLevelPreprocessorTest extends \MailPoetUnitTest {

  private $paragraphBlock = [
    'blockName' => 'core/paragraph',
    'attrs' => [],
    'innerHTML' => 'Paragraph content',
  ];

  private $headingBlock = [
    'blockName' => 'core/heading',
    'attrs' => [],
    'innerHTML' => 'Paragraph content',
  ];

  private $columnsBlock = [
    'blockName' => 'core/columns',
    'attrs' => [],
    'innerBlocks' => [[
      'blockName' => 'core/column',
      'attrs' => [],
      'innerBlocks' => [],
    ]],
  ];

  /** @var TopLevelPreprocessor */
  private $preprocessor;

  /** @var array{contentSize: string} */
  private array $layout;

  /** @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles */
  private array $styles;

  public function _before() {
    parent::_before();
    $this->preprocessor = new TopLevelPreprocessor();
    $this->layout = ['contentSize' => '660px'];
    $this->styles = ['spacing' => ['padding' => ['left' => '10px', 'right' => '10px', 'top' => '10px', 'bottom' => '10px'], 'blockGap' => '10px']];
  }

  public function testItWrapsSingleTopLevelBlockIntoColumns() {
    $parsedDocument = [$this->paragraphBlock];
    $result = $this->preprocessor->preprocess($parsedDocument, $this->layout, $this->styles);
    verify($result[0]['blockName'])->equals('core/columns');
    verify($result[0]['innerBlocks'][0]['blockName'])->equals('core/column');
    verify($result[0]['innerBlocks'][0]['innerBlocks'][0]['blockName'])->equals('core/paragraph');
    verify($result[0]['innerBlocks'][0]['innerBlocks'][0]['innerHTML'])->equals('Paragraph content');
  }

  public function testItDoesntWrapColumns() {
    $parsedDocumentWithMultipleColumns = [$this->columnsBlock, $this->columnsBlock];
    $result = $this->preprocessor->preprocess($parsedDocumentWithMultipleColumns, $this->layout, $this->styles);
    verify($result)->equals($parsedDocumentWithMultipleColumns);
  }

  public function testItWrapsTopLevelBlocksSpreadBetweenColumns() {
    $parsedDocument = [$this->paragraphBlock, $this->columnsBlock, $this->paragraphBlock, $this->paragraphBlock];
    // We expect to wrap top level paragraph blocks into columns so the result should three columns blocks
    $result = $this->preprocessor->preprocess($parsedDocument, $this->layout, $this->styles);
    verify($result)->arrayCount(3);
    // First columns contain columns with one paragraph block
    verify($result[0]['innerBlocks'][0]['blockName'])->equals('core/column');
    verify($result[0]['innerBlocks'][0]['innerBlocks'][0]['blockName'])->equals('core/paragraph');
    // Second columns remains empty
    verify($result[1]['innerBlocks'][0]['blockName'])->equals('core/column');
    verify($result[1]['innerBlocks'][0]['innerBlocks'])->empty();
    // Third columns contain columns with two paragraph blocks
    verify($result[2]['innerBlocks'][0]['blockName'])->equals('core/column');
    verify($result[2]['innerBlocks'][0]['innerBlocks'])->arrayCount(2);
    verify($result[2]['innerBlocks'][0]['innerBlocks'][0]['blockName'])->equals('core/paragraph');
    verify($result[2]['innerBlocks'][0]['innerBlocks'][1]['blockName'])->equals('core/paragraph');
  }

  public function testItWrapsFullWidthBlocksIntoColumns(): void {
    $parsedDocument = [$this->paragraphBlock, $this->columnsBlock, $this->headingBlock, $this->paragraphBlock, $this->headingBlock, $this->columnsBlock];
    $parsedDocument[0]['attrs']['align'] = 'full';
    $parsedDocument[4]['attrs']['align'] = 'full';
    $parsedDocument[5]['attrs']['align'] = 'full';
    $parsedDocument[5]['innerBlocks'][0]['innerBlocks'][] = $this->paragraphBlock;
    // We expect to wrap top level paragraph blocks into columns so the result should three columns blocks
    $result = $this->preprocessor->preprocess($parsedDocument, $this->layout, $this->styles);
    verify($result)->arrayCount(5);
    // First block is a full width paragraph and must be wrapped in a single column
    verify($result[0]['blockName'])->equals('core/columns');
    verify($result[0]['attrs'])->equals(['align' => 'full']);
    verify($result[0]['innerBlocks'][0]['blockName'])->equals('core/column');
    verify($result[0]['innerBlocks'][0]['innerBlocks'][0])->equals($parsedDocument[0]);
    // Second block is a columns block and must remain unchanged
    verify($result[1])->equals($parsedDocument[1]);
    // Third block must contain heading and paragraph blocks
    verify($result[2]['blockName'])->equals('core/columns');
    verify($result[2]['innerBlocks'][0]['blockName'])->equals('core/column');
    verify($result[2]['innerBlocks'][0]['innerBlocks'])->arrayCount(2);
    verify($result[2]['innerBlocks'][0]['innerBlocks'][0])->equals($parsedDocument[2]);
    verify($result[2]['innerBlocks'][0]['innerBlocks'][1])->equals($parsedDocument[3]);
    // Fourth block is a full width heading and must be wrapped in a single column
    verify($result[3]['blockName'])->equals('core/columns');
    verify($result[3]['attrs'])->equals(['align' => 'full']);
    verify($result[3]['innerBlocks'][0]['blockName'])->equals('core/column');
    verify($result[3]['innerBlocks'][0]['innerBlocks'][0])->equals($parsedDocument[4]);
    // Fifth block should stay unchanged
    verify($result[4])->equals($parsedDocument[5]);
  }
}
