import { FormTokenItem } from '../../../components/form-token-field';

export interface Segment extends FormTokenItem {
  name: string;
  id: number;
  type: string;
  description: string;
  subscribers_count: {
    all: number;
    bounced: number;
    inactive: number;
    subscribed: number;
    trash: number;
    unconfirmed: number;
    confirmed: number;
  };
  subscribers_url: string;
  updated_at: string;
  created_at: string;
  deleted_at?: string;
  average_engagement_score: number;
  filters_connect: string;
}

/**
 * The API returns IDs as strings, which we need to cast to integer.
 * This type account for that.
 */
export type RawSegment = Omit<Segment, 'id'> & { id: string };
