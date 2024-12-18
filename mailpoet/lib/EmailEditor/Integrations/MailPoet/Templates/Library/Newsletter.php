<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Templates\Library;

class Newsletter {
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
    return '<!-- wp:group {"backgroundColor":"white","layout":{"type":"constrained"},"lock":{"move":false,"remove":false}} -->
      <div class="wp-block-group has-white-background-color has-background">
        <!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|10","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
        <div
          class="wp-block-group"
          style="
            padding-top: var(--wp--preset--spacing--30);
            padding-right: var(--wp--preset--spacing--20);
            padding-bottom: var(--wp--preset--spacing--10);
            padding-left: var(--wp--preset--spacing--20);
          "
            >
          <!-- wp:image {"width":"130px","sizeSlug":"large"} -->
          <figure class="wp-block-image size-large is-resized">
            <img
              src="https://ps.w.org/mailpoet/assets/email-editor/your-logo-placeholder.png"
              alt="' . __('Your Logo', 'mailpoet') . '"
              style="width: 130px"
                />
          </figure>
          <!-- /wp:image -->
        </div>
        <!-- /wp:group -->
        <!-- wp:post-content {"lock":{"move":false,"remove":false},"layout":{"type":"default"}} /-->
        <!-- wp:group {"style":{"spacing":{"padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20","top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
        <div
          class="wp-block-group"
          style="
            padding-top: var(--wp--preset--spacing--10);
            padding-right: var(--wp--preset--spacing--20);
            padding-bottom: var(--wp--preset--spacing--10);
            padding-left: var(--wp--preset--spacing--20);
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
              >
              ' . __('You received this email because you are subscribed to the [site:title]', 'mailpoet') . '
            <br /><a href="[link:subscription_unsubscribe_url]">' . __('Unsubscribe', 'mailpoet') . '</a> |
            <a href="[link:subscription_manage_url]">' . __('Manage subscription', 'mailpoet') . '</a>
          </p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->
      </div>
      <!-- /wp:group -->';
  }
}
