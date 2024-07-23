<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

class ButtonTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'button',
    'text' => 'Button',
    'url' => 'https://example.com',
    'styles' => [
      'block' => [
        'backgroundColor' => '#252525',
        'borderColor' => '#363636',
        'borderWidth' => '2px',
        'borderRadius' => '5px',
        'borderStyle' => 'solid',
        'width' => '180px',
        'lineHeight' => '40px',
        'fontColor' => '#ffffff',
        'fontFamily' => 'Source Sans Pro',
        'fontSize' => '14px',
        'fontWeight' => 'bold',
        'textAlign' => 'center',
      ],
    ],
  ];

  public function testItRendersCorrectly() {
    $output = (new Button)->render($this->block, 200);
    $expectedResult = '
      <tr>
        <td class="mailpoet_padded_vertical mailpoet_padded_side" valign="top">
          <div>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;">
              <tr>
                <td class="mailpoet_button-container" style="text-align:center;"><!--[if mso]>
                  <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                    href="https://example.com"
                    style="height:40px;
                           width:156px;
                           v-text-anchor:middle;"
                    arcsize="13%"
                    strokeweight="2px"
                    strokecolor="#363636"
                    fillcolor="#252525">
                  <w:anchorlock/>
                  <center style="color:#ffffff;
                    font-family:Source Sans Pro;
                    font-size:14px;
                    font-weight:bold;">Button
                  </center>
                  </v:roundrect>
                  <![endif]-->
                  <!--[if !mso]><!-- -->
                  <table
                    border="0"
                    cellspacing="0"
                    cellpadding="0"
                    role="presentation"
                    style="display:inline-block;border-collapse:separate;mso-table-lspace:0;mso-table-rspace:0;width:160px"
                    width="160px"
                  >
                    <tr>
                      <td class="mailpoet_table_button"
                        valign="middle"
                        role="presentation"
                        style="mso-table-lspace: 0;mso-table-rspace: 0;display:block;text-decoration:none;text-align:center;background-color: #252525;border-color: #363636;border-width: 2px;border-radius: 5px;border-style: solid;width: 156px;line-height: 40px;color: #ffffff;font-family: \'source sans pro\', \'helvetica neue\', helvetica, arial, sans-serif;font-size: 14px;font-weight: bold;"
                      >
                        <a class="mailpoet_button" style="
                          text-decoration: none;
                          display: block;
                          line-height: 40px;
                          color: #ffffff;
                        " href="https://example.com" target="_blank">Button</a>
                      </td>
                    </tr>
                  </table>
                  <!--<![endif]-->
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>';
    verify($output)->equals($expectedResult);
  }
}
