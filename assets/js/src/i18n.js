import mp from 'mailpoet';

var MailPoet = mp;

var translations = {};

MailPoet.I18n = {
  add: function add(key, value) {
    translations[key] = value;
  },
  t: function t(key) {
    return translations[key] || 'TRANSLATION "%1$s" NOT FOUND'.replace('%1$s', key);
  },
  all: function all() {
    return translations;
  },
};
