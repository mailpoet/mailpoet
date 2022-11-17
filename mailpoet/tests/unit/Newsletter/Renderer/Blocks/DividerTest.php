<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

class DividerTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'divider',
    'styles' => [
      'block' => [
        'backgroundColor' => 'transparent',
        'padding' => '13px',
        'borderStyle' => 'solid',
        'borderWidth' => '2px',
        'borderColor' => '#ffffff',
      ],
    ],
  ];

  public function testItRendersCorrectly() {
    $output = (new Divider)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_divider" valign="top" style="padding: 13px 20px 13px 20px;">
          <table width="100%" border="0" cellpadding="0" cellspacing="0"
          style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;">
            <tr>
              <td class="mailpoet_divider-cell" style="border-top-width: 2px;border-top-style: solid;border-top-color: #ffffff;">
             </td>
            </tr>
          </table>
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }
}
