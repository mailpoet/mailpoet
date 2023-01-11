<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;
use MailPoet\Newsletter\Renderer\StylesHelper;
use MailPoet\WooCommerce\Helper;

class Coupon {
  const TYPE = 'coupon';

  const CODE_PLACEHOLDER = 'XXXX-XXXXXXX-XXXX';

  /*** @var Helper */
  private $helper;

  public function __construct(
    Helper $helper
  ) {
    $this->helper = $helper;
  }

  public function render($element, $columnBaseWidth) {
    $couponCode = self::CODE_PLACEHOLDER;
    if (!empty($element['couponId'])) {
      $couponCode = $this->helper->wcGetCouponCodeById($element['couponId']);
    }
    $element['styles']['block']['width'] = $this->calculateWidth($element, $columnBaseWidth);
    $styles = 'display:inline-block;-webkit-text-size-adjust:none;mso-hide:all;text-decoration:none;text-align:center;' . StylesHelper::getBlockStyles($element, $exclude = ['textAlign']);
    $styles = EHelper::escapeHtmlStyleAttr($styles);
    $template = '
      <tr>
        <td class="mailpoet_padded_vertical mailpoet_padded_side" valign="top">
          <div>
            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;">
              <tr>
                <td class="mailpoet_coupon-container" style="text-align:' . $element['styles']['block']['textAlign'] . ';"><!--[if mso]>
                  <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                    style="height:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['lineHeight']) . ';
                           width:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['width']) . ';
                           v-text-anchor:middle;"
                    arcsize="' . round((int)$element['styles']['block']['borderRadius'] / ((int)$element['styles']['block']['lineHeight'] ?: 1) * 100) . '%"
                    strokeweight="' . EHelper::escapeHtmlAttr($element['styles']['block']['borderWidth']) . '"
                    strokecolor="' . EHelper::escapeHtmlAttr($element['styles']['block']['borderColor']) . '"
                    fillcolor="' . EHelper::escapeHtmlAttr($element['styles']['block']['backgroundColor']) . '">
                  <w:anchorlock/>
                  <center style="color:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['fontColor']) . ';
                    font-family:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['fontFamily']) . ';
                    font-size:' . EHelper::escapeHtmlStyleAttr($element['styles']['block']['fontSize']) . ';
                    font-weight:bold;">' . EHelper::escapeHtmlText($element['code']) . '
                  </center>
                  </v:roundrect>
                  <![endif]-->
                  <!--[if !mso]><!-- -->
                  <div class="mailpoet_coupon" style="' . $styles . '"> ' . EHelper::escapeHtmlText($couponCode) . '</div>
                  <!--<![endif]-->
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>';
    return $template;
  }

  public function calculateWidth($element, $columnBaseWidth): string {
    $columnWidth = $columnBaseWidth - (StylesHelper::$paddingWidth * 2);
    $borderWidth = (int)$element['styles']['block']['borderWidth'];
    $width = (int)$element['styles']['block']['width'];
    $width = ($width > $columnWidth) ?
      $columnWidth :
      $width;
    return ($width - (2 * $borderWidth) . 'px');
  }
}
