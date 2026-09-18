import { getSettings, setSettings } from '@wordpress/date';
import { formatScheduledDate } from '../../../assets/js/src/mailpoet-email-editor-integration/shared/format-scheduled-date';

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
    process.env.TZ = originalTz;
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
});
