import { useState } from 'react';
import { t } from 'common/functions';
import { Input } from 'common/form/input/input';
import { Label, Inputs, SegmentsSelect } from 'settings/components';
import { Datepicker } from 'common/datepicker/datepicker';
import { MailPoet } from 'mailpoet';
import { __ } from '@wordpress/i18n';
import { CopyToClipboardButton } from 'common/button/copy-to-clipboard-button';

type Props = {
  name: 'mailpoet_archive' | 'mailpoet_subscribers_count';
  title: string;
  description: string;
};

export function Shortcode({ name, title, description }: Props) {
  const [segments, setSegments] = useState([]);
  const [lastNDays, setLastNDays] = useState<string>('');
  const [startDate, setStartDate] = useState<Date | null>(null);
  const [endDate, setEndDate] = useState<Date | null>(null);
  const [subjectContains, setSubjectContains] = useState<string>('');
  const [limit, setLimit] = useState<string>('');

  let shortcode = `[${name}`;

  if (segments.length) {
    shortcode += ` segments="${segments.join(',')}"`;
  }

  if (startDate) {
    shortcode += ` start_date="${MailPoet.Date.format(startDate, {
      format: 'Y-m-d',
    })}"`;
  }

  if (endDate) {
    shortcode += ` end_date="${MailPoet.Date.format(endDate, {
      format: 'Y-m-d',
    })}"`;
  }

  if (lastNDays && parseInt(lastNDays, 10) > 0) {
    shortcode += ` in_the_last_days="${lastNDays}"`;
  }

  if (subjectContains && subjectContains.length > 0) {
    shortcode += ` subject_contains="${subjectContains}"`;
  }

  if (limit && parseInt(limit, 10) > 0) {
    shortcode += ` limit="${limit}"`;
  }

  shortcode += ']';

  const selectText = (event) => {
    event.target.focus();
    event.target.select();
  };
  return (
    <>
      <Label
        title={title}
        description={description}
        htmlFor={`${name}-shortcode`}
      />
      <Inputs>
        <Input
          dimension="small"
          readOnly
          type="text"
          value={shortcode}
          onClick={selectText}
          id={`${name}-shortcode`}
        />
        <CopyToClipboardButton
          variant="secondary"
          targetId={`${name}-shortcode`}
          alwaysSelectText
        />
        <br />
        <SegmentsSelect
          value={segments}
          setValue={setSegments}
          id={`${name}-shortcode-segments`}
          placeholder={t('leaveEmptyToDisplayAll')}
          segmentsSelector="getSegments"
        />
        {name === 'mailpoet_archive' && (
          <>
            <br />
            <Input
              type="number"
              min="1"
              max="365000"
              dimension="small"
              tooltip={__(
                'Include newsletters sent no more than this many days ago. This overrides start and end dates.',
                'mailpoet',
              )}
              name="in_the_last_days"
              placeholder={__('In the last days', 'mailpoet')}
              onChange={(event) => {
                const inputValue = event.target.value.trim();

                if (inputValue === '') {
                  setLastNDays('');
                  return;
                }

                const newValue = Number(inputValue);

                if (Number.isInteger(newValue) && newValue > 0) {
                  setStartDate(null);
                  setEndDate(null);
                  setLastNDays(inputValue);
                }
              }}
              value={lastNDays}
            />
            <br />
            <Datepicker
              dimension="small"
              dateFormat="MMMM d, yyyy"
              onChange={(value): void => {
                setStartDate(value);
                if (value !== null) {
                  setLastNDays('');
                }
              }}
              selected={startDate}
              maxDate={endDate}
              placeholderText={__('Start date', 'mailpoet')}
              isClearable
            />
            <Datepicker
              dimension="small"
              dateFormat="MMMM d, yyyy"
              onChange={(value): void => {
                setEndDate(value);
                if (value !== null) {
                  setLastNDays('');
                }
              }}
              selected={endDate}
              minDate={startDate}
              placeholderText={__('End date', 'mailpoet')}
              isClearable
            />
            <br />
            <Input
              dimension="small"
              type="text"
              value={subjectContains}
              onChange={(event) => setSubjectContains(event.target.value)}
              placeholder={__('Subject lines containing', 'mailpoet')}
            />
            <br />
            <Input
              type="number"
              min="1"
              max="1000"
              dimension="small"
              placeholder={__('Maximum number to display', 'mailpoet')}
              onChange={(event) => {
                const inputValue = event.target.value.trim();

                if (inputValue === '') {
                  setLimit('');
                  return;
                }

                const newValue = Number(inputValue);

                if (Number.isInteger(newValue) && newValue > 0) {
                  setLimit(inputValue);
                }
              }}
              value={limit}
            />
          </>
        )}
      </Inputs>
    </>
  );
}
