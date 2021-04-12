import React from 'react';
import MailPoet from 'mailpoet';

import {
  EmailFormItem,
  OnFilterChange,
  SegmentTypes,
  EmailActionTypes,
} from '../types';

import { EmailStatisticsFields } from './email_statistics';

export const EmailSegmentOptions = [
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

export const EmailFields: React.FunctionComponent<Props> = ({ onChange, item }) => (
  <EmailStatisticsFields
    item={item}
    onChange={onChange}
  />
);
