export type Settings = {
  sender: {
    name: string
    address: string
  }
  reply_to: {
    name: string
    address: string
  }
  subscribe: {
    on_comment: {
      enabled: '1' | '0'
      label: string
      segments: string[]
    }
    on_register: {
      enabled: '1' | '0'
      label: string
      segments: string[]
    }
  }
  subscription: {
    pages: {
      manage: string
      unsubscribe: string
      confirmation: string
      captcha: string
    }
    segments: string[]
  }
  stats_notifications: {
    enabled: '0' | '1'
    automated: '0' | '1'
    address: string
  }
  subscriber_email_notification: {
    enabled: '' | '1'
    address: string
  }
  cron_trigger: {
    method: 'WordPress' | 'MailPoet' | 'Linux Cron'
  }
  tracking: {
    enabled: '' | '1'
  }
  send_transactional_emails: '' | '1'
  deactivate_subscriber_after_inactive_days: '' | '90' | '180' | '365'
  analytics: {
    enabled: '' | '1'
  }
  captcha: {
    type: 'built-in' | 'recaptcha' | ''
    recaptcha_site_token: string
    recaptcha_secret_token: string
  }
  logging: 'everything' | 'errors' | 'nothing'
  mta_group: 'mailpoet' | 'website' | 'smtp'
  mta: {
    method: 'MailPoet' | 'AmazonSES' | 'SendGrid' | 'PHPMail' | 'SMTP'
    frequency: {
      emails: string
      interval: string
    }
    mailpoet_api_key: string
    host: string
    port: string
    region:
    | 'us-east-1'
    | 'us-west-2'
    | 'eu-west-1'
    | 'eu-central-1'
    | 'ap-south-1'
    | 'ap-southeast-2'
    access_key: string
    secret_key: string
    api_key: string
    login: string
    password: string
    encryption: string
    authentication: '1' | '-1'
    mailpoet_api_key_state: {
      state:
      | 'valid'
      | 'invalid'
      | 'expiring'
      | 'already_used'
      | 'check_error'
      data: any
    }
  }
  mailpoet_smtp_provider: 'server' | 'manual' | 'AmazonSES' | 'SendGrid'
  smtp_provider: 'server' | 'manual' | 'AmazonSES' | 'SendGrid',
  web_host:
  | 'manual'
  | '1and1'
  | 'bluehost'
  | 'df'
  | 'dreamhost'
  | 'free'
  | 'froghost'
  | 'godaddy'
  | 'goneo'
  | 'googleapps'
  | 'greengeeks'
  | 'hawkhost'
  | 'hivetec'
  | 'hostgator'
  | 'hosting2go'
  | 'hostmonster'
  | 'infomaniak'
  | 'justhost'
  | 'laughingsquid'
  | 'lunarpages'
  | 'mediatemple'
  | 'netfirms'
  | 'netissime'
  | 'one'
  | 'ovh'
  | 'phpnet'
  | 'planethoster'
  | 'rochen'
  | 'site5'
  | 'siteground'
  | 'synthesis'
  | 'techark'
  | 'vexxhost'
  | 'vps'
  | 'webcity'
  | 'westhost'
  | 'wpwebhost'
  mailpoet_sending_frequency: 'auto' | 'manual'

  signup_confirmation: {
    enabled: '1' | ''
    subject: string
    body: string
  }
  woocommerce: {
    use_mailpoet_editor: '1' | ''
    transactional_email_id: string,
    optin_on_checkout: {
      enabled: '1' | ''
      message: string
    }
    accept_cookie_revenue_tracking: {
      enabled: '1' | '',
      set: '1' | ''
    }
  }
  mailpoet_subscribe_old_woocommerce_customers: {
    enabled: '1' | ''
  }
  premium: {
    premium_key: string
    premium_key_state: {
      state:
      | 'valid'
      | 'invalid'
      | 'expiring'
      | 'already_used'
      | 'check_error'
      data: any
    }
  }
}
type Segment = {
  id: string
  name: string
  subscribers: string
}
type Page = {
  id: number
  title: string
  url: {
    unsubscribe: string
    manage: string
    confirm: string
  }
}
export type State = {
  data: Settings
  segments: Segment[]
  pages: Page[]
  flags: {
    woocommerce: boolean
    newUser: boolean
    error: boolean
    mssKeyValid: boolean
    premiumKeyValid: boolean
    premiumPluginInstalled: boolean
  }
  save: {
    inProgress: boolean
    error: any
  }
}

export type Action =
  | { type: 'SET_SETTING'; value: any; path: string[] }
  | { type: 'SET_ERROR_FLAG'; value: boolean }
  | { type: 'SAVE_STARTED' }
  | { type: 'SAVE_DONE' }
  | { type: 'SAVE_FAILED'; error: any }

export type PremiumStatus =
  | 'invalid'
  | 'valid_premium_plugin_not_installed'
  | 'valid_premium_plugin_not_active'
  | 'valid_premium_plugin_active'
  | 'valid_premium_plugin_being_installed'
  | 'valid_premium_plugin_being_activated'

export type MssStatus =
  | 'invalid'
  | 'valid_mss_not_active'
  | 'valid_mss_active'
