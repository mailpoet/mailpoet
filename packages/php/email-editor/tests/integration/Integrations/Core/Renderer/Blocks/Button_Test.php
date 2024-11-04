<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Email_Editor;
use MailPoet\EmailEditor\Engine\Settings_Controller;

/**
 * Integration test for Button class
 */
class Button_Test extends \MailPoetTest {
	/**
	 * Instance of Button class
	 *
	 * @var Button
	 */
	private $button_renderer;

	/**
	 * Configuration for parsed button block
	 *
	 * @var array
	 */
	private $parsed_button = array(
		'blockName'    => 'core/button',
		'attrs'        => array(
			'width' => 50,
			'style' => array(
				'spacing' => array(
					'padding' => array(
						'left'   => '10px',
						'right'  => '10px',
						'top'    => '10px',
						'bottom' => '10px',
					),
				),
				'color'   => array(
					'background' => '#dddddd',
					'text'       => '#111111',
				),
			),
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '<div class="wp-block-button has-custom-width wp-block-button__width-50"><a href="http://example.com" class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" style="color:#111111;background-color:#dddddd;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">Button Text</a></div>',
		'innerContent' => array( '<div class="wp-block-button has-custom-width wp-block-button__width-50"><a href="http://example.com" class="wp-block-button__link has-text-color has-background has-link-color wp-element-button" style="color:#111111;background-color:#dddddd;padding-top:10px;padding-right:10px;padding-bottom:10px;padding-left:10px">Button Text</a></div>' ),
		'email_attrs'  => array(
			'color' => '#111111',
			'width' => '320px',
		),
	);

	/**
	 * Instance of Settings_Controller class
	 *
	 * @var Settings_Controller
	 */
	private $settings_controller;

	/**
	 * Set up before each test
	 */
	public function _before(): void {
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->button_renderer     = new Button();
		$this->settings_controller = $this->di_container->get( Settings_Controller::class );
	}

	/**
	 * Test in renders link
	 */
	public function testItRendersLink(): void {
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		verify( $output )->stringContainsString( 'href="http://example.com"' );
		verify( $output )->stringContainsString( 'Button Text' );
	}

	/**
	 * Test it renders padding based on attributes value
	 */
	public function testItRendersPaddingBasedOnAttributesValue(): void {
		$this->parsed_button['attrs']['style']['spacing']['padding'] = array(
			'left'   => '10px',
			'right'  => '20px',
			'top'    => '30px',
			'bottom' => '40px',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		verify( $output )->stringContainsString( 'padding-left:10px;' );
		verify( $output )->stringContainsString( 'padding-right:20px;' );
		verify( $output )->stringContainsString( 'padding-top:30px;' );
		verify( $output )->stringContainsString( 'padding-bottom:40px;' );
	}

	/**
	 * Test it renders colors
	 */
	public function testItRendersColors(): void {
		$this->parsed_button['attrs']['style']['color'] = array(
			'background' => '#000000',
			'text'       => '#111111',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		verify( $output )->stringContainsString( 'background-color:#000000;' );
		verify( $output )->stringContainsString( 'color:#111111;' );
	}

	/**
	 * Test it renders border
	 */
	public function testItRendersBorder(): void {
		$this->parsed_button['attrs']['style']['border'] = array(
			'width' => '10px',
			'color' => '#111111',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		verify( $output )->stringContainsString( 'border-color:#111111;' );
		verify( $output )->stringContainsString( 'border-width:10px;' );
		verify( $output )->stringContainsString( 'border-style:solid;' );
	}

	/**
	 * Test it renders each side specific border
	 */
	public function testItRendersEachSideSpecificBorder(): void {
		$this->parsed_button['attrs']['style']['border'] = array(
			'top'    => array(
				'width' => '1px',
				'color' => '#111111',
			),
			'right'  => array(
				'width' => '2px',
				'color' => '#222222',
			),
			'bottom' => array(
				'width' => '3px',
				'color' => '#333333',
			),
			'left'   => array(
				'width' => '4px',
				'color' => '#444444',
			),
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		verify( $output )->stringContainsString( 'border-top-width:1px;' );
		verify( $output )->stringContainsString( 'border-top-color:#111111;' );

		verify( $output )->stringContainsString( 'border-right-width:2px;' );
		verify( $output )->stringContainsString( 'border-right-color:#222222;' );

		verify( $output )->stringContainsString( 'border-bottom-width:3px;' );
		verify( $output )->stringContainsString( 'border-bottom-color:#333333;' );

		verify( $output )->stringContainsString( 'border-left-width:4px;' );
		verify( $output )->stringContainsString( 'border-left-color:#444444;' );

		verify( $output )->stringContainsString( 'border-style:solid;' );
	}

	/**
	 * Test it renders border radius
	 */
	public function testItRendersBorderRadius(): void {
		$this->parsed_button['attrs']['style']['border'] = array(
			'radius' => '10px',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		verify( $output )->stringContainsString( 'border-radius:10px;' );
	}

	/**
	 * Test it renders font size
	 */
	public function testItRendersFontSize(): void {
		$this->parsed_button['attrs']['style']['typography']['fontSize'] = '10px';
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		verify( $output )->stringContainsString( 'font-size:10px;' );
	}

	/**
	 * Test in renders corner specific border radius
	 */
	public function testItRendersCornerSpecificBorderRadius(): void {
		$this->parsed_button['attrs']['style']['border']['radius'] = array(
			'topLeft'     => '1px',
			'topRight'    => '2px',
			'bottomLeft'  => '3px',
			'bottomRight' => '4px',
		);
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		verify( $output )->stringContainsString( 'border-top-left-radius:1px;' );
		verify( $output )->stringContainsString( 'border-top-right-radius:2px;' );
		verify( $output )->stringContainsString( 'border-bottom-left-radius:3px;' );
		verify( $output )->stringContainsString( 'border-bottom-right-radius:4px;' );
	}

	/**
	 * Test it renders background color set by slug
	 */
	public function testItRendersBackgroundColorSetBySlug(): void {
		unset( $this->parsed_button['attrs']['style']['color'] );
		unset( $this->parsed_button['attrs']['style']['spacing']['padding'] );
		$this->parsed_button['attrs']['backgroundColor'] = 'black';
		$output = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		// For other blocks this is handled by CSS-inliner, but for button we need to handle it manually
		// because of special email HTML markup.
		verify( $output )->stringContainsString( 'background-color:#000000;' );
	}

	/**
	 * Test if it renders font color set by slug
	 */
	public function testItRendersFontColorSetBySlug(): void {
		unset( $this->parsed_button['attrs']['style']['color'] );
		unset( $this->parsed_button['attrs']['style']['spacing']['padding'] );
		$this->parsed_button['attrs']['textColor'] = 'white';
		$output                                    = $this->button_renderer->render( $this->parsed_button['innerHTML'], $this->parsed_button, $this->settings_controller );
		// For other blocks this is handled by CSS-inliner, but for button we need to handle it manually
		// because of special email HTML markup.
		verify( $output )->stringContainsString( 'color:#fff' );
	}
}
