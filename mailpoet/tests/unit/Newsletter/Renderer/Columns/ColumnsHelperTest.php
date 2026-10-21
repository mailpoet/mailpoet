<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Columns;

class ColumnsHelperTest extends \MailPoetUnitTest {
  public function testItUsesTheLayoutWidthsWhenTheyMatchTheColumnCount() {
    verify(ColumnsHelper::columnWidth(2, '1_2'))->equals([220, 440]);
  }

  public function testItIgnoresALayoutWithADifferentColumnCount() {
    verify(ColumnsHelper::columnWidth(3, '1_2'))->equals([220, 220, 220]);
  }

  public function testItSplitsTheWidthEvenlyForAnUnknownColumnCount() {
    verify(ColumnsHelper::columnWidth(4, null))->equals([165, 165, 165, 165]);
  }

  public function testItReturnsNoWidthsForNoColumns() {
    verify(ColumnsHelper::columnWidth(0, null))->equals([]);
  }
}
