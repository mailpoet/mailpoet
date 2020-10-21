import React from 'react';
import ReactStringReplace from 'react-string-replace';

import { Label, Inputs } from 'settings/components';
import { t, onChange } from 'common/functions';
import Select from 'common/form/select/select';
import { useSetting } from 'settings/store/hooks';

const MINUTES_PER_DAY = 1440;
const SECONDS_PER_DAY = 86400;

type Props = {
  recommendedEmails: number
  recommendedInterval: number
}
export default function SendingFrequency({ recommendedEmails, recommendedInterval }: Props) {
  const [frequency, setFrequency] = useSetting('mailpoet_sending_frequency');
  const [frequencyEmails, setFrequencyEmails] = useSetting('mta', 'frequency', 'emails');
  const [frequencyInterval, setFrequencyInterval] = useSetting('mta', 'frequency', 'interval');
  React.useEffect(() => {
    if (frequency === 'auto') {
      setFrequencyEmails(`${recommendedEmails}`);
      setFrequencyInterval(`${recommendedInterval}`);
    }
  }, [frequency, recommendedEmails, recommendedInterval, setFrequencyEmails, setFrequencyInterval]);

  const dailyEmails = Math.floor(
    (MINUTES_PER_DAY * parseInt(frequencyEmails, 10)) / parseInt(frequencyInterval, 10)
  );
  const emailsPerSecond = Math.floor((dailyEmails / SECONDS_PER_DAY) * 10) / 10;

  return (
    <>
      <Label title={t('sendingFrequency')} htmlFor="mailpoet_sending_frequency" />
      <Inputs>
        <Select
          id="mailpoet_sending_frequency"
          value={frequency}
          onChange={onChange(setFrequency)}
          isMinWidth
          dimension="small"
        >
          <option value="auto">{t('recommendedTitle')}</option>
          <option value="manual">{t('ownFrequency')}</option>
        </Select>
        {frequency === 'manual' && (
          <>
            <br />
            <input
              id="other_frequency_emails"
              type="number"
              min="1"
              max="1000"
              value={frequencyEmails}
              onChange={onChange(setFrequencyEmails)}
            />
            {' '}
            {t('emails')}
            <Select
              id="other_frequency_interval"
              value={frequencyInterval}
              onChange={onChange(setFrequencyInterval)}
              isMinWidth
              dimension="small"
            >
              <option value="1">every minute</option>
              <option value="2">every 2 minutes</option>
              <option value="5">every 5 minutes (recommended)</option>
              <option value="10">every 10 minutes</option>
              <option value="15">every 15 minutes</option>
              <option value="30">every 30 minutes</option>
            </Select>
          </>
        )}
        {frequency === 'auto' && (
          <span>
            {t('xEmails').replace('%1$s', frequencyEmails)}
            {' '}
            {formatInterval(frequencyInterval)}
            {'. '}
          </span>
        )}
        <span>
          {ReactStringReplace(
            t('thatsXEmailsPerDay').replace('%1$s', dailyEmails.toLocaleString()),
            /<strong>(.*?)<\/strong>/g,
            (match, i) => <strong key={i}>{match}</strong>
          )}
        </span>
        <br />
        {emailsPerSecond > 1 && (
          <>
            <br />
            <span className="mailpoet_emails_per_second_warning">
              {ReactStringReplace(
                t('thatsXEmailsPerSecond').replace('%1$s', emailsPerSecond.toLocaleString()),
                /<strong>(.*?)<\/strong>/g,
                (match, i) => <strong key={i}>{match}</strong>
              )}
            </span>
            <br />
          </>
        )}
        {frequency === 'manual' && (
          <>
            <br />
            <span>
              {ReactStringReplace(
                t('frequencyWarning').replace('%1$s', emailsPerSecond.toLocaleString()),
                /<strong>(.*?)<\/strong>/g,
                (match, i) => <strong key={i}>{match}</strong>
              )}
            </span>
          </>
        )}
      </Inputs>
    </>
  );
}

function formatInterval(minutes: string): string {
  const value = Math.floor(parseInt(minutes, 10));
  if (value > 60) return t('everyHours').replace('%1$d', `${value / 60}`);
  if (value === 60) return t('everyHour');
  if (value > 1) return t('everyMinutes').replace('%1$d', `${value}`);
  return t('everyMinute');
}
