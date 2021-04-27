import React from 'react';
import MailPoet from 'mailpoet';

import {
  EmailActionTypes,
  EmailFormItem,
  OnFilterChange,
  SegmentTypes,
} from '../types';

import { EmailStatisticsFields } from './email_statistics';
import { EmailOpensAbsoluteCountFields } from './email_opens_absolute_count';

export const EmailSegmentOptions = [
  { value: EmailActionTypes.OPENS_ABSOLUTE_COUNT, label: MailPoet.I18n.t('emailActionOpensAbsoluteCount'), group: SegmentTypes.Email },
  { value: EmailActionTypes.OPENED, label: MailPoet.I18n.t('emailActionOpened'), group: SegmentTypes.Email },
  { value: EmailActionTypes.NOT_OPENED, label: MailPoet.I18n.t('emailActionNotOpened'), group: SegmentTypes.Email },
  { value: EmailActionTypes.CLICKED, label: MailPoet.I18n.t('emailActionClicked'), group: SegmentTypes.Email },
  { value: EmailActionTypes.NOT_CLICKED, label: MailPoet.I18n.t('emailActionNotClicked'), group: SegmentTypes.Email },
];

export function validateEmail(formItems: EmailFormItem): boolean {
  // check if the action has the right type
  if (
    !Object
      .values(EmailActionTypes)
      .some((v) => v === formItems.action)
  ) return false;

  if ((formItems.action !== EmailActionTypes.OPENS_ABSOLUTE_COUNT)) {
    return !!formItems.newsletter_id;
  }

  return (
    !!formItems.days
    && !!formItems.opens
    && !!formItems.operator
  );
}

interface Props {
  onChange: OnFilterChange;
  item: EmailFormItem;
}

const componentsMap = {
  [EmailActionTypes.OPENS_ABSOLUTE_COUNT]: EmailOpensAbsoluteCountFields,
  [EmailActionTypes.CLICKED]: EmailStatisticsFields,
  [EmailActionTypes.NOT_CLICKED]: EmailStatisticsFields,
  [EmailActionTypes.OPENED]: EmailStatisticsFields,
  [EmailActionTypes.NOT_OPENED]: EmailStatisticsFields,
};

export const EmailFields: React.FunctionComponent<Props> = ({ onChange, item }) => {
  const Component = componentsMap[item.action];

  if (!Component) return null;

  return (
    <Component
      item={item}
      onChange={onChange}
    />
  );
};
