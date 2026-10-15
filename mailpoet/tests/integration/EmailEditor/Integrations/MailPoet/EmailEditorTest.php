<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class EmailEditorTest extends \MailPoetTest {
  /** @var EmailEditor */
  private $emailEditor;

  /** @var int[] */
  private $wpPostIds = [];

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
    $emailEditor = $this->getServiceWithOverrides(EmailEditor::class, ['editorPageRenderer' => $renderer]);
    $this->setCurrentScreen('post');
    $post = new \WP_Post((object)['post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE]);

    // Simulates WP_Screen::get() firing the filter again while render() is printing the admin header
    $nestedResult = null;
    $renderer->expects($this->once())->method('render')->willReturnCallback(
      function () use (&$nestedResult, $emailEditor, $post) {
        $nestedResult = $emailEditor->replaceEditor(false, $post);
      }
    );

    $this->assertTrue($emailEditor->replaceEditor(false, $post));
    $this->assertTrue($nestedResult);
  }

  public function testSavingOnlyEmailContentMarksNewsletterAsUpdated() {
    $this->emailEditor->initialize();
    Email_Editor_Container::container()->get(Email_Editor::class)->initialize();
    wp_set_current_user(1);
    $wpPostId = wp_insert_post([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'draft',
      'post_content' => '<!-- wp:paragraph --><p>Old content</p><!-- /wp:paragraph -->',
    ]);
    $this->assertIsInt($wpPostId);
    $this->wpPostIds[] = $wpPostId;
    $newsletter = (new NewsletterFactory())->withWpPostId($wpPostId)->create();
    $oldUpdatedAt = new \DateTimeImmutable('2020-01-01 00:00:00');
    $this->setUpdatedAtForEntity($newsletter, $oldUpdatedAt);

    $request = new \WP_REST_Request('PUT', '/wp/v2/' . EmailEditor::MAILPOET_EMAIL_POST_TYPE . '/' . $wpPostId);
    $request->set_body_params(['content' => '<!-- wp:paragraph --><p>New content</p><!-- /wp:paragraph -->']);
    $response = $this->getRestServer()->dispatch($request);
    $this->assertSame(200, $response->get_status());

    $this->entityManager->clear();
    $newsletter = $this->entityManager->find(NewsletterEntity::class, $newsletter->getId());
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    $updatedAt = $newsletter->getUpdatedAt();
    $this->assertInstanceOf(\DateTimeInterface::class, $updatedAt);
    $this->assertGreaterThan($oldUpdatedAt->getTimestamp(), $updatedAt->getTimestamp());
  }

  private function getRestServer(): \WP_REST_Server {
    global $wp_rest_server;
    $wp_rest_server = new \WP_REST_Server();
    do_action('rest_api_init', $wp_rest_server);
    return $wp_rest_server;
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
    global $wp_rest_server;
    $wp_rest_server = null;
    foreach ($this->wpPostIds as $wpPostId) {
      wp_delete_post($wpPostId, true);
    }
  }
}
