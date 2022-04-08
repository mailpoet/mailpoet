const translations: Record<string, string> = {};

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
