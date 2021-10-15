<?php

namespace MailPoet\NewsletterTemplates;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterTemplateEntity;

class ThumbnailSaverTest extends \MailPoetTest {
  /** @var ThumbnailSaver */
  private $thumbnailSaver;

  public function _before() {
    $this->truncateEntity(NewsletterTemplateEntity::class);
    $this->thumbnailSaver = $this->diContainer->get(ThumbnailSaver::class);
  }

  public function testItCanSaveThumbnailDataAsFile() {
    $template = $this->createTemplate();
    $template = $this->thumbnailSaver->ensureTemplateThumbnailFile($template);
    $thumbnailUrl = $template->getThumbnail();
    expect($thumbnailUrl)->notEmpty();
    expect($thumbnailUrl)->string();
    expect($thumbnailUrl)->startsWith(Env::$tempUrl);
    expect($thumbnailUrl)->stringContainsString(ThumbnailSaver::THUMBNAIL_DIRECTORY);
    [,$fileName] = explode(ThumbnailSaver::THUMBNAIL_DIRECTORY, (string)$thumbnailUrl);
    $file = Env::$tempPath . '/' . ThumbnailSaver::THUMBNAIL_DIRECTORY . $fileName;
    expect(file_exists($file))->true();
    unlink($file); // remove the file after the test
  }

  private function createTemplate(): NewsletterTemplateEntity {
    $template = new NewsletterTemplateEntity('Template');
    $template->setBody([1]);
    $template->setThumbnailData('data:image/gif;base64,R0lGODlhAQABAAAAACw=');
    $this->entityManager->persist($template);
    $this->entityManager->flush();
    return $template;
  }
}
