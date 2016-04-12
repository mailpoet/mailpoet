define('handlebars_helpers', ['handlebars'], function(Handlebars) {
  // Handlebars helpers
  Handlebars.registerHelper('concat', function() {
      var size = (arguments.length - 1),
          output = '';
      for(var i = 0; i < size; i++) {
          output += arguments[i];
      };
      return output;
  });

  Handlebars.registerHelper('number_format', function(value, block) {
      return Number(value).toLocaleString();
  });
  Handlebars.registerHelper('date_format', function(timestamp, block) {
    if(window.moment) {
          if(timestamp === undefined || isNaN(timestamp) || timestamp <= 0) {
              return;
          }

          // set date format
          var f = block.hash.format || "MMM Do, YYYY";
          // check if we passed a timestamp
          if(parseInt(timestamp, 10) == timestamp) {
              return moment.unix(timestamp).format(f);
          } else {
              return moment.utc(timestamp).format(f);
          }
    } else {
      return timestamp;
    };
  });

  Handlebars.registerHelper('cycle', function(value, block) {
    var values = value.split(' ');
    return values[block.data.index % (values.length + 1)];
  });

  Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
      switch (operator) {
          case '==':
              return (v1 == v2) ? options.fn(this) : options.inverse(this);
          case '===':
              return (v1 === v2) ? options.fn(this) : options.inverse(this);
          case '!=':
              return (v1 != v2) ? options.fn(this) : options.inverse(this);
          case '!==':
              return (v1 !== v2) ? options.fn(this) : options.inverse(this);
          case '<':
              return (v1 < v2) ? options.fn(this) : options.inverse(this);
          case '<=':
              return (v1 <= v2) ? options.fn(this) : options.inverse(this);
          case '>':
              return (v1 > v2) ? options.fn(this) : options.inverse(this);
          case '>=':
              return (v1 >= v2) ? options.fn(this) : options.inverse(this);
          case '&&':
              return (v1 && v2) ? options.fn(this) : options.inverse(this);
          case '||':
              return (v1 || v2) ? options.fn(this) : options.inverse(this);
          case 'in':
              var values = v2.split(',');
              return (v2.indexOf(v1) !== -1) ? options.fn(this) : options.inverse(this);
          default:
              return options.inverse(this);
      }
  });

  Handlebars.registerHelper('nl2br', function(value, block) {
      return value.gsub("\n", "<br />");
  });

  Handlebars.registerHelper('json_encode', function(value, block) {
      return JSON.stringify(value);
  });

  Handlebars.registerHelper('json_decode', function(value, block) {
      return JSON.parse(value);
  });
  Handlebars.registerHelper('url', function(value, block) {
      var url = window.location.protocol + "//" + window.location.host + window.location.pathname;

      return url + value;
  });
  Handlebars.registerHelper('emailFromMailto', function(value) {
      var mailtoMatchingRegex = /^mailto\:/i;
      if (typeof value === 'string' && value.match(mailtoMatchingRegex)) {
          return value.replace(mailtoMatchingRegex, '');
      } else {
          return value;
      }
  });
  Handlebars.registerHelper('lookup', function(obj, field, options) {
      return obj && obj[field];
  });


  Handlebars.registerHelper('rsa_key', function(value, block) {
      // extract all lines into an array
      if(value === undefined) return '';

      var lines = value.trim().split("\n");

      // remove header & footer
      lines.shift();
      lines.pop();

      // return concatenated lines
      return lines.join('');
  });

  Handlebars.registerHelper('trim', function(value, block) {
      if(value === null || value === undefined) return '';
      return value.trim();
  });

  /**
   * {{ellipsis}}
   * From: https://github.com/assemble/handlebars-helpers
   * @author: Jon Schlinkert <http://github.com/jonschlinkert>
   * Truncate the input string and removes all HTML tags
   * @param  {String} str      The input string.
   * @param  {Number} limit    The number of characters to limit the string.
   * @param  {String} append   The string to append if charaters are omitted.
   * @return {String}          The truncated string.
   */
  Handlebars.registerHelper('ellipsis', function (str, limit, append) {
      if (append === undefined) {
          append = '';
      }
      var sanitized = str.replace(/(<([^>]+)>)/g, '');
      if (sanitized.length > limit) {
          return sanitized.substr(0, limit - append.length) + append;
      } else {
          return sanitized;
      }
  });

  Handlebars.registerHelper('getNumber', function (string) {
      return parseInt(string, 10);
  });

  Handlebars.registerHelper('fontWithFallback', function(font) {
    switch(font) {
      case 'Lucida': return new Handlebars.SafeString("'Lucida Sans Unicode', 'Lucida Grande', sans-serif");
      default: return font;
    }
  });

  window.Handlebars = Handlebars;
});
