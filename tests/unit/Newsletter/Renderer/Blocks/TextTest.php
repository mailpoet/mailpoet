<?php

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Util\pQuery\pQuery;

class TextTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'text',
    'text' => 'Text',
  ];

  /** @var pQuery */
  private $parser;

  function _before() {
    parent::_before();
    $this->parser = new pQuery;
  }

  function testItRendersPlainText() {
    $output = Text::render($this->block);
    $expected_result = '
      <tr>
        <td class="mailpoet_text mailpoet_padded_vertical mailpoet_padded_side" valign="top" style="word-break:break-word;word-wrap:break-word;">
          Text
        </td>
      </tr>';
    expect($output)->equals($expected_result);
  }

  function testItRendersParagraph() {
    $this->block['text'] = '<p>Text</p>';
    $output = Text::render($this->block);
    $table = $this->parser->parseStr($output)->query('table');
    assert($table instanceof \pQuery);
    $paragraph_table = $table[0]->toString();
    $expected_result = '<table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;" width="100%" cellpadding="0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align: left;">
            Text
          </td>
        </tr></table>';
    expect($paragraph_table)->equals($expected_result);
  }

  function testItRendersList() {
    $this->block['text'] = '<ul><li>Item 1</li><li>Item 2</li></ul>';
    $output = Text::render($this->block);
    $ul = $this->parser->parseStr($output)->query('ul');
    assert($ul instanceof \pQuery);
    $list = $ul[0]->toString();
    $expected_result = '<ul class="mailpoet_paragraph" style="padding-top:0;padding-bottom:0;margin-top:10px;text-align:left;margin-bottom:10px;"><li class="mailpoet_paragraph" style="text-align:left;margin-bottom:10px;">Item 1</li><li class="mailpoet_paragraph" style="text-align:left;margin-bottom:10px;">Item 2</li></ul>';
    expect($list)->equals($expected_result);
  }

  function testItRendersBlockquotes() {
    $this->block['text'] = '<blockquote><p>Quote</p></blockquote>';
    $output = Text::render($this->block);
    $table = $this->parser->parseStr($output)->query('table');
    assert($table instanceof \pQuery);
    $blockquote_table = $table[0]->toString();
    $expected_result = '<table class="mailpoet_blockquote" width="100%" spacing="0" border="0" cellpadding="0">
        <tbody>
          <tr>
            <td width="2" bgcolor="#565656"></td>
            <td width="10"></td>
            <td valign="top">
              <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
                <tr>
                  <td class="mailpoet_blockquote">
                  Quote
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </tbody></table>';
    expect($blockquote_table)->equals($expected_result);
  }

  function testItStylesHeadings() {
    $this->block['text'] = '<h1>Heading</h1><h2>Heading 2</h2>';
    $output = Text::render($this->block);
    expect($output)->contains('<h1 style="text-align:left;padding:0;font-style:normal;font-weight:normal;">Heading</h1>');
    expect($output)->contains('<h2 style="text-align:left;padding:0;font-style:normal;font-weight:normal;">Heading 2</h2>');
  }

  function testItRemovesLastLineBreak() {
    $this->block['text'] = 'hello<br />';
    $output = Text::render($this->block);
    expect($output)->notContains('<br />');
  }
}
