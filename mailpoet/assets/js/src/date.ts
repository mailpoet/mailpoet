import Moment, { MomentInput } from 'moment';

export interface DateOptions {
  format?: string;
  offset?: number;
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
  adjustForTimezoneDifference: (date: Date) => Date;
} = {
  version: 0.1,
  options: {},
  defaults: {
    offset: 0,
    format: 'F j, Y H:i:s',
  },
  init: function init(opts?: DateOptions): typeof MailPoetDate {
    const options = opts || {};

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

    momentDate = Moment(date);
    if (options.offset === 0) momentDate = momentDate.utc();
    return momentDate.format(this.convertFormat(this.options.format));
  },
  toDate: function toDate(date: MomentInput, opts?: DateOptions): Date {
    const options = opts || {};
    this.init(options);

    return Moment(date).toDate();
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
    Moment(dateString).isAfter(currentTime, 's'),
  adjustForTimezoneDifference: function adjustForTimezoneDifference(
    date: Date,
  ): Date {
    // PHP offset is the same as timezone, e.g., UTC-2 is -120 (minutes)
    const serverOffsetMinutes = window.mailpoet_server_timezone_in_minutes || 0;
    // JS offset is the opposite, e.g., UTC-2 is +120 (minutes)
    const browserOffsetMinutes = new Date().getTimezoneOffset();
    // Because of this different representation, we can just sum these two
    // E.g., server UTC-2, browser UTC+2: -120 + -120 = -240 minuts difference
    // E.g., server UTC+1, browser UTC+2: +60 + -120 = -60 minutes difference
    const offsetDifference = serverOffsetMinutes + browserOffsetMinutes;
    if (!offsetDifference) {
      return date;
    }
    // Because the difference is calculated from browser to server, we need to subtract
    // the difference to adjust server time to browser's time zone
    date.setMinutes(date.getMinutes() - offsetDifference);
    return date;
  },
} as const;
