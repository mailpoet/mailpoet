<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use Codeception\Stub;
use MailPoet\Abilities\NewsletterDraftBlocks;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterDraftBlocksTest extends \MailPoetUnitTest {
  public function testItBuildsTheMarkupTheCoreBlocksSave(): void {
    $result = $this->getDraftBlocks()->prepare([
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

  public function testItBuildsTheMarkupOfStyledAndNewBlocks(): void {
    $result = $this->getDraftBlocks()->prepare([
      ['type' => 'heading', 'text' => 'Title', 'align' => 'center', 'text_color' => '#111111', 'background_color' => '#eeeeee'],
      ['type' => 'paragraph', 'text' => 'Text', 'align' => 'right', 'text_color' => '#222222'],
      ['type' => 'button', 'text' => 'Go', 'url' => 'https://example.com/g', 'align' => 'center', 'text_color' => '#ffffff', 'background_color' => '#0a7d33'],
      ['type' => 'list', 'items' => ['One', 'Two <strong>bold</strong>'], 'ordered' => true],
      ['type' => 'quote', 'text' => 'Quoted', 'background_color' => '#fafafa'],
      ['type' => 'spacer', 'height' => 30],
      ['type' => 'section', 'background_color' => '#eef2ff', 'blocks' => [['type' => 'paragraph', 'text' => 'Inside']]],
    ]);

    verify($result['errors'])->equals([]);
    verify($result['markup'])->stringContainsString(
      '<!-- wp:heading {"textAlign":"center","style":{"color":{"text":"#111111","background":"#eeeeee"}}} -->' . "\n"
      . '<h2 class="wp-block-heading has-text-align-center has-text-color has-background" style="color:#111111;background-color:#eeeeee">Title</h2>' . "\n"
      . '<!-- /wp:heading -->'
    );
    verify($result['markup'])->stringContainsString(
      '<!-- wp:paragraph {"align":"right","style":{"color":{"text":"#222222"}}} -->' . "\n"
      . '<p class="has-text-align-right has-text-color" style="color:#222222">Text</p>' . "\n"
      . '<!-- /wp:paragraph -->'
    );
    verify($result['markup'])->stringContainsString(
      '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n"
      . '<div class="wp-block-buttons"><!-- wp:button {"style":{"color":{"text":"#ffffff","background":"#0a7d33"}}} -->' . "\n"
      . '<div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background wp-element-button" href="https://example.com/g" style="color:#ffffff;background-color:#0a7d33">Go</a></div>' . "\n"
      . '<!-- /wp:button --></div>' . "\n"
      . '<!-- /wp:buttons -->'
    );
    verify($result['markup'])->stringContainsString(
      '<!-- wp:list {"ordered":true} -->' . "\n"
      . '<ol class="wp-block-list"><!-- wp:list-item -->' . "\n"
      . '<li>One</li>' . "\n"
      . '<!-- /wp:list-item -->' . "\n\n"
      . '<!-- wp:list-item -->' . "\n"
      . '<li>Two <strong>bold</strong></li>' . "\n"
      . '<!-- /wp:list-item --></ol>' . "\n"
      . '<!-- /wp:list -->'
    );
    verify($result['markup'])->stringContainsString(
      '<!-- wp:quote {"style":{"color":{"background":"#fafafa"}}} -->' . "\n"
      . '<blockquote class="wp-block-quote has-background" style="background-color:#fafafa"><!-- wp:paragraph -->' . "\n"
      . '<p>Quoted</p>' . "\n"
      . '<!-- /wp:paragraph --></blockquote>' . "\n"
      . '<!-- /wp:quote -->'
    );
    verify($result['markup'])->stringContainsString(
      '<!-- wp:spacer {"height":"30px"} -->' . "\n"
      . '<div style="height:30px" aria-hidden="true" class="wp-block-spacer"></div>' . "\n"
      . '<!-- /wp:spacer -->'
    );
    verify($result['markup'])->stringContainsString(
      '<!-- wp:group {"style":{"color":{"background":"#eef2ff"},"spacing":{"padding":{"top":"20px","right":"20px","bottom":"20px","left":"20px"}}},"layout":{"type":"constrained"}} -->' . "\n"
      . '<div class="wp-block-group has-background" style="background-color:#eef2ff;padding-top:20px;padding-right:20px;padding-bottom:20px;padding-left:20px"><!-- wp:paragraph -->' . "\n"
      . '<p>Inside</p>' . "\n"
      . '<!-- /wp:paragraph --></div>' . "\n"
      . '<!-- /wp:group -->'
    );
  }

  private function getDraftBlocks(): NewsletterDraftBlocks {
    $wp = Stub::make(WPFunctions::class, [
      'sanitizeTextField' => function (string $text): string {
        return $text;
      },
      'sanitizeHexColor' => function (string $color): string {
        return $color;
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
    return new NewsletterDraftBlocks($wp, Stub::makeEmptyExcept(Shortcodes::class, 'extract'));
  }
}
