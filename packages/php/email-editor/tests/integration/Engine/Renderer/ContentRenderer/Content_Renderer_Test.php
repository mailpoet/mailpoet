<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\ContentRenderer;

use MailPoet\EmailEditor\Engine\Email_Editor;
use MailPoet\EmailEditor\Integrations\MailPoet\Blocks\BlockTypesController;

require_once __DIR__ . '/Dummy_Block_Renderer.php';

class Content_Renderer_Test extends \MailPoetTest {
	private Content_Renderer $renderer;

	private \WP_Post $emailPost;

	public function _before(): void {
		parent::_before();
		$this->diContainer->get( Email_Editor::class )->initialize();
		$this->diContainer->get( BlockTypesController::class )->initialize();
		$this->renderer  = $this->diContainer->get( Content_Renderer::class );
		$this->emailPost = $this->tester->createPost(
			array(
				'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
			)
		);
	}

	public function testItRendersContent(): void {
		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:core/post-content /-->';
		$content           = $this->renderer->render(
			$this->emailPost,
			$template
		);
		verify( $content )->stringContainsString( 'Hello!' );
	}

	public function testItInlinesContentStyles(): void {
		$template          = new \WP_Block_Template();
		$template->id      = 'template-id';
		$template->content = '<!-- wp:core/post-content /-->';
		$rendered          = $this->renderer->render( $this->emailPost, $template );
		$paragraphStyles   = $this->getStylesValueForTag( $rendered, 'p' );
		verify( $paragraphStyles )->stringContainsString( 'margin: 0' );
		verify( $paragraphStyles )->stringContainsString( 'display: block' );
	}

	private function getStylesValueForTag( $html, $tag ): ?string {
		$html = new \WP_HTML_Tag_Processor( $html );
		if ( $html->next_tag( $tag ) ) {
			return $html->get_attribute( 'style' );
		}
		return null;
	}
}
