import { useSelect } from '@wordpress/data';

import {
  EmailActionTypes,
  EmailFormItem,
  FilterProps,
  WordpressRoleFormItem,
} from '../types';
import { storeName } from '../store';

import { EmailOpenStatisticsFields } from './fields/email/email_statistics_opens';
import { EmailClickStatisticsFields } from './fields/email/email_statistics_clicks';
import { EmailOpensAbsoluteCountFields } from './fields/email/email_opens_absolute_count';

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
  [EmailActionTypes.WAS_SENT]: EmailOpenStatisticsFields,
  [EmailActionTypes.MACHINE_OPENED]: EmailOpenStatisticsFields,
  [EmailActionTypes.CLICKED_ANY]: null,
};

export function EmailFields({ filterIndex }: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const Component = componentsMap[segment.action];

  if (!Component) return null;

  return <Component filterIndex={filterIndex} />;
}
