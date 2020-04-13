import Backbone from 'backbone';
import jQuery from 'jquery';
import mp from 'mailpoet';

var MailPoet = mp;
if (jQuery('#mailpoet_settings').length > 0) {
  MailPoet.Router = new (Backbone.Router.extend({
    routes: {
      '': 'defaultRoute',
      'mta(/:group)': 'sendingMethodGroup',
      '(:tab)': 'tabs',
    },
    defaultRoute: function defaultRoute() {
      // redirect to new settings by default
      window.location.href = '?page=mailpoet-new-settings#/basics';
    },
    sendingMethodGroup: function sendingMethodGroup(group) {
      window.location.href = '?page=mailpoet-new-settings#/mta/' + (group || '');
    },
    tabs: function tabs(tab) {
      if (tab === 'basics') {
        window.location.href = '?page=mailpoet-new-settings#/basics';
      }
      if (tab === 'woocommerce') {
        if (!window.mailpoet_woocommerce_active) {
          window.location.href = '?page=mailpoet-new-settings#/basics';
        } else {
          window.location.href = '?page=mailpoet-new-settings#/woocommerce';
        }
      }
      if (tab === 'premium') {
        window.location.href = '?page=mailpoet-new-settings#/premium';
      }
      if (tab === 'signup') {
        window.location.href = '?page=mailpoet-new-settings#/signup';
      }
      if (tab === 'advanced') {
        window.location.href = '?page=mailpoet-new-settings#/advanced';
      }
      // reset all active tabs
      jQuery('.nav-tab-wrapper a').removeClass('nav-tab-active');

      // hide panels & sections
      jQuery('.mailpoet_tab_panel, .mailpoet_section').hide();

      // set active tab
      jQuery('a.nav-tab[href="#' + tab + '"]').addClass('nav-tab-active').blur();

      // show selected panel
      if (jQuery('.mailpoet_tab_panel[data-tab="' + tab + '"]').length > 0) {
        jQuery('.mailpoet_tab_panel[data-tab="' + tab + '"]').show();
      }

      // show "save settings" button
      if (tab !== 'premium') {
        jQuery('.mailpoet_settings_submit').show();
      }

      // add 'nav-tab-reload' to all tabs when on '#premium'
      if (tab === 'premium') {
        jQuery('.nav-tab-wrapper .nav-tab').addClass('nav-tab-reload');
      }

      MailPoet.trackEvent(
        'User has clicked a tab in Settings',
        {
          'MailPoet Free version': window.mailpoet_version,
          'Tab ID': tab,
        }
      );
    },
  }))();

  // force full reload when going from/to '#premium' page
  window.addEventListener('hashchange', function hashchange(e) {
    e.preventDefault();
    const oldHash = e.oldURL.split('#')[1] || null;
    const newHash = e.newURL.split('#')[1] || null;
    if (oldHash === 'premium' || newHash === 'premium') {
      window.location.reload();
    }
  });

  jQuery(document).ready(function ready() {
    if (!Backbone.History.started) Backbone.history.start();

    // force full tab reload for tabs with 'nav-tab-reload' class
    jQuery('.nav-tab').click(function click(e) {
      if (e.target.classList.contains('nav-tab-reload')) {
        e.preventDefault();
        window.history.replaceState(null, null, e.target.href);
        window.location.reload();
      }
    });
  });
}
