<?php

namespace MailPoet\Newsletter\Renderer;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Editor\LayoutHelper;
use MailPoet\Newsletter\Renderer\Blocks\AbandonedCartContent;
use MailPoet\Newsletter\Renderer\Blocks\AutomatedLatestContentBlock;
use MailPoet\Tasks\Sending as SendingTask;
use MailPoet\WooCommerce\TransactionalEmails;

class Preprocessor {
  const WC_HEADING_PLACEHOLDER = '[mailpoet_woocommerce_heading_placeholder]';
  const WC_CONTENT_PLACEHOLDER = '[mailpoet_woocommerce_content_placeholder]';

  const WC_HEADING_BEFORE = '
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing:0;mso-table-lspace:0;mso-table-rspace:0">
            <tr>
              <td class="mailpoet_text" valign="top" style="padding-top:20px;padding-bottom:20px;word-break:break-word;word-wrap:break-word;">';
  const WC_HEADING_AFTER = '
        </td>
      </tr>
    </table>';

  /** @var AbandonedCartContent */
  private $abandonedCartContent;

  /** @var AutomatedLatestContentBlock */
  private $automatedLatestContent;

  /** @var TransactionalEmails */
  private $transactionalEmails;

  public function __construct(
    AbandonedCartContent $abandonedCartContent,
    AutomatedLatestContentBlock $automatedLatestContent,
    TransactionalEmails $transactionalEmails
  ) {
    $this->abandonedCartContent = $abandonedCartContent;
    $this->automatedLatestContent = $automatedLatestContent;
    $this->transactionalEmails = $transactionalEmails;
  }

  /**
   * @param array $content
   * @param NewsletterEntity $newsletter
   * @return array
   */
  public function process(NewsletterEntity $newsletter, $content, bool $preview = false, SendingTask $sendingTask = null) {
    if (!array_key_exists('blocks', $content)) {
      return $content;
    }
    $blocks = [];
    foreach ($content['blocks'] as $block) {
      $processedBlock = $this->processBlock($newsletter, $block, $preview, $sendingTask);
      if (!empty($processedBlock)) {
        $blocks = array_merge($blocks, $processedBlock);
      }
    }
    $content['blocks'] = $blocks;
    return $content;
  }

  public function processBlock(NewsletterEntity $newsletter, array $block, bool $preview = false, SendingTask $sendingTask = null): array {
    switch ($block['type']) {
      case 'abandonedCartContent':
        return $this->abandonedCartContent->render($newsletter, $block, $preview, $sendingTask);
      case 'automatedLatestContentLayout':
        return $this->automatedLatestContent->render($newsletter, $block);
      case 'woocommerceHeading':
        $wcEmailSettings = $this->transactionalEmails->getWCEmailSettings();
        $content = self::WC_HEADING_BEFORE . '<h1 style="color:' . $wcEmailSettings['base_text_color'] . ';">' . self::WC_HEADING_PLACEHOLDER . '</h1>' . self::WC_HEADING_AFTER;
        return $this->placeholder($content, ['backgroundColor' => $wcEmailSettings['base_color']]);
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
