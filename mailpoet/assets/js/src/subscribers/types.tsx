import type { EngagementScoreType } from './engagement-score-badge-type';

export type StatsType = {
  email: string;
  engagement_score: number | null;
  engagement_score_type: EngagementScoreType;
  last_engagement?: string;
  last_click?: string;
  last_open?: string;
  last_sending?: string;
  last_page_view?: string;
  last_purchase?: string;
  periodic_stats: PeriodicStats[];
  is_woo_active: boolean;
  is_woocommerce_user: boolean;
  avatar_url: string | null;
  subscribed_at: string | null;
  source_label: string | null;
  woocommerce_overview?: WoocommerceOverview;
  profile: SubscriberProfile;
};

export type WoocommerceOverview = {
  orders_count: number;
  total_revenue_formatted: string;
  average_order_value_formatted: string;
  orders_url: string;
};

export type PeriodicStats = {
  key: StatsPeriodKey;
  timeframe: string;
  total_sent: number;
  open: number;
  machine_open: number;
  click: number;
  woocommerce?: {
    currency: string;
    value: number;
    count: number;
    formatted: string;
    formatted_average: string;
  };
};

export type StatsPeriodKey =
  | '7_days'
  | '30_days'
  | '3_months'
  | '12_months'
  | 'lifetime';

export type SubscriberProfile = {
  status: string;
  first_name: string;
  last_name: string;
  email: string;
  shipping_address: string[];
  tags: SubscriberTag[];
  segments: SubscriberSegment[];
  custom_fields: SubscriberCustomField[];
  unsubscribe_reason: string | null;
};

export type SubscriberTag = {
  id: string;
  subscriber_id: string;
  tag_id: string;
  name: string;
};

export type SubscriberSegment = {
  id: string;
  name: string;
};

export type SubscriberCustomField = {
  id: string;
  name: string;
  value: string;
};
