define('admin', [
    'mailpoet',
    'jquery',
    'handlebars',
    'react'
  ], function(MailPoet, jQuery, Handlebars, React) {
  jQuery(function($) {
    // dom ready
    $(function() {

    });
  });

  // set globals
  window.Handlebars = Handlebars;
  window.React = React;
});
