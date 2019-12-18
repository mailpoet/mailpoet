<?php

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Newsletter\Editor\LayoutHelper;
use MailPoet\Newsletter\Renderer\Blocks\Renderer as BlocksRenderer;
use MailPoet\WooCommerce\TransactionalEmails;

class Preprocessor {
  const WC_HEADING_PLACEHOLDER = '[mailpet_woocommerce_heading_placeholder]';
  const WC_CONTENT_PLACEHOLDER = '[mailpet_woocommerce_content_placeholder]';

  const WC_HEADING_BEFORE = '
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
            <tr>
              <td class="mailpoet_text" valign="top" style="padding-top:20px;padding-bottom:20px;word-break:break-word;word-wrap:break-word;">';
  const WC_HEADING_AFTER = '
        </td>
      </tr>
    </table>';

  /** @var BlocksRenderer */
  private $blocks_renderer;

  /** @var TransactionalEmails */
  private $transactional_emails;

  public function __construct(BlocksRenderer $blocks_renderer, TransactionalEmails $transactional_emails) {
    $this->blocks_renderer = $blocks_renderer;
    $this->transactional_emails = $transactional_emails;
  }

  /**
   * @param array $content
   * @return array
   */
  public function process($content) {
    if (!array_key_exists('blocks', $content)) {
      return $content;
    }
    $blocks = [];
    foreach ($content['blocks'] as $block) {
      $blocks = array_merge($blocks, $this->processBlock($block));
    }
    $content['blocks'] = $blocks;
    return $content;
  }

    /**
   * @param array $block
   * @return array
   */
  public function processBlock($block) {
    switch ($block['type']) {
      case 'automatedLatestContentLayout':
        return $this->blocks_renderer->automatedLatestContentTransformedPosts($block);
      case 'woocommerceHeading':
        $wc_email_settings = $this->transactional_emails->getWCEmailSettings();
        $content = self::WC_HEADING_BEFORE . '<h1 style="color:' . $wc_email_settings['base_text_color'] . ';">' . self::WC_HEADING_PLACEHOLDER . '</h1>' . self::WC_HEADING_AFTER;
        return $this->placeholder($content, ['backgroundColor' => $wc_email_settings['base_color']]);
      case 'woocommerceContent':
        return $this->placeholder(self::WC_CONTENT_PLACEHOLDER);
    }
    return [$block];
  }

  /**
   * @param string $text
   * @return array
   */
  private function placeholder($text, $styles = []) {
    return [
      LayoutHelper::row([
        LayoutHelper::col([[
          'type' => 'text',
          'text' => $text,
        ]]),
        ], $styles),
    ];
  }
}