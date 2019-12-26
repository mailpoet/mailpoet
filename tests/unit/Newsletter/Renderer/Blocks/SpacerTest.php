<?php

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
    $output = Spacer::render($this->block);
    $expected_result = '
      <tr>
        <td class="mailpoet_spacer" height="13" valign="top"></td>
      </tr>';
    expect($output)->equals($expected_result);
  }

  public function testsItRendersWithBackground() {
    $this->block['styles']['block']['backgroundColor'] = "#ffffff";
    $output = Spacer::render($this->block);
    $expected_result = '
      <tr>
        <td class="mailpoet_spacer" bgcolor="#ffffff" height="13" valign="top"></td>
      </tr>';
    expect($output)->equals($expected_result);
  }
}
