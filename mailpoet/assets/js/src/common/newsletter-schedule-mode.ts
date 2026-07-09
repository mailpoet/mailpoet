export const SCHEDULE_MODE_WEBSITE_TIME = 'website_time';
export const SCHEDULE_MODE_SUBSCRIBER_TIMEZONE = 'subscriber_timezone';

export type ScheduleMode =
  | typeof SCHEDULE_MODE_WEBSITE_TIME
  | typeof SCHEDULE_MODE_SUBSCRIBER_TIMEZONE;

export const DEFAULT_SCHEDULED_LOCAL_TIME = '08:00:00';
export const SUBSCRIBER_TIMEZONE_LEAD_TIME_HOURS = 24;

export function getScheduleMode(scheduleMode?: string | null): ScheduleMode {
  return scheduleMode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE
    ? SCHEDULE_MODE_SUBSCRIBER_TIMEZONE
    : SCHEDULE_MODE_WEBSITE_TIME;
}

export type ScheduleModeOptionChanges = {
  scheduleMode: ScheduleMode;
  scheduledLocalDate: string;
  scheduledLocalTime: string;
};

export function getScheduleModeOptionChanges(
  mode: ScheduleMode,
  current: { scheduledLocalDate?: string; scheduledLocalTime?: string },
  defaultLocalDate: string,
): ScheduleModeOptionChanges {
  if (mode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE) {
    return {
      scheduleMode: SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
      scheduledLocalDate: current.scheduledLocalDate || defaultLocalDate,
      scheduledLocalTime:
        current.scheduledLocalTime || DEFAULT_SCHEDULED_LOCAL_TIME,
    };
  }
  return {
    scheduleMode: SCHEDULE_MODE_WEBSITE_TIME,
    scheduledLocalDate: '',
    scheduledLocalTime: '',
  };
}

export function normalizeLocalTime(localTime: string): string {
  return localTime.length === 5 ? `${localTime}:00` : localTime;
}

export function isLocalDateTimeInFuture(
  localDate?: string | null,
  localTime?: string | null,
  now: Date = new Date(),
): boolean {
  if (!localDate || !localTime) {
    return false;
  }
  const parsed = new Date(`${localDate}T${normalizeLocalTime(localTime)}`);
  if (Number.isNaN(parsed.getTime())) {
    return false;
  }
  return parsed.getTime() > now.getTime();
}

function formatLocalDate(date: Date): string {
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${date.getFullYear()}-${month}-${day}`;
}

export function getTomorrowLocalDate(now: Date = new Date()): string {
  const tomorrow = new Date(now.getTime());
  tomorrow.setDate(tomorrow.getDate() + 1);
  return formatLocalDate(tomorrow);
}

export type LocalDateTime = {
  localDate: string;
  localTime: string;
};

export function snapLocalDateTimeToQuarterHour(
  value: string,
): LocalDateTime | null {
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return null;
  }
  const quarterHourMs = 15 * 60 * 1000;
  const snapped = new Date(
    Math.round(parsed.getTime() / quarterHourMs) * quarterHourMs,
  );
  const hours = String(snapped.getHours()).padStart(2, '0');
  const minutes = String(snapped.getMinutes()).padStart(2, '0');
  return {
    localDate: formatLocalDate(snapped),
    localTime: `${hours}:${minutes}:00`,
  };
}
