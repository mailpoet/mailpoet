<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Twig;

use MailPoetVendor\Twig\Extension\AbstractExtension;
use MailPoetVendor\Twig\TwigFilter;

class Filters extends AbstractExtension {
  public function getName() {
    return 'filters';
  }

  public function getFilters() {
    return [
      new TwigFilter(
        'intval',
        'intval'
      ),
      new TwigFilter(
        'replaceLinkTags',
        'MailPoet\Util\Helpers::replaceLinkTags'
      ),
    ];
  }
}
