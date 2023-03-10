import { useSelect } from '@wordpress/data';
import { __, _x } from '@wordpress/i18n';

import {
  EmailActionTypes,
  EmailFormItem,
  SegmentTypes,
  WordpressRoleFormItem,
} from '../types';

import { EmailOpenStatisticsFields } from './email_statistics_opens';
import { EmailClickStatisticsFields } from './email_statistics_clicks';
import { EmailOpensAbsoluteCountFields } from './email_opens_absolute_count';

export const EmailSegmentOptions = [
  {
    value: EmailActionTypes.OPENS_ABSOLUTE_COUNT,
    label: __('# of opens', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT,
    label: __('# of machine-opens', 'mailpoet'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.OPENED,
    label: _x(
      'opened',
      'Dynamic segment creation: when newsletter was opened',
      'mailpoet',
    ),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.MACHINE_OPENED,
    label: _x(
      'machine-opened',
      'Dynamic segment creation: list of all subscribers that opened the newsletter automatically in the background',
      'mailpoet',
    ),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.CLICKED,
    label: _x(
      'clicked',
      'Dynamic segment creation: when a newsletter link was clicked',
      'mailpoet',
    ),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.CLICKED_ANY,
    label: _x(
      'clicked any email',
      'Dynamic segment creation: when a newsletter link in any email was clicked',
      'mailpoet',
    ),
    group: SegmentTypes.Email,
  },
];

export function validateEmail(formItems: EmailFormItem): boolean {
  // check if the action has the right type
  if (!Object.values(EmailActionTypes).some((v) => v === formItems.action))
    return false;

  if (formItems.action === EmailActionTypes.CLICKED_ANY) {
    return true;
  }

  if (formItems.action === EmailActionTypes.CLICKED) {
    return !!formItems.newsletter_id;
  }

  if (
    formItems.action !== EmailActionTypes.OPENS_ABSOLUTE_COUNT &&
    formItems.action !== EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT
  ) {
    // EmailActionTypes.OPENED, EmailActionTypes.MACHINE_OPENED
    return (
      Array.isArray(formItems.newsletters) && formItems.newsletters.length > 0
    );
  }

  return !!formItems.days && !!formItems.opens && !!formItems.operator;
}

const componentsMap = {
  [EmailActionTypes.OPENS_ABSOLUTE_COUNT]: EmailOpensAbsoluteCountFields,
  [EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT]:
    EmailOpensAbsoluteCountFields,
  [EmailActionTypes.CLICKED]: EmailClickStatisticsFields,
  [EmailActionTypes.OPENED]: EmailOpenStatisticsFields,
  [EmailActionTypes.MACHINE_OPENED]: EmailOpenStatisticsFields,
  [EmailActionTypes.CLICKED_ANY]: null,
};

type Props = {
  filterIndex: number;
};

export function EmailFields({ filterIndex }: Props): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) =>
      select('mailpoet-dynamic-segments-form').getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const Component = componentsMap[segment.action];

  if (!Component) return null;

  return <Component filterIndex={filterIndex} />;
}
