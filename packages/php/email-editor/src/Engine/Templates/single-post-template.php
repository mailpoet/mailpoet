<?php
/**
 * This file is part of the MailPoet plugin.
 * Template canvas file to render the emails custom post type.
 *
 * @package MailPoet\EmailEditor
 */

use MailPoet\DI\ContainerWrapper;
use MailPoet\EmailEditor\Engine\Renderer\Renderer;

/***
 * Generate html content for email editor post.
 *
 * @return string
 * @throws \Exception If the post is invalid.
 */
function mee_get_rendered_html(): string {
	$post = get_post();

	if ( ! $post instanceof \WP_Post ) {
		throw new \Exception( esc_html__( 'Invalid post', 'mailpoet' ) );
	}

	$subject  = $post->post_title;
	$language = get_bloginfo( 'language' );

	$renderer = ContainerWrapper::getInstance()->get( Renderer::class ); // TODO: Update implementation.

	$rendered_data = $renderer->render(
		$post,
		$subject,
		__( 'Preview', 'mailpoet' ),
		$language
	);

	return $rendered_data['html'];
}


$template_html = mee_get_rendered_html();

// We control those templates.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $template_html;
