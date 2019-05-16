<?php
namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\Newsletter\Renderer\StylesHelper;
use MailPoet\Newsletter\Renderer\EscapeHelper as EHelper;
use MailPoet\Util\pQuery\pQuery;

class Text {
  static function render($element) {
    $html = $element['text'];
    // replace &nbsp; with spaces
    $html = str_replace('&nbsp;', ' ', $html);
    $html = str_replace('\xc2\xa0', ' ', $html);
    $html = self::convertBlockquotesToTables($html);
    $html = self::convertParagraphsToTables($html);
    $html = self::styleLists($html);
    $html = self::styleHeadings($html);
    $html = self::removeLastLineBreak($html);
    $template = '
      <tr>
        <td class="mailpoet_text mailpoet_padded_vertical mailpoet_padded_side" valign="top" style="word-break:break-word;word-wrap:break-word;">
          ' . $html . '
        </td>
      </tr>';
    return $template;
  }

  static function convertBlockquotesToTables($html) {
    $DOM_parser = new pQuery();
    $DOM = $DOM_parser->parseStr($html);
    $blockquotes = $DOM->query('blockquote');
    foreach ($blockquotes as $blockquote) {
      $contents = [];
      $paragraphs = $blockquote->query('p, h1, h2, h3, h4', 0);
      foreach ($paragraphs as $index => $paragraph) {
        if (preg_match('/h\d/', $paragraph->getTag())) {
          $contents[] = $paragraph->getOuterText();
        } else {
          $contents[] = str_replace('&', '&amp;', $paragraph->html());
        }
          if ($index + 1 < $paragraphs->count()) $contents[] = '<br />';
          $paragraph->remove();
      }
      if (empty($contents)) continue;
      $blockquote->setTag('table');
      $blockquote->addClass('mailpoet_blockquote');
      $blockquote->width = '100%';
      $blockquote->spacing = 0;
      $blockquote->border = 0;
      $blockquote->cellpadding = 0;
      $blockquote->html('
        <tbody>
          <tr>
            <td width="2" bgcolor="#565656"></td>
            <td width="10"></td>
            <td valign="top">
              <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
                <tr>
                  <td class="mailpoet_blockquote">
                  ' . implode('', $contents) . '
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody>'
      );
      $blockquote = self::insertLineBreak($blockquote);
    }
    return $DOM->__toString();
  }

  static function convertParagraphsToTables($html) {
    $DOM_parser = new pQuery();
    $DOM = $DOM_parser->parseStr($html);
    $paragraphs = $DOM->query('p');
    if (!$paragraphs->count()) return $html;
    foreach ($paragraphs as $paragraph) {
      // process empty paragraphs
      if (!trim($paragraph->html())) {
          $next_element = ($paragraph->getNextSibling()) ?
            trim($paragraph->getNextSibling()->text()) :
            false;
          $previous_element = ($paragraph->getPreviousSibling()) ?
            trim($paragraph->getPreviousSibling()->text()) :
            false;
        $previous_element_tag = ($previous_element) ?
          $paragraph->getPreviousSibling()->tag :
          false;
        // if previous or next paragraphs are empty OR previous paragraph
        // is a heading, insert a break line
        if (!$next_element ||
            !$previous_element ||
            (preg_match('/h\d+/', $previous_element_tag))
        ) {
          $paragraph = self::insertLineBreak($paragraph);
        }
        $paragraph->remove();
        continue;
      }
      $style = $paragraph->style;
      if (!preg_match('/text-align/i', $style)) {
        $style = 'text-align: left;' . $style;
      }
      $contents = str_replace('&', '&amp;', $paragraph->html());
      $paragraph->setTag('table');
      $paragraph->style = 'border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;';
      $paragraph->width = '100%';
      $paragraph->cellpadding = 0;
      $next_element = $paragraph->getNextSibling();
      // unless this is the last element in column, add double line breaks
      $line_breaks = ($next_element && !trim($next_element->text())) ?
        '<br /><br />' :
        '';
      // if this element is followed by a list, add single line break
      $line_breaks = ($next_element && preg_match('/<li/i', $next_element->getOuterText())) ?
        '<br />' :
        $line_breaks;
      if ($paragraph->hasClass(PostContentManager::WP_POST_CLASS)) {
        $paragraph->removeClass(PostContentManager::WP_POST_CLASS);
        // if this element is followed by a paragraph, add double line breaks
        $line_breaks = ($next_element && preg_match('/<p/i', $next_element->getOuterText())) ?
          '<br /><br />' :
          $line_breaks;
      }
      $paragraph->html('
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;' . EHelper::escapeHtmlStyleAttr($style) . '">
            ' . $contents . $line_breaks . '
          </td>
        </tr>'
      );
    }
    return $DOM->__toString();
  }

  static function styleLists($html) {
    $DOM_parser = new pQuery();
    $DOM = $DOM_parser->parseStr($html);
    $lists = $DOM->query('ol, ul, li');
    if (!$lists->count()) return $html;
    foreach ($lists as $list) {
      if ($list->tag === 'li') {
        $list->setInnertext(str_replace('&', '&amp;', $list->html()));
        $list->class = 'mailpoet_paragraph';
      } else {
        $list->class = 'mailpoet_paragraph';
        $list->style .= 'padding-top:0;padding-bottom:0;margin-top:10px;';
      }
      $list->style = StylesHelper::applyTextAlignment($list->style);
      $list->style .= 'margin-bottom:10px;';
      $list->style = EHelper::escapeHtmlStyleAttr($list->style);
    }
    return $DOM->__toString();
  }

  static function styleHeadings($html) {
    $DOM_parser = new pQuery();
    $DOM = $DOM_parser->parseStr($html);
    $headings = $DOM->query('h1, h2, h3, h4');
    if (!$headings->count()) return $html;
    foreach ($headings as $heading) {
      $heading->style = StylesHelper::applyTextAlignment($heading->style);
      $heading->style .= 'padding:0;font-style:normal;font-weight:normal;';
      $heading->style = EHelper::escapeHtmlStyleAttr($heading->style);
    }
    return $DOM->__toString();
  }

  static function removeLastLineBreak($html) {
    return preg_replace('/(^)?(<br[^>]*?\/?>)+$/i', '', $html);
  }

  static function insertLineBreak($element) {
    $element->parent->insertChild(
      [
        'tag_name' => 'br',
        'self_close' => true,
        'attributes' => [],
      ],
      $element->index() + 1
    );
    return $element;
  }
}
