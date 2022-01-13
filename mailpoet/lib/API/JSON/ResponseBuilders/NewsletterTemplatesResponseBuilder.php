<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\NewsletterTemplateEntity;

class NewsletterTemplatesResponseBuilder {
  const DATE_FORMAT = 'Y-m-d H:i:s';

  public function build(NewsletterTemplateEntity $template): array {
    return [
      'id' => $template->getId(),
      'categories' => $template->getCategories(),
      'thumbnail' => $template->getThumbnail(),
      'name' => $template->getName(),
      'readonly' => $template->getReadonly(),
      'body' => $template->getBody(),
      'created_at' => $template->getCreatedAt()->format(self::DATE_FORMAT),
      'updated_at' => $template->getUpdatedAt()->format(self::DATE_FORMAT),
      'newsletter_id' => ($newsletter = $template->getNewsletter()) ? $newsletter->getId() : null,
    ];
  }

  /**
   * @param NewsletterTemplateEntity[] $newsletterTemplates
   * @return mixed[]
   */
  public function buildForListing(array $newsletterTemplates): array {
    $data = [];
    foreach ($newsletterTemplates as $template) {
      $data[] = [
        'id' => $template->getId(),
        'categories' => $template->getCategories(),
        'thumbnail' => $template->getThumbnail(),
        'name' => $template->getName(),
        'readonly' => $template->getReadonly(),
      ];
    }
    return $data;
  }
}
