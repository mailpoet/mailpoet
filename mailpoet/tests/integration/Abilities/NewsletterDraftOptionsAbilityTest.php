<?php declare(strict_types = 1);

namespace MailPoet\Test\Abilities;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Test\DataFactories\Newsletter;
use MailPoet\Test\DataFactories\Segment;
use MailPoet\Test\DataFactories\Subscriber;
use MailPoetVendor\Carbon\Carbon;

require_once __DIR__ . '/AbilitiesTestHelper.php';

/**
 * @phpstan-type DraftOptions array{lists: array<int, array<string, mixed>>, site_colors: array<int, array<string, string>>, recent_drafts: array<int, array<string, mixed>>}
 */
class NewsletterDraftOptionsAbilityTest extends \MailPoetTest {
  private const ABILITY = 'mailpoet/get-newsletter-draft-options';

  public function _before(): void {
    AbilitiesTestHelper::enableDraftAbilities();
    wp_set_current_user((int)$this->tester->createWordPressUser('options-editor@example.com', 'editor'));
  }

  public function _after(): void {
    AbilitiesTestHelper::disableDraftAbilities();
    wp_clean_theme_json_cache();
    wp_set_current_user(0);
    $this->tester->deleteCreatedUsers();
    parent::_after();
  }

  public function testItListsSegmentsWithSubscribedCountsAndNoSubscriberData(): void {
    $list = (new Segment())->withName('Weekly readers')->create();
    (new Subscriber())->withEmail('options-reader@example.com')->withSegments([$list])->create();

    $result = $this->execute();

    $lists = array_column($result['lists'], null, 'name');
    verify($lists['Weekly readers'])->equals([
      'id' => (int)$list->getId(),
      'name' => 'Weekly readers',
      'type' => 'default',
      'subscribers' => 1,
    ]);
    verify((string)wp_json_encode($result))->stringNotContainsString('options-reader@example.com');
  }

  public function testItReturnsOnlyTheHexColorsOfTheSitePalette(): void {
    add_filter('wp_theme_json_data_theme', function ($data) {
      return $data->update_with([
        'version' => 3,
        'settings' => ['color' => ['palette' => [
          ['name' => 'Brand', 'slug' => 'brand', 'color' => '#0a7d33'],
          ['name' => 'Variable', 'slug' => 'variable', 'color' => 'var(--brand)'],
        ]]],
      ]);
    });
    wp_clean_theme_json_cache();

    $result = $this->execute();

    verify($result['site_colors'])->equals([['name' => 'Brand', 'color' => '#0a7d33']]);
  }

  public function testItListsOnlyStandardBlockEditorDraftsNewestFirst(): void {
    $newer = $this->createDraft('Newer draft');
    $older = $this->createDraft('Older draft');
    (new Newsletter())->withSubject('Legacy draft')->withDraftStatus()->create();
    $this->createDraft('Automation email')->setType(NewsletterEntity::TYPE_AUTOMATION);
    $this->createDraft('Trashed draft')->setDeletedAt(new Carbon('2026-03-02 10:00:00'));
    $this->createDraft('Sent email')->setStatus(NewsletterEntity::STATUS_SENT);
    $this->entityManager->flush();
    $table = $this->entityManager->getClassMetadata(NewsletterEntity::class)->getTableName();
    $this->entityManager->getConnection()->executeStatement("UPDATE $table SET updated_at = '2026-02-01 10:00:00' WHERE subject = 'Newer draft'");
    $this->entityManager->getConnection()->executeStatement("UPDATE $table SET updated_at = '2026-01-01 10:00:00' WHERE subject = 'Older draft'");
    $this->entityManager->clear();

    $result = $this->execute();

    verify(array_column($result['recent_drafts'], 'id'))->equals([$newer->getId(), $older->getId()]);
    verify($result['recent_drafts'][0]['subject'])->equals('Newer draft');
  }

  public function testItIsRefusedWithoutTheEmailCapability(): void {
    wp_set_current_user((int)$this->tester->createWordPressUser('options-author@example.com', 'author'));

    $ability = wp_get_ability(self::ABILITY);

    $this->assertInstanceOf(\WP_Ability::class, $ability);
    $result = $ability->execute();
    $this->assertInstanceOf(\WP_Error::class, $result);
    verify($result->get_error_code())->equals('ability_invalid_permissions');
  }

  private function createDraft(string $subject): NewsletterEntity {
    $postId = wp_insert_post(['post_type' => 'mailpoet_email', 'post_status' => 'draft', 'post_title' => $subject]);
    return (new Newsletter())->withSubject($subject)->withDraftStatus()->withWpPostId((int)$postId)->create();
  }

  /** @return DraftOptions */
  private function execute(): array {
    $ability = wp_get_ability(self::ABILITY);
    $this->assertInstanceOf(\WP_Ability::class, $ability);
    $result = $ability->execute();
    $this->assertIsArray($result);
    /** @var DraftOptions $result */
    return $result;
  }
}
