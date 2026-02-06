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

/**
 * Sidebar edit panel for the scheduled date/time trigger.
 *
 * Renders two controls:
 * 1. A date/time picker shown inside a Dropdown popover (keeps the sidebar compact
 *    instead of rendering the full calendar inline). Pattern borrowed from
 *    mailpoet-email-editor-integration/sidebar/components/scheduled-row.tsx.
 * 2. A segment selector using FormTokenField (same pattern as SomeoneSubscribesTrigger's
 *    list-panel.tsx). Segments are loaded from the mailpoet context.
 *
 * Both values are persisted as trigger step args: `scheduled_at` (ISO 8601 string)
 * and `segment_ids` (array of segment IDs).
 */
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

  // Detect 12h vs 24h time format from WordPress site settings.
  // This regex pattern matches the PHP date format character 'a'/'A' (am/pm marker)
  // while ignoring escaped characters. Copied from scheduled-row.tsx.
  const settings = getSettings();
  const is12HourTime = /a(?!\\)/i.test(
    settings.formats.time
      .toLowerCase()
      .replace(/\\\\/g, '')
      .split('')
      .reverse()
      .join(''),
  );

  // Use WordPress site timezone for past-date check (not browser timezone).
  // dateI18n with 'U' format returns a Unix timestamp in the site's timezone.
  const siteNow = Number(dateI18n('U', new Date(), settings.timezone.string));
  const siteTodayMidnight = siteNow - (siteNow % 86400);

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
              isInvalidDate={(date) =>
                date.getTime() / 1000 < siteTodayMidnight
              }
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
