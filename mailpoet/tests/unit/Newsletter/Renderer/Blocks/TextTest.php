<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer\Blocks;

use MailPoet\Newsletter\Editor\PostContentManager;
use MailPoet\Util\pQuery\DomNode;
use MailPoet\Util\pQuery\pQuery;

class TextTest extends \MailPoetUnitTest {

  private $block = [
    'type' => 'text',
    'text' => 'Text',
  ];

  /** @var pQuery */
  private $parser;

  public function _before() {
    parent::_before();
    $this->parser = new pQuery;
  }

  public function testItRendersPlainText() {
    $output = (new Text)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_text mailpoet_padded_vertical mailpoet_padded_side" valign="top" style="word-break:break-word;word-wrap:break-word;">
          Text
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }

  public function testItRendersParagraph() {
    $this->block['text'] = '<p>Text</p>';
    $output = (new Text)->render($this->block);
    $table = $this->parser->parseStr($output)->query('table');
    $this->assertInstanceOf(pQuery::class, $table);
    $tableElement = $table[0];
    $this->assertInstanceOf(DomNode::class, $tableElement);
    $paragraphTable = $tableElement->toString();
    $expectedResult = '<table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;" width="100%" cellpadding="0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align: left;">
            Text
          </td>
        </tr></table>';
    expect($paragraphTable)->equals($expectedResult);
  }

  public function testItRendersNewLinesBetweenWordPressParagraphs(): void {
    $this->block['text'] = '
      <p class="' . PostContentManager::WP_POST_CLASS . '">First</p>
      <p class="' . PostContentManager::WP_POST_CLASS . '">Second</p>
    ';
    $output = (new Text)->render($this->block);
    $table = $this->parser->parseStr($output)->query('table');
    $this->assertInstanceOf(pQuery::class, $table);
    $tableElement = $table[0];
    $this->assertInstanceOf(DomNode::class, $tableElement);
    $paragraphTable = $tableElement->toString();
    $expectedResult = '<table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;" width="100%" cellpadding="0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align: left;">
            First<br /><br />
          </td>
        </tr></table>';
    expect($paragraphTable)->equals($expectedResult);
    $tableElement = $table[1];
    $this->assertInstanceOf(DomNode::class, $tableElement);
    $paragraphTable = $tableElement->toString();
    $expectedResult = '<table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;" width="100%" cellpadding="0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align: left;">
            Second
          </td>
        </tr></table>';
    expect($paragraphTable)->equals($expectedResult);
  }

  public function testItRendersNewLinesWordPressParagraphAndHeading(): void {
    $this->block['text'] = '
      <p class="' . PostContentManager::WP_POST_CLASS . '">First</p>
      <h1>Second</h1>
    ';
    $output = (new Text)->render($this->block);
    $table = $this->parser->parseStr($output)->query('table');
    $this->assertInstanceOf(pQuery::class, $table);
    $tableElement = $table[0];
    $this->assertInstanceOf(DomNode::class, $tableElement);
    $paragraphTable = $tableElement->toString();
    $expectedResult = '<table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;" width="100%" cellpadding="0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align: left;">
            First<br /><br />
          </td>
        </tr></table>';
    expect($paragraphTable)->equals($expectedResult);
    $heading = $this->parser->parseStr($output)->query('h1');
    $this->assertInstanceOf(pQuery::class, $heading);
    $headingElement = $heading[0];
    $this->assertInstanceOf(DomNode::class, $headingElement);
    $heading = $headingElement->toString();
    $expectedResult = '<h1 style="text-align:left;padding:0;font-style:normal;font-weight:normal;">Second</h1>';
    expect($heading)->equals($expectedResult);
  }

  public function testItRendersList() {
    $this->block['text'] = '<ul><li>Item 1</li><li>Item 2</li></ul>';
    $output = (new Text)->render($this->block);
    $ul = $this->parser->parseStr($output)->query('ul');
    $this->assertInstanceOf(pQuery::class, $ul);
    $ulElement = $ul[0];
    $this->assertInstanceOf(DomNode::class, $ulElement);
    $list = $ulElement->toString();
    $expectedResult = '<ul class="mailpoet_paragraph" style="padding-top:0;padding-bottom:0;margin-top:10px;text-align:left;margin-bottom:10px;"><li class="mailpoet_paragraph" style="text-align:left;margin-bottom:10px;">Item 1</li><li class="mailpoet_paragraph" style="text-align:left;margin-bottom:10px;">Item 2</li></ul>';
    expect($list)->equals($expectedResult);
  }

  public function testItRendersBlockquotes() {
    $this->block['text'] = '<blockquote><p>Quote</p></blockquote>';
    $output = (new Text)->render($this->block);
    $table = $this->parser->parseStr($output)->query('table');
    $this->assertInstanceOf(pQuery::class, $table);
    $tableElement = $table[0];
    $this->assertInstanceOf(DomNode::class, $tableElement);
    $blockquoteTable = $tableElement->toString();
    $expectedResult = '<table class="mailpoet_blockquote" width="100%" spacing="0" border="0" cellpadding="0">
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
    expect($blockquoteTable)->equals($expectedResult);
  }

  public function testItShouldRemoveEmptyParagraphs() {
    $this->block['text'] = '<p></p><p>Text</p><p></p><p>Text2</p><p></p><p></p>';
    $output = (new Text)->render($this->block);
    $expectedResult = '
      <tr>
        <td class="mailpoet_text mailpoet_padded_vertical mailpoet_padded_side" valign="top" style="word-break:break-word;word-wrap:break-word;">
          <br /><table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;" width="100%" cellpadding="0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align: left;">
            Text<br /><br />
          </td>
        </tr></table><table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;" width="100%" cellpadding="0">
        <tr>
          <td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align: left;">
            Text2<br /><br />
          </td>
        </tr></table>
        </td>
      </tr>';
    expect($output)->equals($expectedResult);
  }

  public function testItStylesHeadings() {
    $this->block['text'] = '<h1>Heading</h1><h2>Heading 2</h2>';
    $output = (new Text)->render($this->block);
    expect($output)->stringContainsString('<h1 style="text-align:left;padding:0;font-style:normal;font-weight:normal;">Heading</h1>');
    expect($output)->stringContainsString('<h2 style="text-align:left;padding:0;font-style:normal;font-weight:normal;">Heading 2</h2>');
  }

  public function testItStylesHeadingsCenter() {
    $this->block['text'] = '<h1 style="text-align: center"><strong>Let\'s Get Started! </strong></h1>';
    $output = (new Text)->render($this->block);
    expect($output)->stringContainsString('<h1 style="text-align: center;padding:0;');
  }

  public function testItRemovesLastLineBreak() {
    $this->block['text'] = 'hello<br />';
    $output = (new Text)->render($this->block);
    expect($output)->stringNotContainsString('<br />');
  }
}
