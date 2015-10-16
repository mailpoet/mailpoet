define(
  [
    'backbone',
    'jquery',
    'mailpoet'
  ],
  function(
    Backbone,
    jQuery,
    MailPoet
  ) {
    if(jQuery('#mailpoet_settings').length === 0) {
      return;
    }

    MailPoet.Router = new (Backbone.Router.extend({
      routes: {
        'mta(/:method)': 'sendingMethod',
        '(:tab)': 'tabs',
      },
      sendingMethod: function(method) {
        // display mta tab
        this.tabs('mta');

        // hide all sending methods' settings
        jQuery(
          '#mailpoet_sending_method_setup, .mailpoet_sending_method'
        ).hide();

        // hide "save settings" button
        jQuery('.mailpoet_settings_submit').hide();

        if(method === null) {
          // show sending methods
          jQuery('.mailpoet_sending_methods').fadeIn();
        } else {
          // hide DKIM option when using MailPoet's API
          jQuery('#mailpoet_mta_dkim')[
            (method === 'mailpoet')
            ? 'hide'
            : 'show'
          ]();

          // hide sending methods
          jQuery('.mailpoet_sending_methods').hide();

          // display selected sending method's settings
          jQuery('.mailpoet_sending_method[data-method="'+ method +'"]').show();
          jQuery('#mailpoet_sending_method_setup').fadeIn();
        }
      },
      tabs: function(tab, section) {
        // set default tab
        tab = tab || 'basics';

        // reset all active tabs
        jQuery('.nav-tab-wrapper a').removeClass('nav-tab-active');

        // hide panels & sections
        jQuery('.mailpoet_panel, .mailpoet_section').hide();

        // set active tab
        jQuery('a.nav-tab[href="#'+tab+'"]').addClass('nav-tab-active').blur();

        // show selected panel
        if(jQuery('.mailpoet_panel[data-tab="'+ tab +'"]').length > 0) {
          jQuery('.mailpoet_panel[data-tab="'+ tab +'"]').show();
        }

        // show "save settings" button
        jQuery('.mailpoet_settings_submit').show();
      }
    }));

    jQuery(document).ready(function() {
      Backbone.history.start();
    });
  }
);