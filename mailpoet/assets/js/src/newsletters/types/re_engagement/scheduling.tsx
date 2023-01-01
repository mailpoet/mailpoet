import ReactStringReplace from 'react-string-replace';
import { __ } from '@wordpress/i18n';

import { Heading } from 'common/typography/heading/heading';
import { Input } from 'common/form/input/input';
import { Select } from 'common/form/select/select';
import { Grid } from 'common/grid';
import { onChange } from 'common/functions';

interface Props {
  afterTimeNumber: string;
  afterTimeType: string;
  inactiveSubscribersPeriod: number | undefined;
  updateAfterTimeNumber: (arg: string) => void;
  updateAfterTimeType: (arg: string) => void;
}

export function Scheduling({
  afterTimeNumber,
  afterTimeType,
  updateAfterTimeNumber,
  updateAfterTimeType,
  inactiveSubscribersPeriod,
}: Props): JSX.Element {
  const daysInPeriod = afterTimeType === 'weeks' ? 7 : 30;
  const daysSelected = Number(afterTimeNumber) * daysInPeriod;
  let inactivePeriod = inactiveSubscribersPeriod;
  // so that we can calculate the dates using division and multiplication
  // the inactive subscribers feature stores one year as 365 days.
  // When a user selects 12 months here we multiply 12*30 and that is 360.
  // When comparing those two numbers to decide
  // if the notice needs to be displayed we need the numbers to be equal
  if (inactiveSubscribersPeriod === 365) inactivePeriod = 360;

  return (
    <>
      <Heading level={4}>
        {__('When to send this re-engagement email?', 'mailpoet')}
      </Heading>
      <Grid.CenteredRow className="mailpoet-re-engagement-scheduling">
        <p>{__('After no activity for', 'mailpoet')}</p>
        <Input
          type="text"
          placeholder={__('count', 'mailpoet')}
          value={afterTimeNumber}
          onChange={onChange(updateAfterTimeNumber)}
        />
        <Select value={afterTimeType} onChange={onChange(updateAfterTimeType)}>
          <option value="weeks">weeks</option>
          <option value="months">months</option>
        </Select>
      </Grid.CenteredRow>
      {!!inactiveSubscribersPeriod && inactivePeriod <= daysSelected && (
        <p className="mailpoet-re-engagement-scheduling-note">
          {ReactStringReplace(
            __(
              'Disengaged subscribers will [link]become inactive[/link] after {$months} months and won’t receive this email. Please select a shorter period.',
              'mailpoet',
            ).replace(
              '{$months}',
              `${Math.floor(inactiveSubscribersPeriod / 30)}`,
            ),
            /\[link\](.*?)\[\/link\]/g,
            (match) => (
              <a
                key="link"
                href="https://kb.mailpoet.com/article/264-inactive-subscribers"
                target="_blank"
                rel="noopener noreferrer"
                data-beacon-article="5cbf19622c7d3a026fd3efe1"
              >
                {match}
              </a>
            ),
          )}
        </p>
      )}
    </>
  );
}
