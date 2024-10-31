<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Patterns\Library;

/**
 * Default content pattern.
 */
class Default_Content_Full extends Abstract_Pattern {
	/**
	 * List of block types.
	 *
	 * @var array $block_types
	 */
	protected $block_types = array(
		'core/post-content',
	);
	/**
	 * List of template types.
	 *
	 * @var string[] $template_types
	 */
	protected $template_types = array(
		'email-general-template',
	);

	/**
	 * Get the content of the pattern.
	 *
	 * @return string
	 */
	protected function get_content(): string {
		return '
    <!-- wp:columns {"backgroundColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"},"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <div class="wp-block-columns has-white-background-color has-background" style="margin-top:var(--wp--preset--spacing--20);margin-bottom:var(--wp--preset--spacing--20);padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:column -->
    <div class="wp-block-column">
    <!-- wp:image {"width":"130px","sizeSlug":"large"} -->
    <figure class="wp-block-image size-large is-resized"><img src="' . esc_url( $this->cdn_asset_url->generate_cdn_url( 'email-editor/your-logo-placeholder.png' ) ) . '" alt="Your Logo" style="width:130px"/></figure>
    <!-- /wp:image -->
    <!-- wp:heading {"fontSize":"medium","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
    <h2 class="wp-block-heading has-medium-font-size" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">' . __( 'One column layout', 'mailpoet' ) . '</h2>
    <!-- /wp:heading -->
    <!-- wp:image {"width":"620px","sizeSlug":"large"} -->
    <figure class="wp-block-image"><img src="' . esc_url( $this->cdn_asset_url->generate_cdn_url( 'newsletter/congratulation-page-illustration-transparent-LQ.20181121-1440.png' ) ) . '" alt="Banner Image"/></figure>
    <!-- /wp:image -->
    <!-- wp:paragraph -->
    <p>' . esc_html__( 'A one-column layout is great for simplified and concise content, like announcements or newsletters with brief updates. Drag blocks to add content and customize your styles from the styles panel on the top right.', 'mailpoet' ) . '</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size">' . esc_html__( 'You received this email because you are subscribed to the [site:title]', 'mailpoet' ) . '</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size"><a href="[link:subscription_unsubscribe_url]">' . esc_html__( 'Unsubscribe', 'mailpoet' ) . '</a> | <a href="[link:subscription_manage_url]">' . esc_html__( 'Manage subscription', 'mailpoet' ) . '</a></p>
    <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
    ';
	}

	/**
	 * Get the title of the pattern.
	 *
	 * @return string
	 */
	protected function get_title(): string {
		return __( 'Default Email Content with Header and Footer', 'mailpoet' );
	}
}
