import {
  BaseControl,
  Button,
  DateTimePicker,
  Dropdown,
  PanelBody,
} from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { dateI18n, getSettings } from '@wordpress/date';
import { calendar } from '@wordpress/icons';
import { getContext } from '../../../mailpoet/context';
import { storeName } from '../../../../editor/store';
import { PlainBodyTitle, FormTokenField } from '../../../../editor/components';

export function Edit(): JSX.Element {
  const { selectedStep } = useSelect(
    (select) => ({
      selectedStep: select(storeName).getSelectedStep(),
    }),
    [],
  );

  const scheduledAt = (selectedStep.args?.scheduled_at as string) ?? '';

  const rawSegmentIds = selectedStep.args?.segment_ids
    ? (selectedStep.args.segment_ids as number[])
    : [];

  const validSegments = (getContext().segments ?? []).filter(
    (segment) => segment.type === 'default',
  );
  const selectedSegments = validSegments.filter((segment) =>
    rawSegmentIds.includes(segment.id as number),
  );

  const settings = getSettings();
  const is12HourTime = /a(?!\\)/i.test(
    settings.formats.time
      .toLowerCase()
      .replace(/\\\\/g, '')
      .split('')
      .reverse()
      .join(''),
  );

  const today = new Date().setHours(0, 0, 0, 0);

  const getFormattedDate = () => {
    if (!scheduledAt) {
      return __('Select date and time', 'mailpoet');
    }
    return dateI18n(
      settings.formats.datetime,
      scheduledAt,
      settings.timezone.string,
    );
  };

  return (
    <PanelBody opened>
      <PlainBodyTitle title={__('Trigger settings', 'mailpoet')} />

      <BaseControl
        id="scheduled-date-time"
        label={__('Date and time', 'mailpoet')}
      >
        <Dropdown
          popoverProps={{
            placement: 'left-start',
            offset: 16,
            shift: true,
          }}
          renderToggle={({ isOpen, onToggle }) => (
            <Button
              icon={calendar}
              onClick={onToggle}
              aria-expanded={isOpen}
              variant="tertiary"
              style={{ width: '100%', justifyContent: 'flex-start' }}
            >
              {getFormattedDate()}
            </Button>
          )}
          renderContent={() => (
            <DateTimePicker
              currentDate={scheduledAt || undefined}
              onChange={(date) => {
                void dispatch(storeName).updateStepArgs(
                  selectedStep.id,
                  'scheduled_at',
                  date,
                );
              }}
              is12Hour={is12HourTime}
              isInvalidDate={(date) => date.getTime() < today}
            />
          )}
        />
      </BaseControl>

      <FormTokenField
        label={__('Lists', 'mailpoet')}
        placeholder={__('Select a list', 'mailpoet')}
        value={selectedSegments}
        suggestions={validSegments}
        onChange={(values) => {
          void dispatch(storeName).updateStepArgs(
            selectedStep.id,
            'segment_ids',
            values.map((item) => item.id),
          );
        }}
        __experimentalShowHowTo={false}
      />
    </PanelBody>
  );
}
