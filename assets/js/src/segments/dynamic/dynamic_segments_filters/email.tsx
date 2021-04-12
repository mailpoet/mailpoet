import React from 'react';
import MailPoet from 'mailpoet';

import {
  EmailFormItem,
  OnFilterChange,
  SegmentTypes,
  EmailActionTypes,
} from '../types';

import { EmailStatisticsFields } from './email_statistics';
import { EmailOpensAbsoluteCountFields } from './email_opens_absolute_count';

export const EmailSegmentOptions = [
  { value: 'opensAbsoluteCount', label: MailPoet.I18n.t('emailActionOpensAbsoluteCount'), group: SegmentTypes.Email },
  { value: 'opened', label: MailPoet.I18n.t('emailActionOpened'), group: SegmentTypes.Email },
  { value: 'notOpened', label: MailPoet.I18n.t('emailActionNotOpened'), group: SegmentTypes.Email },
  { value: 'clicked', label: MailPoet.I18n.t('emailActionClicked'), group: SegmentTypes.Email },
  { value: 'notClicked', label: MailPoet.I18n.t('emailActionNotClicked'), group: SegmentTypes.Email },
];

export function validateEmail(formItems: EmailFormItem): boolean {
  return (
    (
      Object
        .values(EmailActionTypes)
        .some((v) => v === formItems.action)
    )
    && !!formItems.newsletter_id
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
