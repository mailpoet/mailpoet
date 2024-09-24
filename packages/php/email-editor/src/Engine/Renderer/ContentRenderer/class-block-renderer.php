<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\Settings_Controller;

interface Block_Renderer {
	public function render( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string;
}
