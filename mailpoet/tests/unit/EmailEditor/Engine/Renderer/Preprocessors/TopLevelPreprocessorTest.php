<?php declare(strict_types = 1);

namespace unit\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\Preprocessors\TopLevelPreprocessor;

class TopLevelPreprocessorTest extends \MailPoetUnitTest {

  private $paragraphBlock = [
    'blockName' => 'core/paragraph',
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

  public function _before() {
    parent::_before();
    $this->preprocessor = new TopLevelPreprocessor();
  }

  public function testItWrapsSingleTopLevelBlockIntoColumns() {
    $parsedDocument = [$this->paragraphBlock];
    $result = $this->preprocessor->preprocess($parsedDocument);
    verify($result[0]['blockName'])->equals('core/columns');
    verify($result[0]['innerBlocks'][0]['blockName'])->equals('core/column');
    verify($result[0]['innerBlocks'][0]['innerBlocks'][0]['blockName'])->equals('core/paragraph');
    verify($result[0]['innerBlocks'][0]['innerBlocks'][0]['innerHTML'])->equals('Paragraph content');
  }

  public function testItDoesntWrapColumns() {
    $parsedDocumentWithMultipleColumns = [$this->columnsBlock, $this->columnsBlock];
    $result = $this->preprocessor->preprocess($parsedDocumentWithMultipleColumns);
    verify($result)->equals($parsedDocumentWithMultipleColumns);
  }

  public function testItWrapsTopLevelBlocksSpreadBetweenColumns() {
    $parsedDocument = [$this->paragraphBlock, $this->columnsBlock, $this->paragraphBlock, $this->paragraphBlock];
    // We expect to wrap top level paragraph blocks into columns so the result should three columns blocks
    $result = $this->preprocessor->preprocess($parsedDocument);
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
}
