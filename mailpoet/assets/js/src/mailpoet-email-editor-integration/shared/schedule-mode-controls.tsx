import { DatePicker, SelectControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { PremiumModal } from 'common/premium-modal';
import { LockedBadge } from 'common/premium-modal/locked-badge';
import {
  DEFAULT_SCHEDULED_LOCAL_TIME,
  SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
  SCHEDULE_MODE_WEBSITE_TIME,
  SUBSCRIBER_TIMEZONE_LEAD_TIME_HOURS,
  ScheduleMode,
  getLocalTimeOfDayItems,
} from 'common/newsletter-schedule-mode';
import { useScheduledDate } from './use-scheduled-date';

/**
 * Scheduling mode selector (website time vs subscriber's time zone) with the
 * local date & time inputs for the subscriber timezone mode. Shared between
 * the scheduled sidebar row and the review & send panel. Renders nothing when
 * the send_by_timezone feature flag is off.
 */
export function ScheduleModeControls(): JSX.Element | null {
  const {
    isTimezoneSchedulingAvailable,
    scheduleMode,
    setScheduleMode,
    scheduledLocalDate,
    scheduledLocalTime,
    setScheduledLocalDate,
    setScheduledLocalTime,
  } = useScheduledDate();
  const [showPremiumModal, setShowPremiumModal] = useState(false);

  if (!isTimezoneSchedulingAvailable) {
    return null;
  }

  const isRestricted =
    !MailPoet.capabilities.sendByTimezone ||
    MailPoet.capabilities.sendByTimezone.isRestricted;
  const isSubscriberTimezoneMode =
    scheduleMode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE;

  const handleModeChange = (mode: ScheduleMode) => {
    if (mode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE && isRestricted) {
      setShowPremiumModal(true);
      return;
    }
    setScheduleMode(mode);
  };

  // Used for comparing today with DatePicker dates to determine validity.
  const today = new Date().setHours(0, 0, 0, 0);

  return (
    <div className="mailpoet-schedule-mode-controls">
      <div
        className="mailpoet-schedule-mode-controls__selector"
        role="radiogroup"
        aria-label={__('Scheduling mode', 'mailpoet')}
      >
        <label className="mailpoet-schedule-mode-controls__option">
          <input
            type="radio"
            name="mailpoet-schedule-mode"
            value={SCHEDULE_MODE_WEBSITE_TIME}
            checked={!isSubscriberTimezoneMode}
            onChange={() => handleModeChange(SCHEDULE_MODE_WEBSITE_TIME)}
          />
          {__('Website time', 'mailpoet')}
        </label>
        <label className="mailpoet-schedule-mode-controls__option">
          <input
            type="radio"
            name="mailpoet-schedule-mode"
            value={SCHEDULE_MODE_SUBSCRIBER_TIMEZONE}
            checked={isSubscriberTimezoneMode}
            onChange={() => handleModeChange(SCHEDULE_MODE_SUBSCRIBER_TIMEZONE)}
            data-automation-id="email-schedule-mode-subscriber-timezone"
          />
          {__('Subscriber’s time zone', 'mailpoet')}
          {isRestricted && <LockedBadge text={__('Premium', 'mailpoet')} />}
        </label>
      </div>

      {isSubscriberTimezoneMode && (
        <div className="mailpoet-schedule-mode-controls__local-inputs">
          <DatePicker
            currentDate={scheduledLocalDate}
            onChange={(newDate) => setScheduledLocalDate(newDate.slice(0, 10))}
            isInvalidDate={(date) => date.getTime() < today}
          />
          <SelectControl
            __nextHasNoMarginBottom
            label={__('Time', 'mailpoet')}
            value={scheduledLocalTime || DEFAULT_SCHEDULED_LOCAL_TIME}
            options={Object.entries(getLocalTimeOfDayItems()).map(
              ([value, label]) => ({ value, label }),
            )}
            onChange={setScheduledLocalTime}
          />
          <p className="mailpoet-schedule-mode-controls__hint">
            {sprintf(
              // translators: %d is the minimum number of hours required before the first timezone batch can send.
              __(
                'Emails will arrive at the selected time in each subscriber’s time zone — or your website’s time zone if unknown. Schedule at least %d hours ahead.',
                'mailpoet',
              ),
              SUBSCRIBER_TIMEZONE_LEAD_TIME_HOURS,
            )}
          </p>
        </div>
      )}

      {showPremiumModal && (
        <PremiumModal
          onRequestClose={() => setShowPremiumModal(false)}
          data={{ capabilities: { sendByTimezone: true } }}
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'send_by_timezone',
          }}
        >
          {__(
            'Sending emails in each subscriber’s time zone is a premium feature.',
            'mailpoet',
          )}
        </PremiumModal>
      )}
    </div>
  );
}
