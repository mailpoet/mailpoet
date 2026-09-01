<?php declare(strict_types = 1);

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

/**
 * STOMAIL-8313 inserts a tracking opt-out link into the email body at editing
 * time. This proves the link survives rendering into BOTH parts of the message,
 * rather than assuming it: an opt-out link a plain-text reader cannot reach is
 * not an opt-out.
 */
class TrackingOptOutTextPartTest extends \MailPoetTest {
  /** @var Renderer */
  private $renderer;

  public function _before() {
    parent::_before();
    $this->renderer = $this->diContainer->get(Renderer::class);
  }

  public function testItKeepsTheTrackingOptOutLinkInBothTheHtmlAndTheTextPart(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Tracking opt-out link')
      ->withBody($this->bodyWithOptOutLink())
      ->create();

    $rendered = $this->renderer->render($newsletter);
    $this->assertIsArray($rendered);

    // The shortcode survives rendering; it is resolved per subscriber later, in
    // prepareNewsletterForSending().
    verify($rendered['html'])->stringContainsString('subscription_tracking_opt_out_url');
    verify($rendered['text'])->stringContainsString('subscription_tracking_opt_out_url');
    verify($rendered['text'])->stringContainsString('Opt out of tracking');
  }

  public function testItLeavesTheTextPartWithoutTheLinkWhenTheBodyHasNone(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('No tracking opt-out link')
      ->withBody($this->bodyWithoutOptOutLink())
      ->create();

    $rendered = $this->renderer->render($newsletter);
    $this->assertIsArray($rendered);
    verify($rendered['text'])->stringNotContainsString('subscription_tracking_opt_out_url');
  }

  /**
   * STOMAIL-8446. On an email with no footer, the editor's one-click fix builds
   * a footer of its own. It has to build a whole row -- horizontal container,
   * vertical container, footer -- because the renderer skips any top-level
   * block that has no `blocks` of its own, and skips it silently. The first
   * version of that fix added the footer straight to the root, so the canvas
   * showed a footer the subscriber never received.
   */
  public function testItRendersAFooterAddedAsItsOwnRow(): void {
    $newsletter = (new NewsletterFactory())
      ->withSubject('Footer added by the editor')
      ->withBody($this->bodyWithFooterRow())
      ->create();

    $rendered = $this->renderer->render($newsletter);
    $this->assertIsArray($rendered);

    verify($rendered['html'])->stringNotContainsString('Skipped unsupported block');
    verify($rendered['html'])->stringContainsString('subscription_tracking_opt_out_url');
    verify($rendered['html'])->stringContainsString('subscription_unsubscribe_url');
    verify($rendered['text'])->stringContainsString('subscription_tracking_opt_out_url');
    verify($rendered['text'])->stringContainsString('Opt out of tracking');
  }

  /**
   * The trap the test above guards against, stated outright: the root content
   * container holds rows, so a footer put there directly is dropped, and
   * dropped without a word in the log or the UI. This is what makes the shape
   * the editor produces worth asserting rather than assuming.
   */
  public function testItSkipsAFooterPutStraightIntoTheRootContainer(): void {
    $body = $this->body('Hello there.');
    $body['content']['blocks'][] = [
      'type' => 'footer',
      'text' => '<a href="[link:subscription_tracking_opt_out_url]">Opt out of tracking</a>',
      'styles' => ['block' => ['backgroundColor' => 'transparent']],
    ];

    $newsletter = (new NewsletterFactory())
      ->withSubject('Footer in the wrong place')
      ->withBody($body)
      ->create();

    $rendered = $this->renderer->render($newsletter);
    $this->assertIsArray($rendered);

    verify($rendered['html'])->stringContainsString('Skipped unsupported block type: footer');
    verify($rendered['html'])->stringNotContainsString('subscription_tracking_opt_out_url');
  }

  private function bodyWithOptOutLink(): array {
    return $this->body(
      'Hello there.<br /><a href="[link:subscription_tracking_opt_out_url]">Opt out of tracking</a>'
    );
  }

  private function bodyWithoutOptOutLink(): array {
    return $this->body('Hello there.');
  }

  private function body(string $text): array {
    return [
      'content' => [
        'type' => 'container',
        'orientation' => 'vertical',
        'styles' => ['block' => ['backgroundColor' => 'transparent']],
        'blocks' => [
          [
            'type' => 'container',
            'orientation' => 'horizontal',
            'styles' => ['block' => ['backgroundColor' => 'transparent']],
            'blocks' => [
              [
                'type' => 'container',
                'orientation' => 'vertical',
                'styles' => ['block' => ['backgroundColor' => 'transparent']],
                'blocks' => [
                  [
                    'type' => 'text',
                    'text' => $text,
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      'globalStyles' => [
        'text' => ['fontColor' => '#000000', 'fontFamily' => 'Arial', 'fontSize' => '16px'],
        'h1' => ['fontColor' => '#111111', 'fontFamily' => 'Arial', 'fontSize' => '36px'],
        'h2' => ['fontColor' => '#222222', 'fontFamily' => 'Arial', 'fontSize' => '28px'],
        'h3' => ['fontColor' => '#333333', 'fontFamily' => 'Arial', 'fontSize' => '22px'],
        'link' => ['fontColor' => '#21759B', 'textDecoration' => 'underline'],
        'wrapper' => ['backgroundColor' => '#ffffff'],
        'body' => ['backgroundColor' => '#eeeeee'],
      ],
    ];
  }

  /**
   * A text row followed by the footer row the editor adds when the email has no
   * footer: the same row -> column -> block nesting every layout widget builds.
   */
  private function bodyWithFooterRow(): array {
    $body = $this->body('Hello there.');
    $body['content']['blocks'][] = [
      'type' => 'container',
      'orientation' => 'horizontal',
      'styles' => ['block' => ['backgroundColor' => 'transparent']],
      'blocks' => [
        [
          'type' => 'container',
          'orientation' => 'vertical',
          'styles' => ['block' => ['backgroundColor' => 'transparent']],
          'blocks' => [
            [
              'type' => 'footer',
              'text' => '<a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | '
                . '<a href="[link:subscription_manage_url]">Manage subscription</a>'
                . '<br /><a href="[link:subscription_tracking_opt_out_url]">Opt out of tracking</a>',
              'styles' => [
                'block' => ['backgroundColor' => 'transparent'],
                'text' => ['fontColor' => '#000000', 'fontFamily' => 'Arial', 'fontSize' => '12px', 'textAlign' => 'center'],
                'link' => ['fontColor' => '#0000ff', 'textDecoration' => 'none'],
              ],
            ],
          ],
        ],
      ],
    ];
    return $body;
  }
}
