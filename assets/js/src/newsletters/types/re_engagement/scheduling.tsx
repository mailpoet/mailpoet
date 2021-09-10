import React from 'react';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';
import Input from 'common/form/input/input';

interface Props {
  afterTimeNumber: string;
}

export function Scheduling({ afterTimeNumber }: Props): JSX.Element {
  return (
    <>
      <Heading level={4}>{MailPoet.I18n.t('selectEventToSendReEngagementEmail')}</Heading>
      <div className="mailpoet-grid-column mailpoet-flex">
        <p>
          {MailPoet.I18n.t('reEngagementTextPre')}
        </p>
        <Input
          type="text"
          placeholder={MailPoet.I18n.t('reEngagementAterTimeNumberPlaceholder')}
          value={afterTimeNumber}
          dimension="small"
        />
      </div>
    </>
  );
}
