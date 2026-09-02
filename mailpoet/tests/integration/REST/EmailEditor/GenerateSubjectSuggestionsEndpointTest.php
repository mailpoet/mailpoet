<?php declare(strict_types = 1);

namespace MailPoet\Test\REST\EmailEditor;

use MailPoet\Config\AccessControl;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\REST\Test;

require_once __DIR__ . '/../Test.php';

class GenerateSubjectSuggestionsEndpointTest extends Test {
  private const ENDPOINT = '/mailpoet/v1/email/generate-subject-suggestions';

  public function _after() {
    parent::_after();
    wp_set_current_user(0);
  }

  public function testItRejectsGuests(): void {
    wp_set_current_user(0);

    $data = $this->post(self::ENDPOINT, ['json' => ['post_id' => 1]]);

    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testItRejectsAuthorsEvenForEmailsTheyOwn(): void {
    $authorId = $this->createUserWithRole('author');
    $emailId = $this->createEmailOwnedBy($authorId);
    wp_set_current_user($authorId);

    $data = $this->post(self::ENDPOINT, ['json' => ['post_id' => $emailId]]);

    $this->assertSame('rest_forbidden', $data['code']);
  }

  public function testItLetsUsersWithTheManageEmailsCapabilityPastThePermissionCheck(): void {
    $editorId = $this->createUserWithRole('editor');
    (new \WP_User($editorId))->add_cap(AccessControl::PERMISSION_MANAGE_EMAILS);
    $emailId = $this->createEmailOwnedBy($editorId);
    wp_set_current_user($editorId);

    $data = $this->post(self::ENDPOINT, ['json' => ['post_id' => $emailId]]);

    $this->assertSame('mailpoet_ai_unavailable', $data['code']);
  }

  private function createUserWithRole(string $role): int {
    $suffix = uniqid();
    return (int)$this->tester->createWordPressUser("subject-suggestions-{$role}-{$suffix}@localhost.test", $role);
  }

  private function createEmailOwnedBy(int $userId): int {
    return $this->tester->createPost([
      'post_type' => EmailEditor::MAILPOET_EMAIL_POST_TYPE,
      'post_status' => 'draft',
      'post_author' => $userId,
      'post_title' => 'Subject suggestions email',
      'post_content' => '<!-- wp:paragraph --><p>Big summer sale on garden tools this weekend only.</p><!-- /wp:paragraph -->',
    ])->ID;
  }
}
