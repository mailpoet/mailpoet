<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks\Text;

require_once __DIR__ . '/Dummy_Block_Renderer.php';

/**
 * Integration test for Blocks_Registry
 */
class Blocks_Registry_Test extends \MailPoetTest {

	/**
	 * Instance of Blocks_Registry
	 *
	 * @var Blocks_Registry
	 */
	private $registry;

	/**
	 * Set up before each test.
	 */
	public function _before() {
		parent::_before();
		$this->registry = $this->di_container->get( Blocks_Registry::class );
	}

	/**
	 * Test it returns null for unknown renderer.
	 */
	public function testItReturnsNullForUnknownRenderer() {
		$stored_renderer = $this->registry->get_block_renderer( 'test' );
		verify( $stored_renderer )->null();
	}

	/**
	 * Test it stores added renderer.
	 */
	public function testItStoresAddedRenderer() {
		$renderer = new Text();
		$this->registry->add_block_renderer( 'test', $renderer );
		$stored_renderer = $this->registry->get_block_renderer( 'test' );
		verify( $stored_renderer )->equals( $renderer );
	}

	/**
	 * Test it reports which renderers are registered.
	 */
	public function testItReportsWhichRenderersAreRegistered() {
		$renderer = new Text();
		$this->registry->add_block_renderer( 'test', $renderer );
		verify( $this->registry->has_block_renderer( 'test' ) )->true();
		verify( $this->registry->has_block_renderer( 'unknown' ) )->false();
	}
}
