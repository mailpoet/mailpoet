import Moment, { MomentInput } from 'moment';

export interface DateOptions {
  format?: string;
  offset?: number;
}

const MILLISECONDS_IN_HOUR = 3600000;
const siteTimeFormatters: Record<string, Intl.DateTimeFormat | null> = {};

// Null when the site uses a manual UTC offset, or the browser does not know its time zone.
function getSiteTimeFormatter(): Intl.DateTimeFormat | null {
  const timeZone = window.mailpoet_date_timezone;
  if (!timeZone) {
    return null;
  }
  if (siteTimeFormatters[timeZone] === undefined) {
    try {
      siteTimeFormatters[timeZone] = new Intl.DateTimeFormat('en-US', {
        timeZone,
        hourCycle: 'h23',
        year: 'numeric',
        month: 'numeric',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        second: 'numeric',
      });
    } catch {
      siteTimeFormatters[timeZone] = null;
    }
  }
  return siteTimeFormatters[timeZone];
}

// Hours between site time and UTC at the given moment. WordPress's gmt_offset
// is this value for today only, so it is wrong for a date across a clock change.
function getSiteOffset(timestamp: number): number {
  const formatter = getSiteTimeFormatter();
  if (!formatter || Number.isNaN(timestamp)) {
    return parseFloat(window.mailpoet_date_offset || '0');
  }
  const siteTime: Record<string, number> = {};
  formatter.formatToParts(timestamp).forEach(({ type, value }) => {
    siteTime[type] = Number(value);
  });
  const siteTimestamp = Date.UTC(
    siteTime.year,
    siteTime.month - 1,
    siteTime.day,
    siteTime.hour,
    siteTime.minute,
    siteTime.second,
  );
  return (
    (siteTimestamp - Math.floor(timestamp / 1000) * 1000) / MILLISECONDS_IN_HOUR
  );
}

// The offset that applies to a site time, given as a timestamp of its clock values.
function getOffsetOfSiteTime(siteTimestamp: number): number {
  const day = 24 * MILLISECONDS_IN_HOUR;
  const before = getSiteOffset(siteTimestamp - day);
  const after = getSiteOffset(siteTimestamp + day);
  const applies = (offset: number) =>
    getSiteOffset(siteTimestamp - offset * MILLISECONDS_IN_HOUR) === offset;
  // A time the clocks skip moves to the hour after, and a time that comes
  // twice means the first one. Both take the offset from before the change.
  return !applies(before) && applies(after) ? after : before;
}

export const MailPoetDate: {
  version: number;
  options: object;
  defaults: {
    offset: number;
    format: string;
  };
  init: (opts?: DateOptions) => typeof MailPoetDate;
  format: (date: MomentInput, opts?: DateOptions) => string;
  toDate: (date: MomentInput, opts?: DateOptions) => Date;
  short: (date: MomentInput) => string;
  full: (date: MomentInput) => string;
  time: (date: MomentInput) => string;
  convertFormat: (format: string) => string;
  isInFuture: (dateString: string, currentTime: MomentInput) => boolean;
  isInPast: (dateString: string, currentTime: MomentInput) => boolean;
  datetimeString: (date: MomentInput) => string;
  toGmtDatetimeString: (date: MomentInput) => string;
} = {
  version: 0.1,
  options: {},
  defaults: {
    offset: 0,
    format: 'F j, Y H:i:s',
  },
  init: function init(opts?: DateOptions): typeof MailPoetDate {
    const options = { ...opts };

    // set UTC offset
    if (
      options.offset === undefined &&
      window.mailpoet_date_offset !== undefined
    ) {
      options.offset =
        typeof window.mailpoet_date_offset === 'string'
          ? parseFloat(window.mailpoet_date_offset)
          : window.mailpoet_date_offset;
    }
    // set dateTime format
    if (
      options.format === undefined &&
      window.mailpoet_datetime_format !== undefined
    ) {
      options.format = window.mailpoet_datetime_format;
    }
    // merge options
    this.options = {
      ...this.defaults,
      ...options,
    };

    return this;
  },
  format: function format(date: MomentInput, opts?: DateOptions): string {
    const options = opts || {};
    let momentDate;
    this.init(options);

    momentDate = Moment.utc(date);
    const offset = options.offset ?? getSiteOffset(Moment.utc(date).valueOf());
    if (offset) {
      momentDate = momentDate.utcOffset(offset);
    }
    return momentDate.format(this.convertFormat(this.options.format));
  },
  toDate: function toDate(date: MomentInput): Date {
    return Moment.utc(date).toDate();
  },
  short: function short(date: MomentInput): string {
    return this.format(date, {
      format: window.mailpoet_date_format || 'F j, Y',
    });
  },
  full: function full(date: MomentInput): string {
    return this.format(date, {
      format: window.mailpoet_datetime_format || 'F j, Y H:i:s',
    });
  },
  time: function time(date: MomentInput): string {
    return this.format(date, {
      format: window.mailpoet_time_format || 'H:i:s',
    });
  },
  datetimeString: function gmtToSiteTimestamp(date: MomentInput): string {
    return this.format(date, { format: 'Y-m-d H:i:s' });
  },
  toGmtDatetimeString: function siteToGmtTimestamp(date: MomentInput): string {
    const siteTimestamp = Moment.utc(date).valueOf();
    const offset = getOffsetOfSiteTime(siteTimestamp);
    return this.format(siteTimestamp - offset * MILLISECONDS_IN_HOUR, {
      format: 'Y-m-d H:i:s',
      offset: 0,
    });
  },
  convertFormat: function convertFormat(format: string): string {
    let escapeToken;
    let index: number;
    let token: string;
    const formatMappings = {
      date: {
        d: 'DD',
        D: 'ddd',
        j: 'D',
        l: 'dddd',
        N: 'E',
        S: 'o',
        w: 'e',
        z: 'DDD',
        W: 'W',
        F: 'MMMM',
        m: 'MM',
        M: 'MMM',
        n: 'M',
        t: '', // no equivalent
        L: '', // no equivalent
        o: 'YYYY',
        Y: 'YYYY',
        y: 'YY',
        a: 'a',
        A: 'A',
        B: '', // no equivalent
        g: 'h',
        G: 'H',
        h: 'hh',
        H: 'HH',
        i: 'mm',
        s: 'ss',
        u: 'SSS',
        e: 'zz', // deprecated since version 1.6.0 of moment.js
        I: '', // no equivalent
        O: '', // no equivalent
        P: '', // no equivalent
        T: '', // no equivalent
        Z: '', // no equivalent
        c: '', // no equivalent
        r: '', // no equivalent
        U: 'X',
      },
      strftime: {
        a: 'ddd',
        A: 'dddd',
        b: 'MMM',
        B: 'MMMM',
        d: 'DD',
        e: 'D',
        F: 'YYYY-MM-DD',
        H: 'HH',
        I: 'hh',
        j: 'DDDD',
        k: 'H',
        l: 'h',
        m: 'MM',
        M: 'mm',
        p: 'A',
        S: 'ss',
        u: 'E',
        w: 'd',
        W: 'WW',
        y: 'YY',
        Y: 'YYYY',
        z: 'ZZ',
        Z: 'z',
      },
    };

    if (!format || format.length <= 0) return format;

    const replacements = formatMappings.date;
    const convertedFormat = [];
    escapeToken = false;

    for (index = 0, token = ''; format.charAt(index); index += 1) {
      token = format.charAt(index);
      if (escapeToken === true) {
        convertedFormat.push(`[${token}]`);
        escapeToken = false;
      } else if (token === '\\') {
        // Slash escapes the next symbol to be treated as literal
        escapeToken = true;
      } else if (replacements[token] !== undefined) {
        convertedFormat.push(replacements[token]);
      } else {
        convertedFormat.push(`[${token}]`);
      }
    }

    return convertedFormat.join('');
  },
  isInFuture: (dateString: string, currentTime: MomentInput): boolean =>
    Moment.utc(dateString).isAfter(currentTime, 's'),
  isInPast: (dateString: string, currentTime: MomentInput): boolean =>
    Moment.utc(dateString).isBefore(currentTime, 's'),
} as const;
