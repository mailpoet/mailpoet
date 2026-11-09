import apiFetch from '@wordpress/api-fetch';

export type EmailPreset = {
  id: number;
  title: string;
  status: string;
  description: string;
  subject: string;
  preheader: string;
  author: string;
  updated_at: string;
  edit_url: string;
};

export const createEmailPreset = (
  title: string,
  sourceId?: number,
): Promise<EmailPreset> =>
  apiFetch<{ data: EmailPreset }>({
    path: '/mailpoet/v1/email-presets',
    method: 'POST',
    data: { title, ...(sourceId ? { source_id: sourceId } : {}) },
  }).then((response) => response.data);
