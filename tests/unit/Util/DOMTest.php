<?php
namespace MailPoet\Test\Util;

use pQuery;
use MailPoet\Util\DOM as DOMUtil;

class DOMTest extends \MailPoetTest {

  function _before() {
    $this->root = pQuery::parseStr('<p><i>italic</i><em>previous text<a href="#mylink"><img src="#myimage" /></a>next text</em><b>bolded</b></p>');
  }

  function testItDeepSplitsDOMTreeByElement() {
    DOMUtil::splitOn($this->root, $this->root->query('a')->offsetGet(0));

    expect($this->root->html())->equals(
      '<p><i>italic</i><em>previous text</em></p>'.
      '<a href="#mylink"><img src="#myimage" /></a>'.
      '<p><em>next text</em><b>bolded</b></p>'
    );
  }

  function testItFindsTopAncestor() {
    $image = $this->root->query('img')->offsetGet(0);
    $paragraph = $this->root->query('p')->offsetGet(0);

    expect(DOMUtil::findTopAncestor($image))->equals($paragraph);
  }
}
