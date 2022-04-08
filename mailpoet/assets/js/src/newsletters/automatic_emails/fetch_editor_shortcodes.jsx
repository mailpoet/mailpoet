import _ from 'underscore';
import MailPoet from 'mailpoet';

const fetchAutomaticEmailShortcodes = (defaultConfig, newsletter) => {
  if (newsletter.type !== 'automatic') return defaultConfig;

  const config = defaultConfig;

  return MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'automatic_emails',
    action: 'get_event_shortcodes',
    data: {
      email_slug: newsletter.options.group,
      event_slug: newsletter.options.event,
    },
  })
    .then((response) => {
      if (!_.isObject(response) || !response.data) return config;
      config.shortcodes = { ...config.shortcodes, ...response.data };
      return config;
    })
    .fail((pauseFailResponse) => {
      if (pauseFailResponse.errors.length > 0) {
        MailPoet.Notice.error(
          pauseFailResponse.errors.map((error) => error.message),
          { scroll: true, static: true },
        );
      }
    });
};

export default fetchAutomaticEmailShortcodes;
