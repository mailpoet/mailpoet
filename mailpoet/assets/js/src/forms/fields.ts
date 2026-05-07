import { createElement } from 'react';
import { __ } from '@wordpress/i18n';
import type { Field } from '@wordpress/dataviews';
import { MailPoet } from 'mailpoet';
import { SegmentTags } from 'common/tag/tags';
import type { FormListingItem } from './api';
import { FormStatusToggle } from './status-toggle';

function getFormPlacement(settings: FormListingItem['settings']): string {
  const placements: string[] = [];
  const placement = settings?.form_placement;
  if (placement?.fixed_bar?.enabled === '1') {
    // translators: placement of the form as a fixed bar
    placements.push(__('Fixed bar', 'mailpoet'));
  }
  if (placement?.below_posts?.enabled === '1') {
    // translators: placement of the form below pages
    placements.push(__('Below pages', 'mailpoet'));
  }
  if (placement?.popup?.enabled === '1') {
    // translators: placement of the form as a popup
    placements.push(__('Pop-up', 'mailpoet'));
  }
  if (placement?.slide_in?.enabled === '1') {
    // translators: placement of the form as a slide-in
    placements.push(__('Slide–in', 'mailpoet'));
  }
  if (placements.length > 0) {
    return placements.join(', ');
  }
  // translators: form placement via theme widget
  return __('Others (widget)', 'mailpoet');
}

export const listFields: Field<FormListingItem>[] = [
  {
    id: 'name',
    label: __('Name', 'mailpoet'),
    type: 'text',
    enableSorting: true,
    enableGlobalSearch: false,
    render: ({ item }) =>
      createElement(
        'a',
        {
          className: 'mailpoet-listing-title',
          href: `admin.php?page=mailpoet-form-editor&id=${item.id}`,
        },
        item.name ? item.name : `(${__('no name', 'mailpoet')})`,
      ),
  },
  {
    id: 'segments',
    label: __('Lists', 'mailpoet'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => {
      const formSegmentIds = new Set(item.segments.map((id) => String(id)));
      const segments = window.mailpoet_segments.filter((segment) =>
        formSegmentIds.has(String(segment.id)),
      );
      return createElement(
        SegmentTags,
        { segments, dimension: 'large' },
        item.settings?.segments_selected_by === 'user'
          ? createElement(
              'span',
              { className: 'mailpoet-tags-prefix' },
              __('User choice:', 'mailpoet'),
            )
          : null,
      );
    },
  },
  {
    id: 'type',
    label: __('Type', 'mailpoet'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) =>
      createElement('span', null, getFormPlacement(item.settings)),
  },
  {
    id: 'status',
    label: __('Status', 'mailpoet'),
    enableSorting: false,
    enableGlobalSearch: false,
    render: ({ item }) => createElement(FormStatusToggle, { form: item }),
  },
  {
    id: 'updated_at',
    label: __('Modified date', 'mailpoet'),
    type: 'datetime',
    enableSorting: true,
    enableGlobalSearch: false,
    render: ({ item }) => {
      if (!item.updated_at) {
        return createElement('span', null, '—');
      }
      return createElement('span', null, MailPoet.Date.full(item.updated_at));
    },
  },
];
