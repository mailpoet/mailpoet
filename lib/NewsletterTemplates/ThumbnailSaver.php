<?php

namespace MailPoet\NewsletterTemplates;

use MailPoet\Config\Env;
use MailPoet\Entities\NewsletterTemplateEntity;
use MailPoet\WP\Functions as WPFunctions;

class ThumbnailSaver {
  const THUMBNAIL_DIRECTORY = 'newsletter_thumbnails';

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
    $thumbNailsDirectory = $this->baseDirectory . '/' . self::THUMBNAIL_DIRECTORY;
    if (!file_exists($thumbNailsDirectory)) {
      $this->wp->wpMkdirP($thumbNailsDirectory);
    }
    $file = $thumbNailsDirectory . '/' . uniqid() . '_template_' . $template->getId() . '.jpg';
    if (file_put_contents($file, file_get_contents($data))) {
      $url = str_replace($this->baseDirectory, $this->baseUrl, $file);
      $template->setThumbnail($url);
      $this->repository->flush();
    }
  }
}
