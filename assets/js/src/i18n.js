define('i18n',
  [
    'mailpoet',
    'underscore',
  ], function(
    MailPoet,
    _
) {
  'use strict';

  var translations = {};

  MailPoet.I18n = {
    add: function(key, value) {
      translations[key] = value;
    },
    t: function(key) {
      return translations[key] || 'TRANSLATION NOT FOUND';
    },
    all: function() {
      return translations;
    }
  };

});
