<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Email_Editor;
use MailPoet\EmailEditor\Engine\Settings_Controller;

class Heading_Test extends \MailPoetTest {
	/** @var Text */
	private $headingRenderer;

	/** @var array */
	private $parsedHeading = array(
		'blockName'    => 'core/heading',
		'attrs'        => array(
			'level'           => 1,
			'backgroundColor' => 'vivid-red',
			'textColor'       => 'pale-cyan-blue',
			'textAlign'       => 'center',
			'style'           => array(
				'typography' => array(
					'textTransform' => 'lowercase',
					'fontSize'      => '24px',
				),
			),
		),
		'email_attrs'  => array(
			'width' => '640px',
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '<h1 class="has-pale-cyan-blue-color has-vivid-red-background-color has-text-color has-background">This is Heading 1</h1>',
		'innerContent' => array(
			0 => '<h1 class="has-pale-cyan-blue-color has-vivid-red-background-color has-text-color has-background">This is Heading 1</h1>',
		),
	);

	/** @var Settings_Controller */
	private $settingsController;

	public function _before() {
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->headingRenderer    = new Text();
		$this->settingsController = $this->di_container->get( Settings_Controller::class );
	}

	public function testItRendersContent(): void {
		$rendered = $this->headingRenderer->render( '<h1>This is Heading 1</h1>', $this->parsedHeading, $this->settingsController );
		verify( $rendered )->stringContainsString( 'This is Heading 1' );
		verify( $rendered )->stringContainsString( 'width:100%;' );
		verify( $rendered )->stringContainsString( 'font-size:24px;' );
		verify( $rendered )->stringNotContainsString( 'width:640px;' );
	}

	public function testItRendersBlockAttributes(): void {
		$rendered = $this->headingRenderer->render( '<h1>This is Heading 1</h1>', $this->parsedHeading, $this->settingsController );
		verify( $rendered )->stringContainsString( 'text-transform:lowercase;' );
		verify( $rendered )->stringContainsString( 'text-align:center;' );
	}

	public function testItRendersCustomSetColors(): void {
		$this->parsedHeading['attrs']['style']['color']['background'] = '#000000';
		$this->parsedHeading['attrs']['style']['color']['text']       = '#ff0000';
		$rendered = $this->headingRenderer->render( '<h1>This is Heading 1</h1>', $this->parsedHeading, $this->settingsController );
		verify( $rendered )->stringContainsString( 'background-color:#000000' );
		verify( $rendered )->stringContainsString( 'color:#ff0000;' );
	}

	public function testItReplacesFluidFontSizeInContent(): void {
		$rendered = $this->headingRenderer->render( '<h1 style="font-size:clamp(10px, 20px, 24px)">This is Heading 1</h1>', $this->parsedHeading, $this->settingsController );
		verify( $rendered )->stringContainsString( 'font-size:24px' );
	}
}
