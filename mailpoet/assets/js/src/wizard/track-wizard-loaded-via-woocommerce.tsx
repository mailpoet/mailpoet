function trackWizardLoadedViaWooCommerce() {
  // Send event that the MP wizard was loaded via the WooCommerce home page to Mixpanel and delete the corresponding setting.
  // We need to do this after the user completes the wizard, since before that tracking is not enabled.
  if (window.mailpoet_track_wizard_loaded_via_woocommerce) {
    window.MailPoet.trackEvent(
      'User opened the MailPoet setup task in WooCommerce > Home',
      {
        'WooCommerce version': window.mailpoet_woocommerce_version,
      },
    );
    void window.MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'delete',
      data: 'send_event_that_wizard_was_loaded_via_woocommerce',
    });
  }

  if (window.mailpoet_track_wizard_loaded_via_woocommerce_marketing_dashboard) {
    window.MailPoet.trackEvent(
      'User clicked on complete MailPoet setup in WooCommerce > Multichannel Marketing dashboard',
      {
        'WooCommerce version': window.mailpoet_woocommerce_version,
      },
    );
    void window.MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'delete',
      data: 'wizard_loaded_via_woocommerce_marketing_dashboard',
    });
  }
}

document.addEventListener('DOMContentLoaded', trackWizardLoadedViaWooCommerce);
