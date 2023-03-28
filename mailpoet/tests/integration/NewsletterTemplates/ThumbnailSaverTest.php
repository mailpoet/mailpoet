<?php declare(strict_types = 1);

namespace MailPoet\NewsletterTemplates;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterTemplateEntity;

class ThumbnailSaverTest extends \MailPoetTest {
  /** @var ThumbnailSaver */
  private $thumbnailSaver;

  public function _before() {
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

  public function testItUpdatesBaseUrlIfChanged() {
    $template = $this->createTemplate();
    $template = $this->thumbnailSaver->ensureTemplateThumbnailFile($template);
    $thumbnailUrl = $template->getThumbnail();
    $template->setThumbnail(str_replace(Env::$tempUrl, 'http://example.com', (string)$thumbnailUrl));
    $template = $this->thumbnailSaver->ensureTemplateThumbnailFile($template);
    // Base url was updated back to initial value
    $thumbnailUrl = $template->getThumbnail();
    expect($thumbnailUrl)->string();
    expect($thumbnailUrl)->startsWith(Env::$tempUrl);
    [,$fileName] = explode(ThumbnailSaver::THUMBNAIL_DIRECTORY, (string)$thumbnailUrl);
    // File is still the same
    expect($thumbnailUrl)->endsWith($fileName);
    $file = Env::$tempPath . '/' . ThumbnailSaver::THUMBNAIL_DIRECTORY . $fileName;
    unlink($file); // remove the file after the test
  }

  public function testItSkipsNotBase64JpegData() {
    $template = $this->createTemplate();
    $template->setThumbnailData('Some data');
    $template = $this->thumbnailSaver->ensureTemplateThumbnailFile($template);
    $thumbnailUrl = $template->getThumbnail();
    // Base url was updated back to initial value
    expect($thumbnailUrl)->null();
  }

  private function createTemplate(): NewsletterTemplateEntity {
    $template = new NewsletterTemplateEntity('Template');
    $template->setBody([1]);
    $template->setThumbnailData('data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');
    $this->entityManager->persist($template);
    $this->entityManager->flush();
    return $template;
  }
}
