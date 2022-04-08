import { LocaleData, setLocaleData } from '@wordpress/i18n';

export default (moduleTranslations: string[]): void => {
  moduleTranslations.forEach((translations) => {
    const parsed = JSON.parse(translations);
    if (!parsed || !parsed.locale_data?.messages) {
      return;
    }
    setLocaleData(parsed.locale_data.messages as LocaleData);
  });
};
