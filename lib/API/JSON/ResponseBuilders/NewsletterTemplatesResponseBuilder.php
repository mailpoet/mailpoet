<?php

namespace MailPoet\API\JSON\ResponseBuilders;

use MailPoet\Entities\NewsletterTemplateEntity;

class NewsletterTemplatesResponseBuilder {
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
        'description' => $template->getDescription(),
        'readonly' => $template->getReadonly(),
      ];
    }
    return $data;
  }
}
