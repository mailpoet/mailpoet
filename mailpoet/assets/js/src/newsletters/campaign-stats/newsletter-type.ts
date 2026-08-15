export type NewsletterType = {
  id: string;
  total_sent: number;
  subject: string;
  campaign_name: string;
  segments: { name: string; id?: string }[];
  queue: {
    scheduled_at: string;
    created_at: string;
    meta: Record<string, unknown> & {
      filterSegment?: {
        name?: string;
      };
    };
    status?: string;
  };
  sender_address?: string;
  reply_to_address?: string;
  sender_name?: string;
  reply_to_name?: string;
  ga_campaign?: string;
  preview_url: string;
  clicked_links: { cnt: string; url: string }[];
  statistics: {
    clicked: number;
    opened: number;
    machineOpened: number;
    unsubscribed: number;
    bounced: number;
    // Recipients whose consent kept them out of the open/click rates, the
    // denominator that leaves, and its share of the audience (0-100).
    notTracked?: number;
    trackedSent?: number;
    trackingCoverage?: number;
    revenue: {
      value: number;
      formatted: string;
      count: number;
    };
    unsubscribeReasons?: { reason: string; count: string | number }[];
  };
  type: string;
  status: string;
  wp_post_id?: number;
  created_at: string;
  sent_at?: string;
};
