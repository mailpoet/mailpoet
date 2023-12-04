import { __ } from '@wordpress/i18n';
import { NewsletterStatus } from './newsletter';

const STATUSES = {
  [NewsletterStatus.Draft]: __('Draft', 'mailpoet'),
  [NewsletterStatus.Scheduled]: __('Scheduled', 'mailpoet'),
  [NewsletterStatus.Sending]: __('Sending', 'mailpoet'),
  [NewsletterStatus.Sent]: __('Sent', 'mailpoet'),
  [NewsletterStatus.Active]: __('Active', 'mailpoet'),
  [NewsletterStatus.Corrupt]: __('Corrupt', 'mailpoet'),
};

export const getNewsletterStatusString = (status: string) =>
  String(STATUSES[status]);
