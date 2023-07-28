declare global {
  interface Window {
    mailpoet_automation_analytics_api: {
      root: string;
      nonce: string;
    };
    mailpoet_locale_full: string;
  }
}

export const api = window.mailpoet_automation_api;

// export locale to use with Intl APIs
export const locale: Intl.Locale = (() => {
  const tag = (
    window.mailpoet_locale_full ??
    document.documentElement.lang ??
    'en'
  ).replace('_', '-');

  try {
    return new Intl.Locale(tag);
  } catch (_) {
    try {
      return new Intl.Locale(tag.split('-')[0]);
    } catch (__) {
      return new Intl.Locale('en');
    }
  }
})();
