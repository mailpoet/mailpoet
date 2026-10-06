<?php declare(strict_types = 1);

namespace MailPoet\Migrations\App;

use MailPoet\Form\FormsRepository;
use MailPoet\Migrator\AppMigration;
use MailPoet\Util\CdnAssetUrl;

/**
 * Forms created from a template store the template image URLs in their body.
 * The CDN serves SVN, which reads a raw "@" in a file name (e.g. mailbox@3x.png)
 * as a peg revision and answers with a 404. CdnAssetUrl now encodes it for new
 * forms; this rewrites the URLs already saved in existing forms the same way.
 */
class Migration_20261006_143614_App extends AppMigration {
  private const FORM_TEMPLATES_CDN_URL = CdnAssetUrl::CDN_URL . 'assets/form-templates/';

  public function run(): void {
    $formsRepository = $this->container->get(FormsRepository::class);
    foreach ($formsRepository->findAll() as $form) {
      $body = $form->getBody();
      if (!is_array($body)) {
        continue;
      }
      $changed = false;
      $body = $this->encodeAtSignInTemplateUrls($body, $changed);
      if ($changed) {
        $form->setBody($body);
      }
    }
    $this->entityManager->flush();
  }

  private function encodeAtSignInTemplateUrls(array $node, bool &$changed): array {
    foreach ($node as $key => $value) {
      if (is_array($value)) {
        $node[$key] = $this->encodeAtSignInTemplateUrls($value, $changed);
        continue;
      }
      if (!is_string($value) || strpos($value, self::FORM_TEMPLATES_CDN_URL) !== 0 || strpos($value, '@') === false) {
        continue;
      }
      $node[$key] = str_replace('@', '%40', $value);
      $changed = true;
    }
    return $node;
  }
}
