import { getSettings, setSettings } from '@wordpress/date';

type WordPressDateSettings = ReturnType<typeof getSettings>;
type WordPressDateGlobal = {
  date?: {
    getSettings?: () => WordPressDateSettings;
    __experimentalGetSettings?: () => WordPressDateSettings;
  };
};

let areWordPressDateSettingsSynced = false;

export const syncWordPressDateSettings = (): void => {
  if (areWordPressDateSettingsSynced) {
    return;
  }

  const wpDate = (window.wp as unknown as WordPressDateGlobal | undefined)
    ?.date;
  const settings =
    wpDate?.getSettings?.() ??
    // eslint-disable-next-line no-underscore-dangle
    wpDate?.__experimentalGetSettings?.();

  if (!settings) {
    return;
  }

  setSettings(settings);
  areWordPressDateSettingsSynced = true;
};
