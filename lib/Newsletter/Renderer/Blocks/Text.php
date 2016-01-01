<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

class Text {
  static function render($element) {
    $html = $element['text'];
    $html = self::convertBlockquotesToTables($html);
    $html = self::convertParagraphsToTables($html);
    $html = self::addLineBreakAfterTags($html);
    $html = self::styleLists($html);
    $html = self::styleHeadings($html);
    $html = self::removeLastElementBreakLine($html);
    $template = '
      <tr>
        <td class="mailpoet_text mailpoet_padded" valign="top" style="word-break:break-word;word-wrap:break-word;">
          ' . $html . '
        </td>
      </tr>';
    return $template;
  }

  static function convertParagraphsToTables($html) {
    $html = preg_replace('/<p>(.*?)<\/p>/', '
      <table width="100%" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;">
            $1
            <br /><br />
          </td>
         </tr>
      </table>'
      , $html);
    $html = preg_replace('/<p style=\"(.*)\">(.*?)<\/p>/', '
      <table width="100%" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;$1">
            $2
            <br /><br />
          </td>
         </tr>
      </table>'
      , $html);
    return $html;
  }


  static function removeLastElementBreakLine($html) {
    return preg_replace('/<br\/>([^<br\/>]*)$/s', '', $html);
  }

  static function addLineBreakAfterTags($html) {
    return preg_replace('/(<\/(ul|ol|h\d)>)/', '$1<br />', $html);
  }

  static function convertBlockquotesToTables($html) {
    $template = '
      <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tbody>
          <tr>
            <td width="2" bgcolor="#565656"></td>
            <td width="10"></td>
            <td valign="top">
              <table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
                <tr>
                  <td class="mailpoet_blockquote">
                    $1
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody>
      </table>
      <br/>';
    preg_match('/<blockquote>.*?<\/blockquote>/s', $html, $blockquotes);
    foreach ($blockquotes as $index => $blockquote) {
      $blockquote = preg_replace('/<\/p>\n<p>/', '<br/><br/>', $blockquote);
      $blockquote = preg_replace('/<\/?p>/', '', $blockquote);
      $blockquote = preg_replace(
        '/<blockquote>(.*?)<\/blockquote>/s',
        $template,
        $blockquote
      );
      $html = preg_replace(
        '/' . preg_quote($blockquotes[$index], '/') . '/',
        $blockquote,
        $html
      );
    }
    return $html;
  }

  static function styleHeadings($html) {
    return preg_replace(
      '/<(h[1-6])(?:.+style=\"(.*)?\")?>/',
      '<$1 style="margin:0;font-style:normal;font-weight:normal;$2">',
      $html
    );
  }

  static function styleLists($html) {
    $html = preg_replace(
      '/<(ul|ol)>/',
      '<$1 class="mailpoet_paragraph" style="padding-top:0;padding-bottom:0;margin-top:0;margin-bottom:0;">',
      $html
    );
    $html = preg_replace('/<li>/', '<li class="mailpoet_paragraph">', $html);
    return $html;
  }
}