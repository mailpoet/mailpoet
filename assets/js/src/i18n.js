define('i18n',
  [
    'mailpoet'
  ], function(
    mp
) {
    'use strict';

    var MailPoet = mp;

    var translations = {};

    MailPoet.I18n = {
      add: function(key, value) {
        translations[key] = value;
      },
      t: function(key) {
        return translations[key] || 'TRANSLATION "%$1s" NOT FOUND'.replace('%$1s', key);
      },
      all: function() {
        return translations;
      }
    };

  });
