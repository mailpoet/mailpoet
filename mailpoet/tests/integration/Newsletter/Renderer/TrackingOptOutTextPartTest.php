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
}
