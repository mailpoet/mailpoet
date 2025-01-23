<?php
/**
 * This file is part of the MailPoet Email Editor package.
 * Template canvas file to render the emails custom post type.
 *
 * @package MailPoet\EmailEditor
 */

// get the rendered post HTML content.
$template_html = apply_filters( 'mailpoet_email_editor_preview_post_template_html', get_post() );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $template_html;
