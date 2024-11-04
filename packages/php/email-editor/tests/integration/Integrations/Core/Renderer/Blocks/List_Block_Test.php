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
 * Integration test for List_Block class
 */
class List_Block_Test extends \MailPoetTest {
	/**
	 * List_Block renderer instance
	 *
	 * @var List_Block
	 */
	private $list_renderer;
	/**
	 * List block configuration
	 *
	 * @var array
	 */
	private $parsed_list = array(
		'blockName'    => 'core/list',
		'attrs'        => array(),
		'innerBlocks'  => array(
			0 => array(
				'blockName'    => 'core/list-item',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '<li>Item 1</li>',
				'innerContent' => array(
					0 => '<li>Item 1</li>',
				),
			),
			1 => array(
				'blockName'    => 'core/list-item',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHTML'    => '<li>Item 2</li>',
				'innerContent' => array(
					0 => '<li>Item 2</li>',
				),
			),
		),
		'innerHTML'    => '<ul></ul>',
		'innerContent' => array(
			0 => '<ul>',
			1 => null,
			2 => '</ul>',
		),
	);
	/**
	 * Settings controller instance
	 *
	 * @var Settings_Controller
	 */
	private $settings_controller;

	/**
	 * Set up before each test
	 */
	public function _before() {
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->list_renderer       = new List_Block();
		$this->settings_controller = $this->di_container->get( Settings_Controller::class );
	}

	/**
	 * Test it renders list content
	 */
	public function testItRendersListContent(): void {
		$rendered = $this->list_renderer->render( '<ul><li>Item 1</li><li>Item 2</li></ul>', $this->parsed_list, $this->settings_controller );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'Item 1', $rendered );
		$this->assertStringContainsString( 'Item 2', $rendered );
	}

	/**
	 * Test it renders font size from preprocessor
	 */
	public function testItRendersFontSizeFromPreprocessor(): void {
		$parsed_list                = $this->parsed_list;
		$parsed_list['email_attrs'] = array(
			'font-size' => '20px',
		);
		$rendered                   = $this->list_renderer->render( '<ul><li>Item 1</li><li>Item 2</li></ul>', $parsed_list, $this->settings_controller );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'Item 1', $rendered );
		$this->assertStringContainsString( 'Item 2', $rendered );
		$this->assertStringContainsString( 'font-size:20px;', $rendered );
	}

	/**
	 * Test it preserves custom set colors
	 */
	public function testItPreservesCustomSetColors(): void {
		$parsed_list = $this->parsed_list;
		$rendered    = $this->list_renderer->render( '<ul style="color:#ff0000;background-color:#000000"><li>Item 1</li><li>Item 2</li></ul>', $parsed_list, $this->settings_controller );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'color:#ff0000;', $rendered );
		$this->assertStringContainsString( 'background-color:#000000', $rendered );
	}
}
