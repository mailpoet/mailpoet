<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\PersonalizationTags;

use MailPoet\EmailEditor\Engine\PersonalizationTags\HTML_Tag_Processor;

/**
 * Converts link tags to shortcodes.
 *
 * This is a temporary solution so that we are able to integrate the new personalization tags
 * with the MailPoet Link tracking system which is based on shortcodes.
 *
 */
class LinksToShortcodesConvertor {
  private const TOKEN_MAP = [
    '[mailpoet/subscription-unsubscribe-url]' => '[link:subscription_unsubscribe_url]',
    '[mailpoet/subscription-manage-url]' => '[link:subscription_manage_url]',
    '[mailpoet/newsletter-view-in-browser-url]' => '[link:newsletter_view_in_browser_url]',
  ];

  public function convertLinkTagsToShortcodes(string $content): string {
    $contentProcessor = new HTML_Tag_Processor($content);
    while ($contentProcessor->next_token()) {
      if ($contentProcessor->get_token_type() === '#tag' && $contentProcessor->get_tag() === 'A' && $contentProcessor->get_attribute('data-link-href')) {
        $href = $contentProcessor->get_attribute('data-link-href');
        if (!isset(self::TOKEN_MAP[$href])) {
          continue;
        }
        $contentProcessor->set_attribute('href', 'http://' . self::TOKEN_MAP[$href]);
        $contentProcessor->remove_attribute('data-link-href');
        $contentProcessor->remove_attribute('contenteditable');
      }
    }
    $contentProcessor->flush_updates();
    $updated = $contentProcessor->get_updated_html();
    // Remove temporary prefix. It was needed so hat the HTML_Tag_Processor could add value to href.
    $updated = str_replace('http://[', '[', $updated);
    return $updated;
  }
}
