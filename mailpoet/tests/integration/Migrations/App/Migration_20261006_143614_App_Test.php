<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Entities\FormEntity;
use MailPoet\Form\FormsRepository;

//phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20261006_143614_App_Test extends \MailPoetTest {
  private const BROKEN_URL = 'https://ps.w.org/mailpoet/assets/form-templates/template-4/mailbox@3x.png';
  private const FIXED_URL = 'https://ps.w.org/mailpoet/assets/form-templates/template-4/mailbox%403x.png';

  /** @var Migration_20261006_143614_App */
  private $migration;

  /** @var FormsRepository */
  private $formsRepository;

  public function _before() {
    parent::_before();
    $this->migration = new Migration_20261006_143614_App($this->diContainer);
    $this->formsRepository = $this->diContainer->get(FormsRepository::class);
  }

  public function testItEncodesAtSignInTemplateImageUrl() {
    $form = $this->createForm([$this->imageBlock(self::BROKEN_URL)]);

    $this->migration->run();

    verify($this->refetchBody($form)[0]['params']['url'])->equals(self::FIXED_URL);
  }

  public function testItEncodesAtSignInImageNestedInColumns() {
    $form = $this->createForm([
      [
        'type' => 'columns',
        'body' => [
          ['type' => 'column', 'body' => [$this->imageBlock(self::BROKEN_URL)]],
        ],
      ],
    ]);

    $this->migration->run();

    verify($this->refetchBody($form)[0]['body'][0]['body'][0]['params']['url'])->equals(self::FIXED_URL);
  }

  public function testItEncodesAtSignInTrashedForm() {
    $form = $this->createForm([$this->imageBlock(self::BROKEN_URL)]);
    $form->setDeletedAt(new \DateTimeImmutable());
    $this->entityManager->flush();

    $this->migration->run();

    verify($this->refetchBody($form)[0]['params']['url'])->equals(self::FIXED_URL);
  }

  public function testItLeavesOtherImageUrlsUnchanged() {
    $userUrl = 'https://example.com/wp-content/uploads/logo@2x.png';
    $form = $this->createForm([$this->imageBlock($userUrl)]);

    $this->migration->run();

    verify($this->refetchBody($form)[0]['params']['url'])->equals($userUrl);
  }

  public function testItIsIdempotentWhenRunTwice() {
    $form = $this->createForm([$this->imageBlock(self::BROKEN_URL)]);

    $this->migration->run();
    $this->migration->run();

    verify($this->refetchBody($form)[0]['params']['url'])->equals(self::FIXED_URL);
  }

  private function createForm(array $body): FormEntity {
    $form = new FormEntity('Form from template');
    $form->setBody($body);
    $this->formsRepository->persist($form);
    $this->formsRepository->flush();
    return $form;
  }

  private function imageBlock(string $url): array {
    return ['type' => 'image', 'id' => 'image', 'params' => ['url' => $url, 'alt' => '']];
  }

  private function refetchBody(FormEntity $form): array {
    // Without clear() findOneById() returns the identity-map instance and passes even if run() never flushed
    $this->entityManager->clear();
    $updated = $this->formsRepository->findOneById($form->getId());
    $this->assertInstanceOf(FormEntity::class, $updated);
    $body = $updated->getBody();
    $this->assertIsArray($body);
    return $body;
  }
}
