<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

class PreprocessorTest extends \MailPoetUnitTest {

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

  /** @var Preprocessor */
  private $preprocessor;

  public function _before() {
    parent::_before();
    $this->preprocessor = new Preprocessor();
  }

  public function testItWrapsSingleTopLevelBlockIntoColumns() {
    $parsedDocument = [$this->paragraphBlock];
    $result = $this->preprocessor->preprocess($parsedDocument);
    expect($result[0]['blockName'])->equals('core/columns');
    expect($result[0]['innerBlocks'][0]['blockName'])->equals('core/column');
    expect($result[0]['innerBlocks'][0]['innerBlocks'][0]['blockName'])->equals('core/paragraph');
    expect($result[0]['innerBlocks'][0]['innerBlocks'][0]['innerHTML'])->equals('Paragraph content');
  }

  public function testItDoesntWrapColumns() {
    $parsedDocumentWithMultipleColumns = [$this->columnsBlock, $this->columnsBlock];
    $result = $this->preprocessor->preprocess($parsedDocumentWithMultipleColumns);
    expect($result)->equals($parsedDocumentWithMultipleColumns);
  }

  public function testItWrapsTopLevelBlocksSpreadBetweenColumns() {
    $parsedDocument = [$this->paragraphBlock, $this->columnsBlock, $this->paragraphBlock, $this->paragraphBlock];
    // We expect to wrap top level paragraph blocks into columns so the result should three columns blocks
    $result = $this->preprocessor->preprocess($parsedDocument);
    expect($result)->count(3);
    // First columns contain columns with one paragraph block
    expect($result[0]['innerBlocks'][0]['blockName'])->equals('core/column');
    expect($result[0]['innerBlocks'][0]['innerBlocks'][0]['blockName'])->equals('core/paragraph');
    // Second columns remains empty
    expect($result[1]['innerBlocks'][0]['blockName'])->equals('core/column');
    expect($result[1]['innerBlocks'][0]['innerBlocks'])->isEmpty();
    // Third columns contain columns with two paragraph blocks
    expect($result[2]['innerBlocks'][0]['blockName'])->equals('core/column');
    expect($result[2]['innerBlocks'][0]['innerBlocks'])->count(2);
    expect($result[2]['innerBlocks'][0]['innerBlocks'][0]['blockName'])->equals('core/paragraph');
    expect($result[2]['innerBlocks'][0]['innerBlocks'][1]['blockName'])->equals('core/paragraph');
  }
}
