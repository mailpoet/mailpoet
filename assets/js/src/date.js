define('date',
  [
    'mailpoet',
    'jquery',
    'moment'
  ], function (
    mp,
    jQuery,
    Moment
) {
    'use strict';

    var MailPoet = mp;

    MailPoet.Date = {
      version: 0.1,
      options: {},
      defaults: {
        offset: 0,
        format: 'F, d Y H:i:s'
      },
      init: function (opts) {
        var options = opts || {};

      // set UTC offset
        if (
        options.offset === undefined
        && window.mailpoet_date_offset !== undefined
      ) {
          options.offset = window.mailpoet_date_offset;
        }
      // set date format
        if (
        options.format === undefined
        && window.mailpoet_date_format !== undefined
      ) {
          options.format = window.mailpoet_date_format;
        }
      // merge options
        this.options = jQuery.extend({}, this.defaults, options);

        return this;
      },
      format: function (date, opts) {
        var options = opts || {};
        var momentDate;
        this.init(options);

        momentDate = Moment(date, this.convertFormat(options.parseFormat));
        if (options.offset === 0) momentDate = momentDate.utc();
        return momentDate.format(this.convertFormat(this.options.format));
      },
      toDate: function (date, opts) {
        var options = opts || {};
        this.init(options);

        return Moment(date, this.convertFormat(options.parseFormat)).toDate();
      },
      short: function (date) {
        return this.format(date, {
          format: 'F, j Y'
        });
      },
      full: function (date) {
        return this.format(date, {
          format: 'F, j Y H:i:s'
        });
      },
      time: function (date) {
        return this.format(date, {
          format: 'H:i:s'
        });
      },
      convertFormat: function (format) {
        var replacements;
        var convertedFormat;
        var escapeToken;
        var index;
        var token;
        var format_mappings = {
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
            U: 'X'
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
            Z: 'z'
          }
        };

        if (!format || format.length <= 0) return format;

        replacements = format_mappings['date'];
        convertedFormat = [];
        escapeToken = false;

        for (index = 0, token = ''; format.charAt(index); index += 1) {
          token = format.charAt(index);
          if (escapeToken === true) {
            convertedFormat.push('[' + token + ']');
            escapeToken = false;
          } else {
            if (token === '\\') {
            // Slash escapes the next symbol to be treated as literal
              escapeToken = true;
              continue;
            } else if (replacements[token] !== undefined) {
              convertedFormat.push(replacements[token]);
            } else {
              convertedFormat.push('[' + token + ']');
            }
          }
        }

        return convertedFormat.join('');
      }
    };
  });
