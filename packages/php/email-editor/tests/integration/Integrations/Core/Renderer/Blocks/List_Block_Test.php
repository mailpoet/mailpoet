<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Email_Editor;
use MailPoet\EmailEditor\Engine\Settings_Controller;

class List_Block_Test extends \MailPoetTest {
	/** @var List_Block */
	private $listRenderer;

	/** @var array */
	private $parsedList = array(
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

	/** @var Settings_Controller */
	private $settingsController;

	public function _before() {
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->listRenderer       = new List_Block();
		$this->settingsController = $this->di_container->get( Settings_Controller::class );
	}

	public function testItRendersListContent(): void {
		$rendered = $this->listRenderer->render( '<ul><li>Item 1</li><li>Item 2</li></ul>', $this->parsedList, $this->settingsController );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'Item 1', $rendered );
		$this->assertStringContainsString( 'Item 2', $rendered );
	}

	public function testItRendersFontSizeFromPreprocessor(): void {
		$parsedList                = $this->parsedList;
		$parsedList['email_attrs'] = array(
			'font-size' => '20px',
		);
		$rendered                  = $this->listRenderer->render( '<ul><li>Item 1</li><li>Item 2</li></ul>', $parsedList, $this->settingsController );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'Item 1', $rendered );
		$this->assertStringContainsString( 'Item 2', $rendered );
		$this->assertStringContainsString( 'font-size:20px;', $rendered );
	}

	public function testItPreservesCustomSetColors(): void {
		$parsedList = $this->parsedList;
		$rendered   = $this->listRenderer->render( '<ul style="color:#ff0000;background-color:#000000"><li>Item 1</li><li>Item 2</li></ul>', $parsedList, $this->settingsController );
		$this->checkValidHTML( $rendered );
		$this->assertStringContainsString( 'color:#ff0000;', $rendered );
		$this->assertStringContainsString( 'background-color:#000000', $rendered );
	}
}
