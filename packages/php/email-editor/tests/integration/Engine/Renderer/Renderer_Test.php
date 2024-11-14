<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Email_Editor;
use MailPoet\EmailEditor\Engine\Settings_Controller;
use MailPoet\EmailEditor\Engine\Templates\Templates;
use MailPoet\EmailEditor\Engine\Templates\Utils;
use MailPoet\EmailEditor\Engine\Theme_Controller;

/**
 * Integration test for Renderer
 */
class Renderer_Test extends \MailPoetTest {
	/**
	 * The renderer.
	 *
	 * @var Renderer
	 */
	private Renderer $renderer;
	/**
	 * The email post.
	 *
	 * @var \WP_Post
	 */
	private \WP_Post $email_post;

	/**
	 * Set up before each test.
	 */
	public function _before(): void {
		parent::_before();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->renderer  = $this->di_container->get( Renderer::class );
		$styles          = array(
			'spacing'    => array(
				'padding' => array(
					'bottom' => '4px',
					'top'    => '3px',
					'left'   => '2px',
					'right'  => '1px',
				),
			),
			'typography' => array(
				'fontFamily' => 'Test Font Family',
			),
			'color'      => array(
				'background' => '#123456',
			),
		);
		$theme_json_mock = $this->createMock( \WP_Theme_JSON::class );
		$theme_json_mock->method( 'get_data' )->willReturn(
			array(
				'styles' => $styles,
			)
		);
		$settings_controller_mock = $this->createMock( Settings_Controller::class );
		$settings_controller_mock->method( 'get_email_styles' )->willReturn( $styles );
		$theme_controller_mock = $this->createMock( Theme_Controller::class );
		$theme_controller_mock->method( 'get_theme' )->willReturn( $theme_json_mock );
		$theme_controller_mock->method( 'get_styles' )->willReturn( $styles );
		$theme_controller_mock->method( 'get_layout_settings' )->willReturn( array( 'contentSize' => '660px' ) );

		// We need to mock only the get_block_template_theme method and templates need to be initialized.
		$templates_mock = $this->getMockBuilder( Templates::class )
			->setConstructorArgs( array( $this->di_container->get( Utils::class ) ) )
			->onlyMethods( array( 'get_block_template_theme' ) )
			->getMock();
		$templates_mock->initialize();

		$templates_mock->method( 'get_block_template_theme' )->willReturn(
			array(
				'version' => 3,
				'styles'  => array(
					'elements' => array(
						'h1'      => array(
							'typography' => array(
								'fontFamily' => 'lato test',
							),
						),
						'heading' => array(
							'color' => array(
								'background' => 'pale-pink',
							),
						),
					),
				),
			)
		);

		$this->renderer   = $this->getServiceWithOverrides(
			Renderer::class,
			array(
				'settings_controller' => $settings_controller_mock,
				'theme_controller'    => $theme_controller_mock,
				'templates'           => $templates_mock,
			)
		);
		$this->email_post = $this->tester->create_post(
			array(
				'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
			)
		);
	}

	/**
	 * Test it renders template with content.
	 */
	public function testItRendersTemplateWithContent(): void {
		$rendered = $this->renderer->render(
			$this->email_post,
			'Subject',
			'Preheader content',
			'en',
			'noindex,nofollow'
		);
		verify( $rendered['html'] )->stringContainsString( 'Subject' );
		verify( $rendered['html'] )->stringContainsString( 'Preheader content' );
		verify( $rendered['html'] )->stringContainsString( 'noindex,nofollow' );
		verify( $rendered['html'] )->stringContainsString( 'Hello!' );

		verify( $rendered['text'] )->stringContainsString( 'Preheader content' );
		verify( $rendered['text'] )->stringContainsString( 'Hello!' );
	}

	/**
	 * Test it inlines styles.
	 */
	public function testItInlinesStyles(): void {
		$styles_callback = function ( $styles ) {
			return $styles . 'body { color: pink; }';
		};
		add_filter( 'mailpoet_email_renderer_styles', $styles_callback );
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );
		$style    = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		verify( $style )->stringContainsString( 'color: pink' );
		remove_filter( 'mailpoet_email_renderer_styles', $styles_callback );
	}

	/**
	 * Test it inlines body styles.
	 */
	public function testItInlinesBodyStyles(): void {
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );
		$style    = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		verify( $style )->stringContainsString( 'margin: 0; padding: 0;' );
	}

	/**
	 * Test it inlines wrapper styles.
	 */
	public function testItInlinesWrappersStyles(): void {
		$rendered = $this->renderer->render( $this->email_post, 'Subject', '', 'en' );

		// Verify body element styles.
		$style = $this->getStylesValueForTag( $rendered['html'], array( 'tag_name' => 'body' ) );
		verify( $style )->stringContainsString( 'background-color: #123456' );

		// Verify layout element styles.
		$doc = new \DOMDocument();
		$doc->loadHTML( $rendered['html'] );
		$xpath   = new \DOMXPath( $doc );
		$wrapper = null;
		$nodes   = $xpath->query( '//div[contains(@class, "email_layout_wrapper")]' );
		if ( ( $nodes instanceof \DOMNodeList ) && $nodes->length > 0 ) {
			$wrapper = $nodes->item( 0 );
		}
		$this->assertInstanceOf( \DOMElement::class, $wrapper );
		$style = $wrapper->getAttribute( 'style' );
		verify( $style )->stringContainsString( 'background-color: #123456' );
		verify( $style )->stringContainsString( 'font-family: Test Font Family;' );
		verify( $style )->stringContainsString( 'padding-top: 3px;' );
		verify( $style )->stringContainsString( 'padding-bottom: 4px;' );
		verify( $style )->stringContainsString( 'padding-left: 2px;' );
		verify( $style )->stringContainsString( 'padding-right: 1px;' );
		verify( $style )->stringContainsString( 'max-width: 660px;' );
	}

	/**
	 * Test it inlines block styles from template.
	 */
	public function testItRendersTemplateStyles(): void {
		$email    = $this->tester->create_post(
			array(
				'post_content' => '<!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Hello!</h1><!-- /wp:heading -->',
			)
		);
		$rendered = $this->renderer->render(
			$email,
			'Subject 1',
			'Preheader content 2',
			'en',
			'noindex,nofollow'
		);

		verify( $rendered['html'] )->stringContainsString( 'Subject' );
		verify( $rendered['html'] )->stringContainsString( 'font-family: lato test;' );
		verify( $rendered['html'] )->stringContainsString( ' background-color: pale-pink;' );
	}

	/**
	 * Returns the value of the style attribute for the first tag that matches the query.
	 *
	 * @param string $html HTML content.
	 * @param array  $query Query to find the tag.
	 * @return string|null
	 */
	private function getStylesValueForTag( string $html, array $query ): ?string {
		$html = new \WP_HTML_Tag_Processor( $html );
		if ( $html->next_tag( $query ) ) {
			return $html->get_attribute( 'style' );
		}
		return null;
	}
}
