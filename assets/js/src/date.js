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
      this.init(options);
      return Moment.utc(date)
        .local()
        .format(this.convertFormat(this.options.format));
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
      const format_mappings = {
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

      const replacements = format_mappings['date'];

      let outputFormat = '';

      Object.keys(replacements).forEach(function (key) {
        if (format.contains(key)) {
          format = format.replace(key, '%'+key);
        }
      });
      outputFormat = format;
      Object.keys(replacements).forEach(function(key) {
        if (outputFormat.contains('%'+key)) {
          outputFormat = outputFormat.replace('%'+key, replacements[key]);
        }
      });
      return outputFormat;
    }
  };
});