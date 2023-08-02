export type StatsType = {
  email: string;
  total_sent: number;
  open: number;
  machine_open: number;
  click: number;
  engagement_score: number;
  last_engagement?: string;
  last_click?: string;
  last_open?: string;
  last_sending?: string;
  last_page_view?: string;
  last_purchase?: string;
  woocommerce?: {
    currency: string;
    value: number;
    count: number;
    formatted: string;
    formatted_average: string;
  };
};
