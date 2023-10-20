import { useSelect } from '@wordpress/data';

import {
  EmailActionTypes,
  EmailFormItem,
  FilterProps,
  WordpressRoleFormItem,
} from '../types';
import { storeName } from '../store';

import { EmailOpenStatisticsFields } from './fields/email/email-statistics-opens';
import { EmailClickStatisticsFields } from './fields/email/email-statistics-clicks';
import { EmailOpensAbsoluteCountFields } from './fields/email/email-opens-absolute-count';
import { validateDaysPeriod } from './fields/days-period-field';
import { NumberOfEmailsReceivedFields } from './fields/email/number-of-emails-received';

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
    [EmailActionTypes.OPENED, EmailActionTypes.MACHINE_OPENED].includes(
      formItems.action as EmailActionTypes,
    )
  ) {
    return (
      Array.isArray(formItems.newsletters) && formItems.newsletters.length > 0
    );
  }

  if (
    [
      EmailActionTypes.OPENS_ABSOLUTE_COUNT,
      EmailActionTypes.MACHINE_OPENS_ABSOLUTE_COUNT,
    ].includes(formItems.action as EmailActionTypes)
  ) {
    return (
      validateDaysPeriod(formItems) && !!formItems.opens && !!formItems.operator
    );
  }

  if (EmailActionTypes.NUMBER_RECEIVED === formItems.action) {
    return (
      validateDaysPeriod(formItems) &&
      !!formItems.emails &&
      !!formItems.operator
    );
  }

  return false;
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
  [EmailActionTypes.NUMBER_RECEIVED]: NumberOfEmailsReceivedFields,
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
