export type Settings = {
  sender: {
    name: string;
    address: string;
  };
  reply_to: {
    name: string;
    address: string;
  };
  bounce: {
    address: string;
  };
  subscribe: {
    on_comment: {
      enabled: '1' | '0';
      label: string;
      segments: string[];
    };
    on_register: {
      enabled: '1' | '0';
      label: string;
      segments: string[];
    };
  };
  reEngagement: {
    page: string;
  };
  subscription: {
    pages: {
      manage: string;
      unsubscribe: string;
      confirmation: string;
      captcha: string;
      confirm_unsubscribe: string;
    };
    segments: string[];
  };
  stats_notifications: {
    enabled: '0' | '1';
    automated: '0' | '1';
    address: string;
  };
  subscriber_email_notification: {
    enabled: '' | '1';
    address: string;
  };
  cron_trigger: {
    method: 'WordPress' | 'MailPoet' | 'Linux Cron';
  };
  tracking: {
    level: 'full' | 'basic' | 'partial';
  };
  '3rd_party_libs': {
    enabled: '' | '1';
  };
  send_transactional_emails: '' | '1';
  deactivate_subscriber_after_inactive_days: '' | '90' | '180' | '365' | '540';
  analytics: {
    enabled: '' | '1';
  };
  captcha: {
    type: 'built-in' | 'recaptcha' | 'recaptcha-invisible' | '';
    recaptcha_site_token: string;
    recaptcha_secret_token: string;
    recaptcha_invisible_site_token: string;
    recaptcha_invisible_secret_token: string;
  };
  logging: 'everything' | 'errors' | 'nothing';
  mta_group: 'mailpoet' | 'website' | 'smtp';
  mta: {
    method: 'MailPoet' | 'AmazonSES' | 'SendGrid' | 'PHPMail' | 'SMTP';
    frequency: {
      emails: string;
      interval: string;
    };
    mailpoet_api_key: string;
    host: string;
    port: string;
    region: string;
    access_key: string;
    secret_key: string;
    api_key: string;
    login: string;
    password: string;
    encryption: string;
    authentication: '1' | '-1';
    mailpoet_api_key_state: {
      state: 'valid' | 'invalid' | 'expiring' | 'already_used' | 'check_error';
      data: Record<string, unknown>;
    };
  };
  mailpoet_smtp_provider: 'server' | 'manual' | 'AmazonSES' | 'SendGrid';
  smtp_provider: 'server' | 'manual' | 'AmazonSES' | 'SendGrid';
  web_host: string;
  mailpoet_sending_frequency: 'auto' | 'manual';
  signup_confirmation: {
    enabled: '1' | '';
    subject: string;
    body: string;
  };
  woocommerce: {
    use_mailpoet_editor: '1' | '';
    transactional_email_id: string;
    optin_on_checkout: {
      enabled: '1' | '';
      segments: string[];
      message: string;
    };
    accept_cookie_revenue_tracking: {
      set: '1' | '';
    };
  };
  mailpoet_subscribe_old_woocommerce_customers: {
    enabled: '1' | '';
  };
  premium: {
    premium_key: string;
    premium_key_state: {
      state: 'valid' | 'invalid' | 'expiring' | 'already_used' | 'check_error';
      data: Record<string, unknown>;
    };
  };
  authorized_emails_addresses_check: null | {
    invalid_sender_address?: string;
    invalid_senders_in_newsletters?: Array<{
      subject: string;
      sender_address: string;
      newsletter_id: number | string;
    }>;
  };
};
type Segment = {
  id: string;
  name: string;
  subscribers: string;
  type: 'default' | 'wp_users' | 'woocommerce_users' | 'dynamic';
};
type Page = {
  id: number;
  title: string;
  url: {
    unsubscribe: string;
    manage: string;
    confirm: string;
  };
};
type Hosts = {
  web: {
    [key: string]: {
      name: string;
      emails: number;
      interval: number;
    };
  };
  smtp: {
    AmazonSES: {
      emails: number;
      interval: number;
      regions: {
        [key: string]: string;
      };
    };
    SendGrid: {
      emails: number;
      interval: number;
    };
  };
};

export enum PremiumStatus {
  INVALID,
  VALID_PREMIUM_PLUGIN_NOT_INSTALLED,
  VALID_PREMIUM_PLUGIN_NOT_ACTIVE,
  VALID_PREMIUM_PLUGIN_ACTIVE,
  VALID_PREMIUM_PLUGIN_BEING_INSTALLED,
  VALID_PREMIUM_PLUGIN_BEING_ACTIVATED,
}

export enum MssStatus {
  INVALID,
  VALID_MSS_NOT_ACTIVE,
  VALID_MSS_ACTIVE,
}

export enum PremiumInstallationStatus {
  INSTALL_INSTALLING,
  INSTALL_ACTIVATING,
  INSTALL_DONE,
  INSTALL_INSTALLING_ERROR,
  INSTALL_ACTIVATING_ERROR,
  ACTIVATE_ACTIVATING,
  ACTIVATE_DONE,
  ACTIVATE_ERROR,
}

export type KeyActivationState = {
  key: string;
  isKeyValid: boolean;
  premiumStatus: PremiumStatus;
  premiumMessage: string;
  mssStatus: MssStatus;
  mssMessage: string;
  premiumInstallationStatus: PremiumInstallationStatus;
  fromAddressModalCanBeShown: boolean;
  inProgress: boolean;
  congratulatoryMssEmailSentTo: string | null;
  code?: number;
  downloadUrl?: string;
  activationUrl?: string;
};

export type ReEngagement = {
  showNotice: boolean;
  action?: string;
};

export enum TestEmailState {
  SENDING,
  NONE,
  SUCCESS,
  FAILURE,
}

export type State = {
  data: Settings;
  segments: Segment[];
  pages: Page[];
  paths: {
    root: string;
    plugin: string;
  };
  flags: {
    woocommerce: boolean;
    membersPlugin: boolean;
    builtInCaptcha: boolean;
    newUser: boolean;
    error: boolean;
  };
  save: {
    inProgress: boolean;
    error: string[];
  };
  testEmail: {
    state: TestEmailState;
    error: string[];
  };
  keyActivation: KeyActivationState;
  hosts: Hosts;
  reEngagement: ReEngagement;
};

export type Action =
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  | { type: 'SET_SETTING'; value: any; path: string[] }
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  | { type: 'SET_SETTINGS'; value: any }
  | { type: 'SET_ERROR_FLAG'; value: boolean }
  | { type: 'SAVE_STARTED' }
  | { type: 'SAVE_DONE' }
  | { type: 'SAVE_FAILED'; error: string[] }
  | { type: 'UPDATE_KEY_ACTIVATION_STATE'; fields: Partial<KeyActivationState> }
  | { type: 'SET_RE_ENGAGEMENT_NOTICE'; value: ReEngagement }
  | { type: 'START_TEST_EMAIL_SENDING' }
  | { type: 'TEST_EMAIL_SUCCESS' }
  | { type: 'TEST_EMAIL_FAILED'; error: string[] };
