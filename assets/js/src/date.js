define('date',
  [
    'mailpoet',
    'jquery',
    'moment'
  ], function(
    MailPoet,
    jQuery,
    Moment
) {
  'use strict';

  MailPoet.Date = {
    version: 0.1,
    options: {},
    defaults: {
      offset: 0,
      format: 'F, d Y H:i:s'
    },
    init: function(options) {
      options = options || {};

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
    format: function(date, options) {
      options = options || {};
      this.init(options);

      return Moment(date, this.convertFormat(options.parseFormat))
        .format(this.convertFormat(this.options.format));
    },
    toDate: function(date, options) {
      options = options || {};
      this.init(options);

      return Moment(date, this.convertFormat(options.parseFormat)).toDate();
    },
    short: function(date) {
      return this.format(date, {
        format: 'F, j Y'
      });
    },
    full: function(date) {
      return this.format(date, {
        format: 'F, j Y H:i:s'
      });
    },
    time: function(date) {
      return this.format(date, {
        format: 'H:i:s'
      });
    },
    convertFormat: function(format) {
      var format_mappings = {
        date: {
          D: 'ddd',
          l: 'dddd',
          d: 'DD',
          j: 'D',
          z: 'DDDD',
          N: 'E',
          S: '',
          M: 'MMM',
          F: 'MMMM',
          m: 'MM',
          n: '',
          t: '',
          y: 'YY',
          Y: 'YYYY',
          H: 'HH',
          h: 'hh',
          g: 'h',
          A: 'A',
          i: 'mm',
          s: 'ss',
          T: 'z',
          O: 'ZZ',
          w: 'd',
          W: 'WW'
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

      var replacements = format_mappings['date'];

      var outputFormat = '';

      Object.keys(replacements).forEach(function(key) {
        if (format.indexOf(key) !== -1) {
          format = format.replace(key, '%'+key);
        }
      });
      outputFormat = format;
      Object.keys(replacements).forEach(function(key) {
        if (outputFormat.indexOf('%'+key) !== -1) {
          outputFormat = outputFormat.replace('%'+key, replacements[key]);
        }
      });
      return outputFormat;
    }
  };
});
