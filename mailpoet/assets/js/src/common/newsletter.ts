import { Segment } from '../form/types';

export enum NewsletterType {
  Standard = 'standard',
  Automatic = 'automatic',
  Welcome = 'welcome',
  Notification = 'notification',
  NotificationHistory = 'notification_history',
  WCTransactional = 'wc_transactional',
  ReEngagement = 're_engagement',
  Automation = 'automation',
}

export enum NewsletterStatus {
  Draft = 'draft',
  Scheduled = 'scheduled',
  Sending = 'sending',
  Sent = 'sent',
  Active = 'active',
  Corrupt = 'corrupt',
}

export enum NewsletterOptionGroup {
  WooCommerce = 'woocommerce',
}

export type NewsLetter = {
  campaign_name: string | null;
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
    group: NewsletterOptionGroup;
    intervalType?: string;
    event: string;
    automationId?: string;
    afterTimeNumber: number | string;
    afterTimeType: string;
    filterSegmentId?: string;
  };
  parent_id: null | string;
  preheader: string;
  queue: Record<string, unknown> & {
    scheduled_at: string;
    count_processed: string;
    count_total: string;
    meta: Record<string, unknown> & {
      filterSegment?: {
        name?: string;
      };
    };
  };
  reply_to_address: string;
  reply_to_name: string;
  segments: Segment[];
  sender_address: string;
  sender_name: string;
  sent_at: null | string;
  status: NewsletterStatus;
  subject: string;
  type: NewsletterType;
  unsubscribe_token: string;
  updated_at: string;
  logs: string[];
  wp_post_id: null | number;
};
