<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Templates\Library;

use MailPoet\WP\Functions as WPFunctions;

class Newsletter {
  private WPFunctions $wp;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function getSlug(): string {
    return 'newsletter';
  }

  public function getTitle(): string {
    return __('Newsletter', 'mailpoet');
  }

  public function getDescription(): string {
    return __('Newsletter', 'mailpoet');
  }

  public function getContent(): string {
    // translators: This is a text used in a footer on an email <!--[mailpoet/site-title]--> will be replaced with the site title.
    $footerText = __('You received this email because you are subscribed to the <!--[mailpoet/site-title]-->', 'mailpoet');
    $siteIdentityBlock = $this->getSiteIdentityBlock();
    return '<!-- wp:group {"backgroundColor":"white","layout":{"type":"constrained"},"lock":{"move":false,"remove":true}} -->
      <div class="wp-block-group has-white-background-color has-background">
        <!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|10","left":"var:preset|spacing|40","right":"var:preset|spacing|20"}}}} -->
        <div
          class="wp-block-group"
          style="
            padding-top: var(--wp--preset--spacing--30);
            padding-right: var(--wp--preset--spacing--20);
            padding-bottom: var(--wp--preset--spacing--10);
            padding-left: var(--wp--preset--spacing--40);
          "
            >
          ' . $siteIdentityBlock . '
        </div>
        <!-- /wp:group -->
        <!-- wp:post-content {"lock":{"move":false,"remove":true},"layout":{"type":"default"}} /-->
        <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|40","left":"var:preset|spacing|40","top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
        <div
          class="wp-block-group"
          style="
            padding-top: var(--wp--preset--spacing--10);
            padding-right: var(--wp--preset--spacing--40);
            padding-bottom: var(--wp--preset--spacing--10);
            padding-left: var(--wp--preset--spacing--40);
          "
            >
          <!-- wp:paragraph {"align":"center","fontSize":"small","style":{"border":{"top":{"color":"var:preset|color|cyan-bluish-gray","width":"1px","style":"solid"},"right":{},"bottom":{},"left":{}},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}},"color":{"text":"#787c82"},"elements":{"link":{"color":{"text":"#787c82"}}}}} -->
          <p
            class="has-text-align-center has-text-color has-link-color has-small-font-size"
            style="
              border-top-color: var(--wp--preset--color--cyan-bluish-gray);
              border-top-style: solid;
              border-top-width: 1px;
              color: #787c82;
              padding-top: var(--wp--preset--spacing--20);
              padding-bottom: var(--wp--preset--spacing--20);
            "
              >' . $footerText . '<br /><a data-link-href="[mailpoet/subscription-unsubscribe-url]" contenteditable="false" style="text-decoration: underline;" class="mailpoet-email-editor__personalization-tags-link">' . __('Unsubscribe', 'mailpoet') . '</a> | <a data-link-href="[mailpoet/subscription-manage-url]" contenteditable="false" style="text-decoration: underline;" class="mailpoet-email-editor__personalization-tags-link">' . __('Manage subscription', 'mailpoet') . '</a>
          </p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->
        <!-- wp:mailpoet/powered-by-mailpoet {"lock":{"move":true,"remove":true}} /-->
      </div>
      <!-- /wp:group -->';
  }

  private function getSiteIdentityBlock(): string {
    if ($this->wp->hasCustomLogo()) {
      return '<!-- wp:site-logo {"width":130,"isLink":false,"align":"center"} /-->';
    }

    return '<!-- wp:site-title {"level":2,"textAlign":"center","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} /-->';
  }
}
