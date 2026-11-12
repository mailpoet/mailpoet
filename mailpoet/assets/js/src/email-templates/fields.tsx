import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { Badge } from '@wordpress/ui';
import { MailPoet } from 'mailpoet';
import type { EmailPreset } from 'common/email-presets';
import { PresetThumbnail } from './thumbnail';

export const presetFields: Field<EmailPreset>[] = [
  {
    id: 'title',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableSorting: true,
    enableHiding: false,
    render: ({ item }) =>
      item.status === 'trash' ? (
        <span className="mailpoet-listing-title">{item.title}</span>
      ) : (
        <a className="mailpoet-listing-title" href={item.edit_url}>
          {item.title}
        </a>
      ),
  },
  {
    id: 'thumbnail',
    label: __('Preview', 'mailpoet'),
    enableSorting: false,
    render: ({ item }) => (
      <PresetThumbnail
        key={`${item.id}:${item.updated_at}`}
        id={item.id}
        cacheKey={`${item.id}:${item.updated_at}`}
        title={item.title}
      />
    ),
  },
  {
    id: 'description',
    label: __('Description', 'mailpoet'),
    enableSorting: false,
    render: ({ item }) => <span>{item.description}</span>,
  },
  {
    id: 'source',
    label: __('Source', 'mailpoet'),
    enableSorting: false,
    render: () => <Badge intent="stable">{__('Yours', 'mailpoet')}</Badge>,
  },
  {
    id: 'author',
    label: __('Author', 'mailpoet'),
    enableSorting: false,
    render: ({ item }) => <span>{item.author}</span>,
  },
  {
    id: 'updated_at',
    label: __('Last modified', 'mailpoet'),
    type: 'datetime',
    enableSorting: true,
    render: ({ item }) =>
      item.updated_at ? (
        <span>{MailPoet.Date.short(item.updated_at)}</span>
      ) : null,
  },
];
