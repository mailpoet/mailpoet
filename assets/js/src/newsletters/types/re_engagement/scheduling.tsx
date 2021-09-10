import React from 'react';
import MailPoet from 'mailpoet';
import Heading from 'common/typography/heading/heading';
import Input from 'common/form/input/input';
import Select from 'common/form/select/select';

interface Props {
  afterTimeNumber: string;
  afterTimeType: string;
  inactiveSubscribersPeriod: number;
}

export function Scheduling({
  afterTimeNumber,
  afterTimeType,
  inactiveSubscribersPeriod,
}: Props): JSX.Element {
  const daysInPeriod = afterTimeType === 'weeks' ? 7 : 30;
  const daysSelected = Number(afterTimeNumber) * daysInPeriod;
  return (
    <>
      <Heading level={4}>{MailPoet.I18n.t('selectEventToSendReEngagementEmail')}</Heading>
      <div className="mailpoet-grid-column mailpoet-flex mailpoet-re-engagement-scheduling">
        <p>
          {MailPoet.I18n.t('reEngagementTextPre')}
        </p>
        <Input
          type="text"
          placeholder={MailPoet.I18n.t('reEngagementAterTimeNumberPlaceholder')}
          value={afterTimeNumber}
          dimension="small"
        />
        <Select
          isMinWidth
          value={afterTimeType}
          dimension="small"
        >
          <option value="weeks">weeks</option>
          <option value="months">months</option>
        </Select>
      </div>
      {
        (inactiveSubscribersPeriod <= daysSelected) && (
          <div>note</div>
        )
      }
    </>
  );
}
