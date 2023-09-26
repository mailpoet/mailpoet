export type NewsletterType = {
  id: string;
  total_sent: number;
  subject: string;
  segments: { name: string; id?: string }[];
  queue: {
    scheduled_at: string;
    created_at: string;
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
    revenue: {
      value: number;
      formatted: string;
      count: number;
    };
  };
};
