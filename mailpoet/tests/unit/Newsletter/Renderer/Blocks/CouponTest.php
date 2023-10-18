<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\WooCommerce\Helper;

class CouponTest extends \MailPoetUnitTest {
  private const COUPON_CODE = 'ABCD-1234-5678';

  private $block = [
    'type' => 'coupon',
    'couponId' => 1,
    'styles' => [
      'block' => [
        'backgroundColor' => '#eeeeee',
        'borderColor' => '#dddddd',
        'borderWidth' => '2px',
        'borderRadius' => '10px',
        'borderStyle' => 'solid',
        'lineHeight' => '30px',
        'fontColor' => '#ccffcc',
        'fontFamily' => 'Source Sans Pro',
        'fontSize' => '14px',
        'fontWeight' => 'bold',
        'textAlign' => 'center',
        'width' => '150px',
      ],
    ],
  ];

  public function testItRendersCorrectly() {
    $wcHelper = $this->make(Helper::class, [
      'wcGetCouponCodeById' => self::COUPON_CODE,
    ]);

    $output = (new Coupon($wcHelper))->render($this->block, 200);
    $expectedResult = '
      <tr>
        <td class="mailpoet_padded_vertical mailpoet_padded_side" valign="top">
          <div>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;">
              <tr>
                <td class="mailpoet_coupon-container" style="text-align:center;"><!--[if mso]>
                  <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                    style="height:30px;
                           width:146px;
                           v-text-anchor:middle;"
                    arcsize="33%"
                    strokeweight="2px"
                    strokecolor="#dddddd"
                    fillcolor="#eeeeee">
                  <w:anchorlock/>
                  <center style="color:#ccffcc;
                    font-family:Source Sans Pro;
                    font-size:14px;
                    font-weight:bold;">' . self::COUPON_CODE . '
                  </center>
                  </v:roundrect>
                  <![endif]-->
                  <!--[if !mso]><!-- -->
                  <div class="mailpoet_coupon" style="display:inline-block;-webkit-text-size-adjust:none;mso-hide:all;text-decoration:none;text-align:center;background-color: #eeeeee;border-color: #dddddd;border-width: 2px;border-radius: 10px;border-style: solid;line-height: 30px;color: #ccffcc;font-family: \'source sans pro\', \'helvetica neue\', helvetica, arial, sans-serif;font-size: 14px;font-weight: bold;width: 146px;">' . self::COUPON_CODE . '</div>
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
