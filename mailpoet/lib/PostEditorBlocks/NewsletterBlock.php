<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\PostEditorBlocks;

use MailPoet\API\REST\API as RestApi;
use MailPoet\Newsletter\Embed\NewsletterEmbedService;
use MailPoet\Newsletter\Embed\RestApi\Endpoints\NewsletterEmbedSelectorEndpoint;
use MailPoet\WP\Functions as WPFunctions;

class NewsletterBlock {
  /** @var WPFunctions */
  private $wp;

  /** @var NewsletterEmbedService */
  private $newsletterEmbedService;

  /** @var RestApi */
  private $api;

  public function __construct(
    WPFunctions $wp,
    NewsletterEmbedService $newsletterEmbedService,
    RestApi $api
  ) {
    $this->wp = $wp;
    $this->newsletterEmbedService = $newsletterEmbedService;
    $this->api = $api;
  }

  public function init() {
    $this->wp->registerBlockType('mailpoet/newsletter-render', [
      'attributes' => $this->getAttributes(),
      'render_callback' => [$this, 'renderNewsletter'],
    ]);

    $this->wp->addAction(RestApi::REST_API_INIT_ACTION, function(): void {
      $this->api->registerGetRoute('newsletter-embeds', NewsletterEmbedSelectorEndpoint::class);
    });
  }

  public function initAdmin() {
    $this->wp->registerBlockType('mailpoet/newsletter', [
      'style' => 'mailpoetblock-form-block-css',
      'editor_script' => 'mailpoet/subscription-form-block',
      'attributes' => $this->getAttributes(),
      'supports' => [
        'align' => ['wide', 'full'],
      ],
    ]);
  }

  public function initFrontend() {
    $this->wp->registerBlockType('mailpoet/newsletter', [
      'attributes' => $this->getAttributes(),
      'supports' => [
        'align' => ['wide', 'full'],
      ],
      'render_callback' => [$this, 'renderNewsletter'],
    ]);
  }

  public function renderNewsletter(array $attributes = []): string {
    return $this->newsletterEmbedService->render($attributes);
  }

  private function getAttributes(): array {
    return [
      'newsletterId' => [
        'type' => 'number',
        'default' => null,
      ],
      'height' => [
        'type' => 'number',
        'default' => NewsletterEmbedService::DEFAULT_HEIGHT,
      ],
      'showFallbackLink' => [
        'type' => 'boolean',
        'default' => true,
      ],
      'align' => [
        'type' => 'string',
      ],
    ];
  }
}
