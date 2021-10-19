<?php

namespace MailPoet\NewsletterTemplates;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterTemplateEntity;
use MailPoet\WP\Functions as WPFunctions;

class ThumbnailSaver {
  const THUMBNAIL_DIRECTORY = 'newsletter_thumbnails';
  const IMAGE_QUALITY = 80;

  /** @var NewsletterTemplatesRepository */
  private $repository;

  /** @var WPFunctions */
  private $wp;

  /** @var string */
  private $baseDirectory;

  /** @var string */
  private $baseUrl;

  public function __construct(
    NewsletterTemplatesRepository $repository,
    WPFunctions $wp
  ) {
    $this->repository = $repository;
    $this->wp = $wp;
    $this->baseDirectory = Env::$tempPath;
    $this->baseUrl = Env::$tempUrl;
  }

  public function ensureTemplateThumbnailsForAll() {
    $templates = $this->repository->findBy(['readonly' => false]);
    foreach ($templates as $template) {
      $this->ensureTemplateThumbnailFile($template);
    }
  }

  public function ensureTemplateThumbnailFile(NewsletterTemplateEntity $template): NewsletterTemplateEntity {
    if ($template->getReadonly()) {
      return $template;
    }
    $thumbnailUrl = $template->getThumbnail();
    $savedFilename = null;
    $savedBaseUrl = null;
    if ($thumbnailUrl && strpos($thumbnailUrl, self::THUMBNAIL_DIRECTORY) !== false) {
      [$savedBaseUrl, $savedFilename] = explode('/' . self::THUMBNAIL_DIRECTORY . '/', $thumbnailUrl ?? '');
    }
    $file = $this->baseDirectory . '/' . self::THUMBNAIL_DIRECTORY . '/' . $savedFilename;
    if (!$savedFilename || !file_exists($file)) {
      $this->saveTemplateImage($template);
    }

    // File might exist but domain was changed
    $thumbnailUrl = $template->getThumbnail();
    if ($savedBaseUrl && $savedBaseUrl !== $this->baseUrl && $thumbnailUrl) {
      $template->setThumbnail(str_replace($savedBaseUrl, $this->baseUrl, $thumbnailUrl));
    }
    return $template;
  }

  private function saveTemplateImage(NewsletterTemplateEntity $template): void {
    $data = $template->getThumbnailData();
    if (!$data) {
      return;
    }
    // Check that data contains Base 64 encoded jpeg
    if (strpos($data, 'data:image/jpeg;base64') !== 0) {
      return;
    }
    $thumbNailsDirectory = $this->baseDirectory . '/' . self::THUMBNAIL_DIRECTORY;
    if (!file_exists($thumbNailsDirectory)) {
      $this->wp->wpMkdirP($thumbNailsDirectory);
    }
    $file = $thumbNailsDirectory . '/' . uniqid() . '_template_' . $template->getId() . '.jpg';
    if ($this->compressAndSaveFile($file, $data)) {
      $url = str_replace($this->baseDirectory, $this->baseUrl, $file);
      $template->setThumbnail($url);
      $this->repository->flush();
    }
  }

  private function compressAndSaveFile(string $file, string $data): bool {
    $initialSaveResult = $this->saveBase64AsImageFile($file, $data);
    $editor = $this->wp->wpGetImageEditor($file);
    if ($editor instanceof \WP_Error) {
      return $initialSaveResult;
    }
    $result = $editor->set_quality(self::IMAGE_QUALITY);
    if ($result instanceof \WP_Error) {
      return $initialSaveResult;
    }
    $result = $editor->save($file);
    if ($result instanceof \WP_Error) {
      return $initialSaveResult;
    }
    unset($editor);
    return true;
  }

  /**
   * Simply saves base64 to a file without any compression
   * @return bool
   */
  private function saveBase64AsImageFile(string $file, string $data): bool {
    return file_put_contents($file, file_get_contents($data)) !== false;
  }
}
