import { getSettings, setSettings } from '@wordpress/date';
import {
  formatLocalScheduledDate,
  formatScheduledDate,
} from '../../../assets/js/src/mailpoet-email-editor-integration/shared/format-scheduled-date';

describe('formatScheduledDate', () => {
  const originalTz = process.env.TZ;
  const defaultSettings = getSettings();

  before(() => {
    // Browser timezone differs from the site timezone below, so a bug that
    // converts the instant instead of parsing it in the site zone shows up
    // as a shifted hour.
    process.env.TZ = 'Asia/Tokyo';
    setSettings({
      ...defaultSettings,
      formats: {
        ...defaultSettings.formats,
        datetime: 'Y-m-d H:i',
      },
      timezone: {
        ...defaultSettings.timezone,
        string: 'America/New_York',
        offset: -5,
      },
    });
  });

  after(() => {
    // Assigning an undefined original would store the string "undefined",
    // which is not a valid zone and leaves later specs stuck on UTC.
    if (originalTz === undefined) {
      delete process.env.TZ;
    } else {
      process.env.TZ = originalTz;
    }
    setSettings(defaultSettings);
  });

  it('formats a scheduled date as the same wall clock during standard time (December)', () => {
    expect(formatScheduledDate('2026-12-10T09:30:00')).to.equal(
      '2026-12-10 09:30',
    );
  });

  it('formats a scheduled date as the same wall clock during daylight time (July)', () => {
    expect(formatScheduledDate('2026-07-10T09:30:00')).to.equal(
      '2026-07-10 09:30',
    );
  });

  it('keeps a subscriber-local time that falls in the site’s clock-change gap', () => {
    // 02:30 on 14 March 2027 doesn't exist in New York (clocks jump to 03:00),
    // but subscribers in other time zones still get the email at 02:30.
    expect(formatLocalScheduledDate('2027-03-14', '02:30:00')).to.equal(
      '2027-03-14 02:30',
    );
  });
});
