<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypes;

class EmailContent extends AbstractBlock {

    /**
     * Block name.
     *
     * @var string
     */
    protected $blockName = 'email-content';

    /**
     * Render the block.
     *
     * @param array    $attributes Block attributes.
     * @param string   $content Block content.
     * @param \WP_Block $block Block instance.
     *
     * @return string | void Rendered block output.
     */
  protected function render($attributes, $content, $block) {
    global $post;

    /** This filter is documented in wp-includes/post-template.php */
    $content = apply_filters('the_content', str_replace(']]>', ']]&gt;', $post->post_content)); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

    return sprintf(
      '<div class="%1$s">%2$s</div>',
      esc_attr('wp-block-' . $this->blockName),
      $content
    );
  }
}
