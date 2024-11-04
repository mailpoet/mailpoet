<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Layout;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Dummy_Block_Renderer;
use MailPoet\EmailEditor\Engine\Settings_Controller;

require_once __DIR__ . '/../Dummy_Block_Renderer.php';

class Flex_Layout_Renderer_Test extends \MailPoetTest {

	/** @var Flex_Layout_Renderer */
	private $renderer;

	/** @var Settings_Controller */
	private $settingsController;

	public function _before(): void {
		parent::_before();
		$this->settingsController = $this->diContainer->get( Settings_Controller::class );
		$this->renderer           = new Flex_Layout_Renderer();
		register_block_type( 'dummy/block', array() );
		add_filter( 'render_block', array( $this, 'renderDummyBlock' ), 10, 2 );
	}

	public function testItRendersInnerBlocks(): void {
		$parsedBlock = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHtml' => 'Dummy 1',
				),
				array(
					'blockName' => 'dummy/block',
					'innerHtml' => 'Dummy 2',
				),
			),
			'email_attrs' => array(),
		);
		$output      = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		verify( $output )->stringContainsString( 'Dummy 1' );
		verify( $output )->stringContainsString( 'Dummy 2' );
	}

	public function testItHandlesJustification(): void {
		$parsedBlock = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHtml' => 'Dummy 1',
				),
			),
			'email_attrs' => array(),
		);
		// Default justification is left
		$output = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		verify( $output )->stringContainsString( 'text-align: left' );
		verify( $output )->stringContainsString( 'align="left"' );
		// Right justification
		$parsedBlock['attrs']['layout']['justifyContent'] = 'right';
		$output = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		verify( $output )->stringContainsString( 'text-align: right' );
		verify( $output )->stringContainsString( 'align="right"' );
		// Center justification
		$parsedBlock['attrs']['layout']['justifyContent'] = 'center';
		$output = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		verify( $output )->stringContainsString( 'text-align: center' );
		verify( $output )->stringContainsString( 'align="center"' );
	}

	public function testItEscapesAttributes(): void {
		$parsedBlock                                      = array(
			'innerBlocks' => array(
				array(
					'blockName' => 'dummy/block',
					'innerHtml' => 'Dummy 1',
				),
			),
			'email_attrs' => array(),
		);
		$parsedBlock['attrs']['layout']['justifyContent'] = '"> <script>alert("XSS")</script><div style="text-align: right';
		$output = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		verify( $output )->stringNotContainsString( '<script>alert("XSS")</script>' );
	}

	public function testInComputesProperWidthsForReasonableSettings(): void {
		$parsedBlock = array(
			'innerBlocks' => array(),
			'email_attrs' => array(
				'width' => '640px',
			),
		);

		// 50% and 25%
		$parsedBlock['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 1',
				'attrs'     => array( 'width' => '50' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 2',
				'attrs'     => array( 'width' => '25' ),
			),
		);
		$output                     = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		$flexItems                  = $this->getFlexItemsFromOutput( $output );
		verify( $flexItems[0] )->stringContainsString( 'width:312px;' );
		verify( $flexItems[1] )->stringContainsString( 'width:148px;' );

		// 25% and 25% and auto
		$parsedBlock['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 1',
				'attrs'     => array( 'width' => '25' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 2',
				'attrs'     => array( 'width' => '25' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 3',
				'attrs'     => array(),
			),
		);
		$output                     = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		$flexItems                  = $this->getFlexItemsFromOutput( $output );
		verify( $flexItems[0] )->stringContainsString( 'width:148px;' );
		verify( $flexItems[1] )->stringContainsString( 'width:148px;' );
		verify( $flexItems[2] )->stringNotContainsString( 'width:' );

		// 50% and 50%
		$parsedBlock['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 1',
				'attrs'     => array( 'width' => '50' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 2',
				'attrs'     => array( 'width' => '50' ),
			),
		);
		$output                     = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		$flexItems                  = $this->getFlexItemsFromOutput( $output );
		verify( $flexItems[0] )->stringContainsString( 'width:312px;' );
		verify( $flexItems[1] )->stringContainsString( 'width:312px;' );
	}

	public function testInComputesWidthsForStrangeSettingsValues(): void {
		$parsedBlock = array(
			'innerBlocks' => array(),
			'email_attrs' => array(
				'width' => '640px',
			),
		);

		// 100% and 25%
		$parsedBlock['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 1',
				'attrs'     => array( 'width' => '100' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 2',
				'attrs'     => array( 'width' => '25' ),
			),
		);
		$output                     = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		$flexItems                  = $this->getFlexItemsFromOutput( $output );
		verify( $flexItems[0] )->stringContainsString( 'width:508px;' );
		verify( $flexItems[1] )->stringContainsString( 'width:105px;' );

		// 100% and 100%
		$parsedBlock['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 1',
				'attrs'     => array( 'width' => '100' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 2',
				'attrs'     => array( 'width' => '100' ),
			),
		);
		$output                     = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		$flexItems                  = $this->getFlexItemsFromOutput( $output );
		verify( $flexItems[0] )->stringContainsString( 'width:312px;' );
		verify( $flexItems[1] )->stringContainsString( 'width:312px;' );

		// 100% and auto
		$parsedBlock['innerBlocks'] = array(
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 1',
				'attrs'     => array( 'width' => '100' ),
			),
			array(
				'blockName' => 'dummy/block',
				'innerHtml' => 'Dummy 2',
				'attrs'     => array(),
			),
		);
		$output                     = $this->renderer->render_inner_blocks_in_layout( $parsedBlock, $this->settingsController );
		$flexItems                  = $this->getFlexItemsFromOutput( $output );
		verify( $flexItems[0] )->stringContainsString( 'width:508px;' );
		verify( $flexItems[1] )->stringNotContainsString( 'width:' );
	}

	private function getFlexItemsFromOutput( string $output ): array {
		$matches = array();
		preg_match_all( '/<td class="layout-flex-item" style="(.*)">/', $output, $matches );
		return explode( '><', $matches[0][0] ?? array() );
	}

	public function renderDummyBlock( $blockContent, $parsedBlock ): string {
		$dummyRenderer = new Dummy_Block_Renderer();
		return $dummyRenderer->render( $blockContent, $parsedBlock, $this->settingsController );
	}

	public function _after(): void {
		parent::_after();
		unregister_block_type( 'dummy/block' );
		remove_filter( 'render_block', array( $this, 'renderDummyBlock' ), 10 );
	}
}
