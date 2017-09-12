<?php

namespace MailPoet\Config;

class PluginActivatedHook {
  private $deferred_admin_notices;

  public function __construct(DeferredAdminNotices $deferred_admin_notices) {
    $this->deferred_admin_notices = $deferred_admin_notices;
  }

  public function action($plugin, $network_wide) {
    if($plugin === plugin_basename(Env::$file) && $network_wide) {
      $this->deferred_admin_notices->addNetworkAdminNotice(__("We noticed that you're using an unsupported environment. While MailPoet might work within a MultiSite environment, we don’t support it.", 'mailpoet'));
    }
  }
}