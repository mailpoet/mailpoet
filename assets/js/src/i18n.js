define('i18n',
  [
    'mailpoet'
  ], function i18n(
    mp
) {
    'use strict';

    var MailPoet = mp;

    var translations = {};

    MailPoet.I18n = {
      add: function add(key, value) {
        translations[key] = value;
      },
      t: function t(key) {
        return translations[key] || 'TRANSLATION "%$1s" NOT FOUND'.replace('%$1s', key);
      },
      all: function all() {
        return translations;
      }
    };
  });
