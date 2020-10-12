import _ from 'lodash';
import { t } from 'common/functions';
import { Settings } from './types';

export default function normalizeSettings(data: any): Settings {
  const text = asString('');
  const disabledCheckbox = asBoolean('1', '0', '0');
  const enabledCheckbox = asBoolean('1', '0', '1');
  const disabledRadio = asBoolean('1', '', '');
  const enabledRadio = asBoolean('1', '', '1');
  const emptyArray = asStringArray([]);
  const smtpServer = asEnum(['server', 'manual', 'AmazonSES', 'SendGrid'], 'server');

  const settingsSchema = asObject({
    sender: asObject({ name: text, address: text }),
    reply_to: asObject({ name: text, address: text }),
    bounce: asObject({ address: text }),
    subscribe: asObject({
      on_comment: asObject({ enabled: disabledCheckbox, label: asString(t('yesAddMe')), segments: emptyArray }),
      on_register: asObject({ enabled: disabledCheckbox, label: asString(t('yesAddMe')), segments: emptyArray }),
    }),
    subscription: asObject({
      pages: asObject({
        manage: text,
        unsubscribe: text,
        confirmation: text,
        captcha: text,
        confirm_unsubscribe: text,
      }),
      segments: emptyArray,
    }),
    stats_notifications: asObject({
      enabled: enabledCheckbox,
      automated: enabledCheckbox,
      address: text,
    }),
    subscriber_email_notification: asObject({ enabled: enabledRadio, address: text }),
    cron_trigger: asObject({
      method: asEnum(['WordPress', 'MailPoet', 'Linux Cron'], 'WordPress'),
    }),
    tracking: asObject({ enabled: enabledRadio }),
    send_transactional_emails: disabledRadio,
    deactivate_subscriber_after_inactive_days: asEnum(['', '90', '180', '365'], '180'),
    analytics: asObject({ enabled: disabledRadio }),
    '3rd_party_libs': asObject({ enabled: disabledRadio }),
    captcha: asObject({
      type: asEnum(['', 'built-in', 'recaptcha'], 'built-in'),
      recaptcha_site_token: text,
      recaptcha_secret_token: text,
    }),
    logging: asEnum(['everything', 'errors', 'nothing'], 'errors'),
    mta_group: asEnum(['mailpoet', 'website', 'smtp'], 'website'),
    mta: asObject({
      method: asEnum([
        'MailPoet',
        'AmazonSES',
        'SendGrid',
        'PHPMail',
        'SMTP',
      ], 'PHPMail'),
      frequency: asObject({
        emails: asString('25'),
        interval: asString('5'),
      }),
      mailpoet_api_key: text,
      host: text,
      port: text,
      region: asString('us-east-1'),
      access_key: text,
      secret_key: text,
      api_key: text,
      login: text,
      password: text,
      encryption: text,
      authentication: asEnum(['1', '-1'], '1'),
      mailpoet_api_key_state: asObject({
        state: asEnum([
          'valid',
          'invalid',
          'expiring',
          'already_used',
          'check_error',
        ], 'check_error'),
        data: asIs,
      }),
    }),
    mailpoet_smtp_provider: smtpServer,
    smtp_provider: smtpServer,
    web_host: asString('manual'),
    mailpoet_sending_frequency: asEnum(['auto', 'manual'], 'manual'),
    signup_confirmation: asObject({
      enabled: enabledRadio,
      subject: text,
      body: text,
    }),
    woocommerce: asObject({
      use_mailpoet_editor: disabledRadio,
      transactional_email_id: text,
      optin_on_checkout: asObject({
        enabled: enabledRadio,
        segments: emptyArray,
        message: text,
      }),
      accept_cookie_revenue_tracking: asObject({
        enabled: disabledRadio,
        set: enabledRadio,
      }),
    }),
    mailpoet_subscribe_old_woocommerce_customers: asObject({
      enabled: enabledRadio,
    }),
    premium: asObject({
      premium_key: text,
      premium_key_state: asObject({
        state: asEnum([
          'valid',
          'invalid',
          'expiring',
          'already_used',
          'check_error',
        ], 'check_error'),
        data: asIs,
      }),
    }),
    authorized_emails_addresses_check: asIs,
  });
  return settingsSchema(data) as Settings;
}

function asString(defaultValue: string) {
  return (value: any): string => {
    if (value === undefined) return defaultValue;
    if (!value) return '';
    return `${value}`;
  };
}

function asStringArray(defaultValue: string[]) {
  return (value: any): string[] => {
    if (!_.isArray(value)) return defaultValue;
    return value.map(asString(''));
  };
}

function asBoolean<T, F>(trueValue: T, falseValue: F, defaultValue: T | F) {
  return (value: any): T | F => {
    if (value === undefined) return defaultValue;
    if (value === trueValue || value === falseValue) return value;
    if (value) return trueValue;
    return falseValue;
  };
}

function asEnum(choices: string[], defaultValue: string) {
  return (value: any): string => {
    if (!choices.includes(value)) return defaultValue;
    return value;
  };
}

type Schema = {
  [key: string]: ReturnType<
    | typeof asString
    | typeof asStringArray
    | typeof asBoolean
    | typeof asEnum
    | typeof asObject
  >
}
type SchemaResult<T extends Schema> = {
  [key in keyof T]: ReturnType<T[key]>
}
function asObject<T extends Schema>(schema: T) {
  return (value: any): SchemaResult<T> => {
    const object = Object.keys(schema).reduce((result, field) => ({
      [field]: schema[field](value ? value[field] : undefined),
      ...result,
    }), {});
    return object as SchemaResult<T>;
  };
}

function asIs<T>(value: T): T {
  return value;
}
