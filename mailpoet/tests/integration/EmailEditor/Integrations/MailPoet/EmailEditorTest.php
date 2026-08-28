<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use MailPoet\Entities\NewsletterEntity;

class EmailEditorTest extends \MailPoetTest {
  /** @var EmailEditor */
  private $emailEditor;

  public function _before() {
    $this->emailEditor = $this->diContainer->get(EmailEditor::class);
  }

  public function testItRegistersMailPoetEmailPostType() {
    $this->emailEditor->initialize();
    Email_Editor_Container::container()->get(Email_Editor::class)->initialize();
    $postTypes = get_post_types();
    $this->assertArrayHasKey('mailpoet_email', $postTypes);
  }

  public function testItRendersEditorOnlyOnceWhenReplaceEditorFilterIsReentered() {
    $renderer = $this->createMock(EditorPageRenderer::class);
    $renderer->expects($this->once())->method('render');
    $emailEditor = $this->getServiceWithOverrides(EmailEditor::class, ['editorPageRenderer' => $renderer]);
    $this->setCurrentScreen('post');
    $post = new \WP_Post((object)['post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE]);

    $this->assertTrue($emailEditor->replaceEditor(false, $post));
    // Simulates WP_Screen::get() firing the filter again while render() is printing the admin header
    $this->assertTrue($emailEditor->replaceEditor(false, $post));
  }

  private function setCurrentScreen(string $hookName): void {
    if (!function_exists('set_current_screen')) {
      require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
      require_once ABSPATH . 'wp-admin/includes/screen.php';
    }
    set_current_screen($hookName);
  }

  public function _after() {
    parent::_after();
    if (function_exists('set_current_screen')) {
      set_current_screen('front');
    }
    remove_filter('woocommerce_email_editor_post_types', [$this->emailEditor, 'addEmailPostType']);
    $this->truncateEntity(NewsletterEntity::class);
  }
}
