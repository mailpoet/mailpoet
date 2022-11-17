<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

class SpacerTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'spacer',
    'styles' => [
      'block' => [
        'backgroundColor' => 'transparent',
        'height' => '13px',
      ],
    ],
  ];

  public function testItRendersCorrectly() {
    $output = (new Spacer)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_spacer" height="13" valign="top"></td>
      </tr>';
    expect($output)->equals($expectedResult);
  }

  public function testsItRendersWithBackground() {
    $this->block['styles']['block']['backgroundColor'] = "#ffffff";
    $output = (new Spacer)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_spacer" bgcolor="#ffffff" height="13" valign="top"></td>
      </tr>';
    expect($output)->equals($expectedResult);
  }
}
