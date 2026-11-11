/**
 * The `mailpoet_data` field of the email post, from
 * `EmailApiController::getEmailData()`.
 */
export type MailPoetEmailData = {
  type?: string;
  subject?: string;
  preheader?: string;
  sender_name?: string;
  sender_address?: string;
  reply_to_name?: string;
  reply_to_address?: string;
  scheduled_at?: string | null;
  schedule_mode?: string | null;
  scheduled_local_date?: string | null;
  scheduled_local_time?: string | null;
  utm_campaign?: string;
  segment_ids?: number[];
  show_in_archive?: boolean;
};
