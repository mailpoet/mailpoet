export enum NewsletterType {
  Standard = 'standard',
  Automatic = 'automatic',
  Welcome = 'welcome',
  Notification = 'notification',
  NotificationHistory = 'notification_history',
  WCTransactional = 'wc_transactional',
  ReEngagement = 're_engagement',
}

export enum NewsletterStatus {
  Draft = 'draft',
  Scheduled = 'scheduled',
  Sending = 'sending',
  Sent = 'sent',
  Active = 'active',
}

export type NewsLetter = {
  body: {
    blockDefaults: unknown;
    content: unknown;
    globalStyles: {
      body: CSSStyleDeclaration;
      h1: CSSStyleDeclaration;
      h2: CSSStyleDeclaration;
      h3: CSSStyleDeclaration;
      link: CSSStyleDeclaration;
      text: CSSStyleDeclaration;
      wrapper: CSSStyleDeclaration;
    };
  };
  created_at: string;
  deleted_at: null | string;
  ga_campaign: string;
  hash: string;
  id: string;
  options: {
    isScheduled: string;
    scheduledAt: string;
    disabled?: string;
  };
  parent_id: null | string;
  preheader: string;
  queue: boolean;
  reply_to_address: string;
  reply_to_name: string;
  segments: Array<unknown>;
  sender_address: string;
  sender_name: string;
  sent_at: null | string;
  status: NewsletterStatus;
  subject: string;
  type: NewsletterType;
  unsubscribe_token: string;
  updated_at: string;
};
