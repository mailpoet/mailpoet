<?php declare(strict_types = 1);

namespace MailPoet\Test\Newsletter\Editor;

use MailPoet\Newsletter\Editor\StructureTransformer;

class StructureTransformerTest extends \MailPoetUnitTest {
  /** @var StructureTransformer */
  private $transformer;

  public function _before() {
    parent::_before();
    $this->transformer = new StructureTransformer();
  }

  public function testItExtractsImagesAsImageBlocks() {
    $html = '<p><i>italic</i><em>previous text<a href="#mylink"><img src="#myimage" /></a>next text</em><b>bolded</b></p>';

    $blocks = $this->transformer->transform($html, false);

    expect($blocks)->count(3);
    expect($blocks[0]['type'])->equals('text');
    expect($blocks[0]['text'])->equals('<p><i>italic</i><em>previous text</em></p>');

    expect($blocks[1]['type'])->equals('image');
    expect($blocks[1]['src'])->equals('#myimage');
    expect($blocks[1]['link'])->equals('#mylink');

    expect($blocks[2]['type'])->equals('text');
    expect($blocks[2]['text'])->equals('<p><em>next text</em><b>bolded</b></p>');
  }
}
