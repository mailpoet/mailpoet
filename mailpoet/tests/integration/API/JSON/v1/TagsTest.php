<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\v1\Tags;
use MailPoet\Entities\TagEntity;
use MailPoet\Tags\TagRepository;

class TagsTest extends \MailPoetTest {

  /** @var TagRepository */
  private $repository;

  /** @var Tags */
  private $testee;

  public function _before() {
    parent::_before();

    $this->repository = $this->diContainer->get(TagRepository::class);
    $this->testee = $this->diContainer->get(Tags::class);
  }

  public function testItListsAllTags() {
    $count = count($this->repository->findAll());
    $this->assertSame(0, $count);
    do {
      $this->repository->persist(new TagEntity(sprintf("Tag %d", $count + 1)));
      $this->repository->flush();
      $count = count($this->repository->findAll());
    } while ($count < 10);

    $response = $this->testee->listing();
    $this->assertCount(10, $response->data);
  }

  public function testItCreatesTags() {

    $entity = $this->repository->findOneBy(['name' => 'test']);
    $this->assertNull($entity);
    $response = $this->testee->create(['name' => 'test']);
    $entity = $this->repository->findOneBy(['name' => 'test']);
    $this->assertNotNull($entity);
    $this->assertSame('test', $response->data['name']);
  }

  public function testItCanNotCreateTagsWithoutNames() {

    $result = $this->testee->create([]);
    $this->assertEquals(400, $result->status);
  }

  public function _after() {
    parent::_after();
  }
}
