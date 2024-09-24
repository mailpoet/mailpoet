<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Postprocessors;

/**
 * This postprocessor replaces <mark> tags with <span> tags because mark tags are not supported across all email clients
 */
class Highlighting_Postprocessor implements Postprocessor {
	public function postprocess( string $html ): string {
		return str_replace(
			array( '<mark', '</mark>' ),
			array( '<span', '</span>' ),
			$html
		);
	}
}
