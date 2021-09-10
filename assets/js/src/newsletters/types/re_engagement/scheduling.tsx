import React from 'react';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';
import Input from 'common/form/input/input';
import Select from 'common/form/select/select';

interface Props {
  afterTimeNumber: string;
  afterTimeType: string;
}

export function Scheduling({ afterTimeNumber, afterTimeType }: Props): JSX.Element {
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
        <Select isMinWidth value={afterTimeType}>
          <option value="weeks">weeks</option>
          <option value="months">months</option>
        </Select>
      </div>
    </>
  );
}
