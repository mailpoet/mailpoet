import MailPoet from 'mailpoet';
import { useSelect } from '@wordpress/data';

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
    label: MailPoet.I18n.t('emailActionOpensAbsoluteCount'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT,
    label: MailPoet.I18n.t('emailActionMachineOpensAbsoluteCount'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.OPENED,
    label: MailPoet.I18n.t('emailActionOpened'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.MACHINE_OPENED,
    label: MailPoet.I18n.t('emailActionMachineOpened'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.CLICKED,
    label: MailPoet.I18n.t('emailActionClicked'),
    group: SegmentTypes.Email,
  },
  {
    value: EmailActionTypes.CLICKED_ANY,
    label: MailPoet.I18n.t('emailActionClickedAnyEmail'),
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
