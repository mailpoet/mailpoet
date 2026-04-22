<?php declare(strict_types = 1);

namespace MailPoet\Test\API\MP;

use MailPoet\API\MP\v1\API;
use MailPoet\API\MP\v1\APIException;
use MailPoet\Tags\TagRepository;
use MailPoet\Test\DataFactories\Subscriber as SubscriberFactory;
use MailPoet\Test\DataFactories\Tag as TagFactory;

class TagsTest extends \MailPoetTest {
  /** @var TagRepository */
  private $tagRepository;

  public function _before(): void {
    parent::_before();
    $this->tagRepository = $this->diContainer->get(TagRepository::class);
  }

  public function testItGetsAllTags(): void {
    $tag1 = (new TagFactory())->withName('Tag 1')->create();
    $tag2 = (new TagFactory())->withName('Tag 2')->create();

    $result = $this->getApi()->getTags();

    $this->assertCount(2, $result);
    $this->assertEquals((string)$tag1->getId(), $result[0]['id']);
    $this->assertEquals($tag1->getName(), $result[0]['name']);
    $this->assertEquals((string)$tag2->getId(), $result[1]['id']);
  }

  public function testItGetsTagById(): void {
    $tag = (new TagFactory())->withName('My tag')->create();

    $result = $this->getApi()->getTag((int)$tag->getId());

    $this->assertEquals((string)$tag->getId(), $result['id']);
    $this->assertEquals('My tag', $result['name']);
  }

  public function testItGetsTagByName(): void {
    $tag = (new TagFactory())->withName('By name')->create();

    $result = $this->getApi()->getTag('By name');

    $this->assertEquals((string)$tag->getId(), $result['id']);
  }

  public function testItGetsTagByNumericNameWhenIdLookupFails(): void {
    $tag = (new TagFactory())->withName('2026')->create();

    // numeric string "2026" is a valid name; there is no tag with id 2026 so
    // the ID lookup must fall back to a name lookup
    $result = $this->getApi()->getTag('2026');

    $this->assertEquals((string)$tag->getId(), $result['id']);
    $this->assertEquals('2026', $result['name']);
  }

  public function testItThrowsWhenGettingMissingTag(): void {
    try {
      $this->getApi()->getTag('missing');
      $this->fail('Tag not exists exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getMessage())->equals('The tag does not exist.');
      verify($e->getCode())->equals(APIException::TAG_NOT_EXISTS);
    }
  }

  public function testItRequiresNameToAddTag(): void {
    try {
      $this->getApi()->addTag([]);
      $this->fail('Tag name required exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getMessage())->equals('Tag name is required.');
      verify($e->getCode())->equals(APIException::TAG_NAME_REQUIRED);
    }
  }

  public function testItDoesNotAddExistingTag(): void {
    (new TagFactory())->withName('Taken')->create();
    try {
      $this->getApi()->addTag(['name' => 'Taken']);
      $this->fail('Tag exists exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getMessage())->equals('This tag already exists.');
      verify($e->getCode())->equals(APIException::TAG_EXISTS);
    }
  }

  public function testItDoesNotAddTagWhenSanitizedNameDuplicatesExisting(): void {
    (new TagFactory())->withName('VIP')->create();

    // raw "  VIP  " sanitizes to "VIP" which already exists; uniqueness must
    // be checked after sanitization
    try {
      $this->getApi()->addTag(['name' => '  VIP  ']);
      $this->fail('Tag exists exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getCode())->equals(APIException::TAG_EXISTS);
    }
  }

  public function testItDoesNotAddTagWhenSanitizedNameIsEmpty(): void {
    // "<script>" is non-empty but sanitize_text_field strips it to ""; the
    // empty check must run on the sanitized value
    try {
      $this->getApi()->addTag(['name' => '<script>']);
      $this->fail('Tag name required exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getCode())->equals(APIException::TAG_NAME_REQUIRED);
    }

    verify($this->tagRepository->findOneBy(['name' => '']))->null();
  }

  public function testItAddsTag(): void {
    $result = $this->getApi()->addTag([
      'name' => 'Fresh tag',
      'description' => 'Explained here',
    ]);

    verify($result['id'])->greaterThan(0);
    verify($result['name'])->equals('Fresh tag');
    verify($result['description'])->equals('Explained here');
  }

  public function testItRequiresIdToUpdateTag(): void {
    try {
      $this->getApi()->updateTag(['name' => 'Anything']);
      $this->fail('Tag id required exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getMessage())->equals('Tag id is required.');
      verify($e->getCode())->equals(APIException::TAG_ID_REQUIRED);
    }
  }

  public function testItChecksTagExistenceForUpdateTag(): void {
    try {
      $this->getApi()->updateTag(['id' => 999999, 'name' => 'Any']);
      $this->fail('Tag not exists exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getMessage())->equals('The tag does not exist.');
      verify($e->getCode())->equals(APIException::TAG_NOT_EXISTS);
    }
  }

  public function testItDoesNotAllowUpdateToDuplicateName(): void {
    $tag1 = (new TagFactory())->withName('Tag 1')->create();
    (new TagFactory())->withName('Tag 2')->create();

    try {
      $this->getApi()->updateTag(['id' => $tag1->getId(), 'name' => 'Tag 2']);
      $this->fail('Tag exists exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getCode())->equals(APIException::TAG_EXISTS);
    }
  }

  public function testItDoesNotAllowUpdateWhenSanitizedNameDuplicatesExisting(): void {
    $tag1 = (new TagFactory())->withName('Tag 1')->create();
    (new TagFactory())->withName('VIP')->create();

    // raw "  VIP  " sanitizes to "VIP" which is taken by another tag
    try {
      $this->getApi()->updateTag(['id' => $tag1->getId(), 'name' => '  VIP  ']);
      $this->fail('Tag exists exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getCode())->equals(APIException::TAG_EXISTS);
    }
  }

  public function testItDoesNotAllowUpdateWhenSanitizedNameIsEmpty(): void {
    $tag = (new TagFactory())->withName('Original')->create();

    try {
      $this->getApi()->updateTag(['id' => $tag->getId(), 'name' => '<script>']);
      $this->fail('Tag name required exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getCode())->equals(APIException::TAG_NAME_REQUIRED);
    }

    $this->entityManager->clear();
    $reloaded = $this->tagRepository->findOneById((int)$tag->getId());
    $this->assertInstanceOf(\MailPoet\Entities\TagEntity::class, $reloaded);
    verify($reloaded->getName())->equals('Original');
  }

  public function testItUpdatesTag(): void {
    $tag = (new TagFactory())->withName('Old name')->create();

    $result = $this->getApi()->updateTag([
      'id' => (string)$tag->getId(),
      'name' => 'New name',
      'description' => 'New description',
    ]);

    verify($result['id'])->equals((string)$tag->getId());
    verify($result['name'])->equals('New name');
    verify($result['description'])->equals('New description');
  }

  public function testItRequiresIdToDeleteTag(): void {
    try {
      $this->getApi()->deleteTag('');
      $this->fail('Tag id required exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getCode())->equals(APIException::TAG_ID_REQUIRED);
    }
  }

  public function testItChecksTagExistenceForDeleteTag(): void {
    try {
      $this->getApi()->deleteTag('999999');
      $this->fail('Tag not exists exception should have been thrown.');
    } catch (APIException $e) {
      verify($e->getCode())->equals(APIException::TAG_NOT_EXISTS);
    }
  }

  public function testItDeletesTagAndCascadesSubscriberAssociations(): void {
    $tag = (new TagFactory())->withName('To delete')->create();
    $subscriber = (new SubscriberFactory())->create();
    $this->getApi()->tagSubscriber((int)$subscriber->getId(), (int)$tag->getId());

    $result = $this->getApi()->deleteTag((string)$tag->getId());

    verify($result)->equals(true);
    $this->entityManager->clear();
    verify($this->tagRepository->findOneById((int)$tag->getId()))->null();

    $subscriberResult = $this->getApi()->getSubscriber((int)$subscriber->getId());
    verify($subscriberResult['tags'])->arrayCount(0);
  }

  private function getApi(): API {
    return $this->diContainer->get(API::class);
  }
}
