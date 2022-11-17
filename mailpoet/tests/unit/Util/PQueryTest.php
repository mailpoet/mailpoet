<?php declare(strict_types = 1);

namespace MailPoet\Test\Util;

use MailPoet\Util\pQuery\pQuery;

class PQueryTest extends \MailPoetUnitTest {
  public function testBreakingQuoteAreNotRendered() {
    $html = '<a href="#" title="Escape " this"></a>';
    $domnode = pQuery::parseStr($html);
    $innerText = $domnode->getInnerText();
    expect($innerText)->equals("");
  }

  public function testQuotesAreCorrectlyEscaped() {
    $htmlCharacters = ['&quot;', '&#34;', '&#39;'];

    foreach ($htmlCharacters as $char) {
      $this->parseTest($char);
    }
  }

  public function testEncodedHtmlNamesAreDecoded() {
    $htmlNames = ['&amp;', '&lt;', '&gt;', '&nbsp;', '&iexcl;', '&cent;', '&pound;', '&curren;', '&yen;', '&brvbar;', '&sect;', '&uml;', '&copy;', '&ordf;', '&laquo;', '&not;', '&shy;', '&reg;', '&macr;', '&deg;', '&plusmn;', '&sup2;', '&sup3;', '&acute;', '&micro;', '&para;', '&middot;', '&cedil;', '&sup1;', '&ordm;', '&raquo;', '&frac14;', '&frac12;', '&frac34;', '&iquest;', '&Agrave;', '&Aacute;', '&Acirc;', '&Atilde;', '&Auml;', '&Aring;', '&AElig;', '&Ccedil;', '&Egrave;', '&Eacute;', '&Ecirc;', '&Euml;', '&Igrave;', '&Iacute;', '&Icirc;', '&Iuml;', '&ETH;', '&Ntilde;', '&Ograve;', '&Oacute;', '&Ocirc;', '&Otilde;', '&Ouml;', '&times;', '&Oslash;', '&Ugrave;', '&Uacute;', '&Ucirc;', '&Uuml;', '&Yacute;', '&THORN;', '&szlig;', '&agrave;', '&aacute;', '&acirc;', '&atilde;', '&auml;', '&aring;', '&aelig;', '&ccedil;', '&egrave;', '&eacute;', '&ecirc;', '&euml;', '&igrave;', '&iacute;', '&icirc;', '&iuml;', '&eth;', '&ntilde;', '&ograve;', '&oacute;', '&ocirc;', '&otilde;', '&ouml;', '&divide;', '&oslash;', '&ugrave;', '&uacute;', '&ucirc;', '&uuml;', '&yacute;', '&thorn;', '&yuml;'];

    foreach ($htmlNames as $char) {
      $this->parseTest($char, $equals = false);
    }
  }

  public function testEncodedHtmlNumbersAreDecoded() {
    // Tested numbers are from https://www.ascii.cl/htmlcodes.htm
    $htmlNumbers = array_merge(range(40, 126), range(160, 255), [32, 33, 35, 36, 37, 38, 338, 339, 352, 353, 376, 402, 8211, 8212, 8216, 8217, 8218, 8220, 8221, 8222, 8224, 8225, 8226, 8230, 8240, 8364, 8482]);

    foreach ($htmlNumbers as $char) {
      $this->parseTest('&#' . $char . ';', $equals = false);
    }
  }

  public function testItCanParseRealHtmlSnippets() {
    $snippets = [
      '<table width="100%" border="0" cellpadding="0" cellspacing="0" class="mailpoet_cols-one" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;table-layout:fixed;margin-left:auto;margin-right:auto;padding-left:0;padding-right:0;background-color:#ffffff;border-collapse:collapse" bgcolor="#ffffff"><tbody><tr><td class="mailpoet_header_footer_padded mailpoet_header" style="line-height:19.2px;text-align:center ;color:#222222 ;font-family:Arial ;font-size:12px ;border-collapse:collapse;padding:10px 20px"> <a href="http://littlespree.com?mailpoet_router&endpoint=track&action=click&data=WyIyMjIwIiwiZDE0Zjc3IiwiMTA4IiwiMTBiYjc1ZDhiZjgxIixmYWxzZV0" style="color:#6cb7d4 ;text-decoration:underline ">View this newsletter in your browser.</a></td></tr></tbody></table>',
      '<td class="mailpoet_spacer" height="30" valign="top" style="border-collapse:collapse"></td>',
      '<a href="http://littlespree.com?mailpoet_router&endpoint=track&action=click&data=WyIyMjIwIiwiZDE0Zjc3IiwiMTA4IiwiZDEzMTMxOTEyOTk5IixmYWxzZV0" style="text-decoration:none;color:#f3d0c8"><img src="http://littlespree.com/wp-content/plugins/mailpoet/assets/img/newsletter_editor/social-icons/02-grey/Twitter.png?mailpoet_version=3.2.1" width="32" height="32" style="width:32px;height:32px;-ms-interpolation-mode:bicubic;border:0;display:inline;outline:none;" alt="twitter" /></a>',
      '<td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align:left;border-collapse:collapse;color:#000000;font-family:Georgia,Times,\'Times New Roman\',serif;font-size:13px;line-height:20.8px"><a href="http://littlespree.com?mailpoet_router&endpoint=track&action=click&data=WyIyMjIwIiwiZDE0Zjc3IiwiMTA4IiwiYTU0N2Y2OTlmZWRkIixmYWxzZV0" style="color:#f3d0c8;text-decoration:none">read full post</a></td>',
      '<table style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0;border-collapse:collapse" data-post-id="9023" width="100%" cellpadding="0"><tr><td class="mailpoet_paragraph" style="word-break:break-word;word-wrap:break-word;text-align:left;border-collapse:collapse;color:#000000;font-family:Arial,\'Helvetica Neue\',Helvetica,sans-serif;font-size:16px;line-height:25.6px"><span>KIZOMBA SUR, el último evento del verano </span><span>que no te debes perder <span class="_5mfr _47e3"><span class="_7oe"></span></span><span class="_5mfr _47e3"><span class="_7oe">🌞</span></span>  </span><span>Aprovecha ahora esta oferta limitada y llama </span><span class="_5mfr _47e3"><span class="_7oe">➡️</span></span><span class="_5mfr _47e3"><span class="_7oe">➡️</span></span><span>+34 660 144 954</span><span class="_5mfr _47e3"><span class="_7oe"><span></span></span></span></td></tr></table>',
      '<td class="mailpoet_image " align="center" valign="top" style="border-collapse:collapse"><a href="https://loveskizomba.com?mailpoet_router&endpoint=track&action=click&data=WyIyMzciLCIwYzgyZWQiLCIyMiIsIjhjNTI0M2E0ZjM1ZCIsZmFsc2Vd" style="color:#21759B;text-decoration:underline"><img style="max-width:660px;height:auto;width:100%;-ms-interpolation-mode:bicubic;border:0;display:block;outline:none;text-align:center" src="https://loveskizomba.com/wp-content/uploads/Kizomba-Sur.png" width="660" alt="Kizomba Sur" /></a></td>',
      '<tr> <td class="mailpoet_header_footer_padded mailpoet_footer" style="line-height:19.2px;text-align:center ;color:#222222 ;font-family:Arial ;font-size:12px ;border-collapse:collapse;padding:10px 20px"> <a href="https://loveskizomba.com?mailpoet_router&endpoint=track&action=click&data=WyIyMzciLCIwYzgyZWQiLCIyMiIsIjVjOTA2NTQ2ZDhkNSIsZmFsc2Vd" style="color:#6cb7d4 ;text-decoration:none ">Darse de Baja</a> | <a href="https://loveskizomba.com?mailpoet_router&endpoint=track&action=click&data=WyIyMzciLCIwYzgyZWQiLCIyMiIsIjc2MTU0YmE4NGRhMiIsZmFsc2Vd" style="color:#6cb7d4 ;text-decoration:none ">Gestionar suscripción</a><br />Loves Dance to <span style="background-color: transparent;">Loves Kizomba</span><br /><span style="background-color: transparent;">Avda. Marconi 2 11009 Cádiz </span> </td> </tr> ',
      '<td class="mailpoet_button-container" style="text-align:center;border-collapse:collapse"><!--[if mso]> <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="https://www.derooboeken.nl/product/een-woord-op-zijn-tijd-complete-set-4-delen/" style="height:30px; width:118px; v-text-anchor:middle;" arcsize="17%" strokeweight="1px" strokecolor="#000000" fillcolor="#ffffff"> <w:anchorlock/> <center style="color:#000000; font-family:Arial; font-size:20px; font-weight:bold;">Meer lezen </center> </v:roundrect> <![endif]--><a class="mailpoet_button" href="https://www.derooboeken.nl?mailpoet_router&endpoint=track&action=click&data=WyI4NzY5IiwiZTZjNjJmIiwiNDIiLCI0MDJkMzZkNGMwNGIiLGZhbHNlXQ" style="display:inline-block;-webkit-text-size-adjust:none;mso-hide:all;text-decoration:none;text-align:center;background-color:#ffffff ;border-color:#000000 ;border-width:1px ;border-radius:5px ;border-style:solid ;width:118px ;line-height:30px ;color:#000000 ;font-family:Arial ;font-size:20px ;font-weight:normal "> Meer lezen </a></td>',
      '<h1 data-post-id="92828" style="text-align:left;padding:0;font-style:normal;font-weight:700;margin:0 0 5.1px;color:#111111;font-family:\'Trebuchet MS\',\'Lucida Grande\',\'Lucida Sans Unicode\',\'Lucida Sans\',Tahoma,sans-serif;font-size:17px;line-height:27.2px">Les petits Bollandistes : vies des Saints de l\'Acien et du Nouveau Testament, des Martyrs, des Pères, des Auteurs sacrés et ecclésiastiques (17 Volumes). Supplément aux vies des saints et spécialement aux Petits bollandistes : d\'après les documents hagiographiques les plus authentiques et les plus récents (3 volumes). (Complete Set, 20 volumes)</h1>',
    ];

    foreach ($snippets as $snippet) {
      $this->parseTest($snippet);
    }
  }

  public function parseTest($html, $equals = true) {
    $parsedHtml = pQuery::parseStr($html)->getInnerText();
    if ($equals) {
      expect($parsedHtml)->equals($html);
    } else {
      expect($parsedHtml)->notEquals($html);
    }
  }
}
