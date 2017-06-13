<?php

namespace MailPoet\Config;

class PluginActivatedHook {

  /** @var DeferredAdminNotices */
  private $deferredAdminNotices;

  public function __construct(DeferredAdminNotices $deferredAdminNotices) {
    $this->deferredAdminNotices = $deferredAdminNotices;
  }

  public function action($plugin, $networkWide) {
    if($networkWide) {
      $this->deferredAdminNotices->addNetworkAdminNotice(__('We noticed that you\'re using an unsupported environment. While MailPoet might work within a MultiSite environment, we don’t support it.', 'mailpoet'));
    }
  }

}