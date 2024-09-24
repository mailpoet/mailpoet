<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Settings_Controller;

class List_Item extends Abstract_Block_Renderer {
	/**
	 * Override this method to disable spacing (block gap) for list items.
	 */
	protected function addSpacer( $content, $emailAttrs ): string {
		return $content;
	}

	protected function renderContent( $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		return $blockContent;
	}
}
