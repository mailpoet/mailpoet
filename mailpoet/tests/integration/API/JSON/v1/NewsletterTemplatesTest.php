<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\NewsletterTemplates;
use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterTemplateEntity;
use MailPoet\Newsletter\NewsletterSaveController;
use MailPoet\Newsletter\NewslettersRepository;
use MailPoet\NewsletterTemplates\NewsletterTemplatesRepository;

class NewsletterTemplatesTest extends \MailPoetTest {
  /** @var NewsletterTemplatesRepository */
  private $newsletterTemplatesRepository;

  public function _before() {
    parent::_before();
    $this->newsletterTemplatesRepository = $this->diContainer->get(NewsletterTemplatesRepository::class);

    $template1 = new NewsletterTemplateEntity('Template #1');
    $template1->setBody(['key1' => 'value1']);
    $this->entityManager->persist($template1);

    $template2 = new NewsletterTemplateEntity('Template #2');
    $template2->setBody(['key2' => 'value2']);
    $template2->setNewsletter($this->entityManager->getReference(NewsletterEntity::class, 1));
    $this->entityManager->persist($template2);

    $this->entityManager->flush();
  }

  public function testItCanGetANewsletterTemplate() {
    $template = $this->newsletterTemplatesRepository->findOneBy(['name' => 'Template #1']);
    $this->assertInstanceOf(NewsletterTemplateEntity::class, $template);

    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->get(/* missing id */);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $endpoint->get(['id' => 'not_an_id']);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $endpoint->get(['id' => $template->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['name'])->same('Template #1');
    verify($response->data['body'])->same(['key1' => 'value1']);
  }

  public function testItCanGetAllNewsletterTemplates() {
    $templates = $this->newsletterTemplatesRepository->findAll();

    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->getAll();
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data)->arrayCount(count($templates));
  }

  public function testItCanSaveANewTemplate() {
    $templateData = [
      'name' => 'Template #3',
      'body' => '{"key3": "value3"}',
    ];

    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->save($templateData);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['name'])->same('Template #3');
    verify($response->data['body'])->same(['key3' => 'value3']);
  }

  public function testItCanSaveANewTemplateAssociatedWithANewsletter() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Newsletter subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $templateData = [
      'newsletter_id' => $newsletter->getId(),
      'name' => 'Template #3',
      'body' => '{"key3": "value3"}',
    ];

    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->save($templateData);
    verify($response->status)->equals(APIResponse::STATUS_OK);
    verify($response->data['name'])->same('Template #3');
    verify($response->data['body'])->same(['key3' => 'value3']);
    verify($response->data['newsletter_id'])->same($newsletter->getId());
  }

  public function testItCanUpdateTemplateAssociatedWithANewsletter() {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Newsletter subject');
    $newsletter->setType(NewsletterEntity::TYPE_STANDARD);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();

    $templateData = [
      'newsletter_id' => $newsletter->getId(),
      'name' => 'Template #2',
      'body' => '{"key3": "value3"}',
    ];

    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->save($templateData);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $templateData['body'] = json_decode($templateData['body'], true);

    verify($response->data['name'])->same('Template #2');
    verify($response->data['body'])->same(['key3' => 'value3']);
    verify($response->data['newsletter_id'])->same($newsletter->getId());
  }

  public function testItCanDeleteANewsletterTemplate() {
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->delete(/* missing id */);
    verify($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    verify($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $template = $this->newsletterTemplatesRepository->findOneBy(['name' => 'Template #1']);
    $this->assertInstanceOf(NewsletterTemplateEntity::class, $template);
    $templateId = $template->getId();
    $response = $endpoint->delete(['id' => $template->getId()]);
    verify($response->status)->equals(APIResponse::STATUS_OK);

    $deletedTemplate = $this->newsletterTemplatesRepository->findOneById($templateId);
    verify($deletedTemplate)->null();
  }

  public function testAutosaveInTheEditorSavesTheTemplate() {
    $template = $this->createSavedTemplate('Saved template');
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $newsletterId = $endpoint->edit(['id' => $template->getId()])->data['newsletter_id'];

    $this->diContainer->get(NewsletterSaveController::class)->save([
      'id' => $newsletterId,
      'subject' => 'Autosaved name',
      'body' => '{"content":{"type":"container","blocks":[{"type":"text","text":"autosaved"}]}}',
    ]);
    $endpoint->edit(['id' => $template->getId()]);

    $this->entityManager->refresh($template);
    verify($template->getName())->same('Autosaved name');
    verify($template->getBody())->same(['content' => ['type' => 'container', 'blocks' => [['type' => 'text', 'text' => 'autosaved']]]]);
  }

  public function testItReplacesTheThumbnailOnEverySave() {
    $template = $this->createSavedTemplate('Saved template');
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $newsletterId = $endpoint->edit(['id' => $template->getId()])->data['newsletter_id'];

    $first = $endpoint->save(['newsletter_id' => $newsletterId, 'thumbnail_data' => $this->thumbnailData('first')]);
    $second = $endpoint->save(['newsletter_id' => $newsletterId, 'thumbnail_data' => $this->thumbnailData('second')]);

    verify($second->data['thumbnail'])->notEquals($first->data['thumbnail']);
    verify(file_get_contents($this->thumbnailPath($second->data['thumbnail'])))->same('second');
    verify(file_exists($this->thumbnailPath($first->data['thumbnail'])))->false();
  }

  public function testSavingACopyKeepsTheOriginalThumbnail() {
    $original = $this->createSavedTemplate('Original');
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $originalNewsletterId = $endpoint->edit(['id' => $original->getId()])->data['newsletter_id'];
    $originalThumbnail = $endpoint->save(['newsletter_id' => $originalNewsletterId, 'thumbnail_data' => $this->thumbnailData('original')])->data['thumbnail'];
    $copyId = $endpoint->duplicate(['id' => $original->getId()])->data['id'];
    $copyNewsletterId = $endpoint->edit(['id' => $copyId])->data['newsletter_id'];

    $copyThumbnail = $endpoint->save(['newsletter_id' => $copyNewsletterId, 'thumbnail_data' => $this->thumbnailData('copy')])->data['thumbnail'];

    verify($copyThumbnail)->notEquals($originalThumbnail);
    verify(file_get_contents($this->thumbnailPath($originalThumbnail)))->same('original');
  }

  public function testSavingTheOriginalKeepsTheCopyThumbnail() {
    $original = $this->createSavedTemplate('Original');
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $originalNewsletterId = $endpoint->edit(['id' => $original->getId()])->data['newsletter_id'];
    $endpoint->save(['newsletter_id' => $originalNewsletterId, 'thumbnail_data' => $this->thumbnailData('original')]);
    $copyThumbnail = $endpoint->duplicate(['id' => $original->getId()])->data['thumbnail'];

    $endpoint->save(['newsletter_id' => $originalNewsletterId, 'thumbnail_data' => $this->thumbnailData('changed')]);

    verify(file_get_contents($this->thumbnailPath($copyThumbnail)))->same('original');
  }

  public function testItCreatesOneTemplateEmailAndReusesIt() {
    $template = $this->createSavedTemplate('Saved template');
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);

    $first = $endpoint->edit(['id' => $template->getId()]);
    $second = $endpoint->edit(['id' => $template->getId()]);

    verify($first->status)->same(APIResponse::STATUS_OK);
    verify($second->data['newsletter_id'])->same($first->data['newsletter_id']);
    $newsletter = $this->diContainer->get(NewslettersRepository::class)->findOneById($first->data['newsletter_id']);
    $this->assertInstanceOf(NewsletterEntity::class, $newsletter);
    verify($newsletter->getType())->same(NewsletterEntity::TYPE_TEMPLATE);
    verify($newsletter->getSubject())->same('Saved template');
  }

  public function testItDoesNotEditMailPoetTemplates() {
    $template = $this->createMailPoetTemplate();
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);

    $response = $endpoint->edit(['id' => $template->getId()]);

    verify($response->status)->same(APIResponse::STATUS_BAD_REQUEST);
    $this->entityManager->refresh($template);
    verify($template->getNewsletter())->null();
  }

  public function testItDuplicatesAMailPoetTemplateAsASavedTemplate() {
    $template = $this->createMailPoetTemplate();
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);

    $response = $endpoint->duplicate(['id' => $template->getId()]);

    verify($response->status)->same(APIResponse::STATUS_OK);
    verify($response->data['name'])->same('Copy of Welcome: Blank');
    verify($response->data['readonly'])->false();
    verify($response->data['categories'])->same('["saved","welcome"]');
    verify($response->data['body'])->same(['content' => 'mailpoet']);
  }

  public function testItDuplicatesARecentlySentTemplateAsAStandardTemplate() {
    $template = new NewsletterTemplateEntity('Recently sent');
    $template->setBody(['content' => 'recent']);
    $template->setCategories(NewsletterTemplatesRepository::RECENTLY_SENT_CATEGORIES);
    $this->entityManager->persist($template);
    $this->entityManager->flush();
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);

    $response = $endpoint->duplicate(['id' => $template->getId()]);

    verify($response->data['categories'])->same('["saved","standard"]');
  }

  public function testItDeletesTheTemplateEmailWithTheTemplate() {
    $template = $this->createSavedTemplate('Saved template');
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $newsletterId = $endpoint->edit(['id' => $template->getId()])->data['newsletter_id'];

    $endpoint->delete(['id' => $template->getId()]);

    $this->entityManager->clear();
    verify($this->diContainer->get(NewslettersRepository::class)->findOneById($newsletterId))->null();
  }

  public function testItKeepsTheLinkedStandardEmailWhenDeletingATemplate() {
    $newsletter = $this->createNewsletter(NewsletterEntity::TYPE_STANDARD);
    $newsletterId = $newsletter->getId();
    $template = $this->createSavedTemplate('Saved from a campaign');
    $template->setNewsletter($newsletter);
    $this->entityManager->flush();
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);

    $endpoint->delete(['id' => $template->getId()]);

    $this->entityManager->clear();
    verify($this->diContainer->get(NewslettersRepository::class)->findOneById($newsletterId))->notNull();
  }

  private function thumbnailData(string $content): string {
    return 'data:image/jpeg;base64,' . base64_encode($content);
  }

  private function thumbnailPath(string $url): string {
    return str_replace(Env::$tempUrl, Env::$tempPath, $url);
  }

  private function createSavedTemplate(string $name): NewsletterTemplateEntity {
    $template = new NewsletterTemplateEntity($name);
    $template->setBody(['content' => 'original']);
    $template->setCategories('["saved","standard"]');
    $this->entityManager->persist($template);
    $this->entityManager->flush();
    return $template;
  }

  private function createMailPoetTemplate(): NewsletterTemplateEntity {
    $template = new NewsletterTemplateEntity('Welcome: Blank');
    $template->setBody(['content' => 'mailpoet']);
    $template->setCategories('["welcome","blank"]');
    $template->setReadonly(true);
    $this->entityManager->persist($template);
    $this->entityManager->flush();
    return $template;
  }

  private function createNewsletter(string $type): NewsletterEntity {
    $newsletter = new NewsletterEntity();
    $newsletter->setSubject('Campaign subject');
    $newsletter->setType($type);
    $this->entityManager->persist($newsletter);
    $this->entityManager->flush();
    return $newsletter;
  }
}
