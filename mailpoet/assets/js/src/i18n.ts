const mailpoetWindow =
  typeof window !== 'undefined'
    ? (window as typeof window & {
        MailPoet?: {
          I18n?: {
            all?: () => Record<string, string>;
          };
        };
        mailpoet_i18n?: Record<string, string>;
      })
    : undefined;

const translations: Record<string, string> = {
  ...(mailpoetWindow?.MailPoet?.I18n?.all?.() ??
    mailpoetWindow?.mailpoet_i18n ??
    {}),
};
if (mailpoetWindow) {
  mailpoetWindow.mailpoet_i18n = translations;
}

export const MailPoetI18n = {
  add: function add(key: string, value: string): void {
    translations[key] = value;
  },
  t: function t(key: string): string {
    return (
      translations[key] || 'TRANSLATION "%1$s" NOT FOUND'.replace('%1$s', key)
    );
  },
  all: function all(): Record<string, string> {
    return translations;
  },
} as const;
