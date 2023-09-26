// A placeholder for MailPoet object
import { MailPoetAjax } from './ajax';
import { MailPoetI18n } from './i18n';
import { MailPoetIframe } from './iframe';

export const MailPoet = {
  Ajax: MailPoetAjax,
  I18n: MailPoetI18n,
  Iframe: MailPoetIframe,
} as const;

// Expose MailPoet globally
window.MailPoet = MailPoet;
