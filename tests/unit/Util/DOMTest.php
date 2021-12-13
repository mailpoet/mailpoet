<?php

namespace MailPoet\Test\Util;

use MailPoet\Util\DOM as DOMUtil;
use pQuery;

class DOMTest extends \MailPoetUnitTest {
  /** @var pQuery\DomNode */
  private $root;

  public function _before() {
    $this->root = pQuery::parseStr('<p><i>italic</i><em>previous text<a href="#mylink"><img src="#myimage" /></a>next text</em><b>bolded</b></p>');
  }

  public function testItDeepSplitsDOMTreeByElement() {
    $a = $this->root->query('a');
    assert($a instanceof pQuery);
    DOMUtil::splitOn($this->root, $a->offsetGet(0));

    expect($this->root->html())->equals(
      '<p><i>italic</i><em>previous text</em></p>' .
      '<a href="#mylink"><img src="#myimage" /></a>' .
      '<p><em>next text</em><b>bolded</b></p>'
    );
  }

  public function testItFindsTopAncestor() {
    $img = $this->root->query('img');
    assert($img instanceof pQuery);
    $image = $img->offsetGet(0);

    $p = $this->root->query('p');
    assert($p instanceof pQuery);
    $paragraph = $p->offsetGet(0);

    expect(DOMUtil::findTopAncestor($image))->equals($paragraph);
  }
}
