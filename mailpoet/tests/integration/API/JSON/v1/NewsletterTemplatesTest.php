<?php declare(strict_types = 1);

namespace MailPoet\Test\API\JSON\v1;

use MailPoet\API\JSON\Response as APIResponse;
use MailPoet\API\JSON\v1\NewsletterTemplates;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterTemplateEntity;
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
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $endpoint->get(['id' => 'not_an_id']);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $response = $endpoint->get(['id' => $template->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['name'])->same('Template #1');
    expect($response->data['body'])->same(['key1' => 'value1']);
  }

  public function testItCanGetAllNewsletterTemplates() {
    $templates = $this->newsletterTemplatesRepository->findAll();

    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->getAll();
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data)->count(count($templates));
  }

  public function testItCanSaveANewTemplate() {
    $templateData = [
      'name' => 'Template #3',
      'body' => '{"key3": "value3"}',
    ];

    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->save($templateData);
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['name'])->same('Template #3');
    expect($response->data['body'])->same(['key3' => 'value3']);
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
    expect($response->status)->equals(APIResponse::STATUS_OK);
    expect($response->data['name'])->same('Template #3');
    expect($response->data['body'])->same(['key3' => 'value3']);
    expect($response->data['newsletter_id'])->same($newsletter->getId());
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
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $templateData['body'] = json_decode($templateData['body'], true);

    expect($response->data['name'])->same('Template #2');
    expect($response->data['body'])->same(['key3' => 'value3']);
    expect($response->data['newsletter_id'])->same($newsletter->getId());
  }

  public function testItCanDeleteANewsletterTemplate() {
    $endpoint = $this->diContainer->get(NewsletterTemplates::class);
    $response = $endpoint->delete(/* missing id */);
    expect($response->status)->equals(APIResponse::STATUS_NOT_FOUND);
    expect($response->errors[0]['message'])
      ->equals('This template does not exist.');

    $template = $this->newsletterTemplatesRepository->findOneBy(['name' => 'Template #1']);
    $this->assertInstanceOf(NewsletterTemplateEntity::class, $template);
    $templateId = $template->getId();
    $response = $endpoint->delete(['id' => $template->getId()]);
    expect($response->status)->equals(APIResponse::STATUS_OK);

    $deletedTemplate = $this->newsletterTemplatesRepository->findOneById($templateId);
    expect($deletedTemplate)->null();
  }
}
