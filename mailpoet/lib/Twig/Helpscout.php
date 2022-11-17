<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Twig;

use MailPoet\DI\ContainerWrapper;
use MailPoet\Helpscout\Beacon;
use MailPoetVendor\Twig\TwigFunction;

class Helpscout extends \MailPoetVendor\Twig\Extension\AbstractExtension {
  public function getFunctions() {
    return [
      new TwigFunction(
        'get_helpscout_user_data',
        [$this, 'getHelpscoutUserData'],
        ['is_safe' => ['all']]
      ),
      new TwigFunction(
        'get_helpscout_site_data',
        [$this, 'getHelpscoutSiteData'],
        ['is_safe' => ['all']]
      ),
    ];
  }

  public function getHelpscoutUserData() {
    return ContainerWrapper::getInstance()->get(Beacon::class)->getUserData();
  }

  public function getHelpscoutSiteData() {
    return ContainerWrapper::getInstance()->get(Beacon::class)->getSiteData();
  }
}
