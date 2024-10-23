<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Layout\Flex_Layout_Renderer;
use MailPoet\EmailEditor\Engine\Settings_Controller;

class Buttons extends Abstract_Block_Renderer {
	/** @var Flex_Layout_Renderer */
	private $flexLayoutRenderer;

	public function __construct(
		Flex_Layout_Renderer $flexLayoutRenderer
	) {
		$this->flexLayoutRenderer = $flexLayoutRenderer;
	}

	protected function renderContent( $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		// Ignore font size set on the buttons block
		// We rely on TypographyPreprocessor to set the font size on the buttons
		// Rendering font size on the wrapper causes unwanted whitespace below the buttons
		if ( isset( $parsedBlock['attrs']['style']['typography']['fontSize'] ) ) {
			unset( $parsedBlock['attrs']['style']['typography']['fontSize'] );
		}
		return $this->flexLayoutRenderer->render_inner_blocks_in_layout( $parsedBlock, $settingsController );
	}
}
