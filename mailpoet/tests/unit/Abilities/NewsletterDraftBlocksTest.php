<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use Codeception\Stub;
use MailPoet\Abilities\NewsletterDraftBlocks;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterDraftBlocksTest extends \MailPoetUnitTest {
  public function testItBuildsTheMarkupTheCoreBlocksSave(): void {
    $wp = Stub::make(WPFunctions::class, [
      'sanitizeTextField' => function (string $text): string {
        return $text;
      },
      'wpKses' => function (string $text): string {
        return $text;
      },
      'forceBalanceTags' => function (string $text): string {
        return $text;
      },
      'escUrlRaw' => function (string $url): string {
        return $url;
      },
      'wpParseUrl' => function (string $url) {
        return parse_url($url);
      },
      'escHtml' => function (string $text): string {
        return htmlspecialchars($text, ENT_QUOTES);
      },
      'escAttr' => function (string $text): string {
        return htmlspecialchars($text, ENT_QUOTES);
      },
    ]);
    $shortcodes = Stub::makeEmptyExcept(Shortcodes::class, 'extract');

    $result = (new NewsletterDraftBlocks($wp, $shortcodes))->prepare([
      ['type' => 'heading', 'text' => 'H2 & more'],
      ['type' => 'heading', 'text' => 'H1', 'level' => 1],
      ['type' => 'paragraph', 'text' => 'Some <strong>text</strong>'],
      ['type' => 'image', 'url' => 'https://example.com/a.png', 'alt' => 'Alt "quoted"'],
      ['type' => 'button', 'text' => 'Read', 'url' => 'https://example.com/r?a=1&b=2'],
    ]);

    verify($result['errors'])->equals([]);
    verify($result['warnings'])->equals([]);
    verify($result['markup'])->equals(
      '<!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->' . "\n"
      . '<div class="wp-block-group" style="padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">'
      . '<!-- wp:heading -->' . "\n"
      . '<h2 class="wp-block-heading">H2 &amp; more</h2>' . "\n"
      . '<!-- /wp:heading -->' . "\n\n"
      . '<!-- wp:heading {"level":1} -->' . "\n"
      . '<h1 class="wp-block-heading">H1</h1>' . "\n"
      . '<!-- /wp:heading -->' . "\n\n"
      . '<!-- wp:paragraph -->' . "\n"
      . '<p>Some <strong>text</strong></p>' . "\n"
      . '<!-- /wp:paragraph -->' . "\n\n"
      . '<!-- wp:image -->' . "\n"
      . '<figure class="wp-block-image"><img src="https://example.com/a.png" alt="Alt &quot;quoted&quot;"/></figure>' . "\n"
      . '<!-- /wp:image -->' . "\n\n"
      . '<!-- wp:buttons -->' . "\n"
      . '<div class="wp-block-buttons"><!-- wp:button -->' . "\n"
      . '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://example.com/r?a=1&amp;b=2">Read</a></div>' . "\n"
      . '<!-- /wp:button --></div>' . "\n"
      . '<!-- /wp:buttons -->'
      . '</div>' . "\n"
      . '<!-- /wp:group -->'
    );
  }
}
