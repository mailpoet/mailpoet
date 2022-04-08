import Handlebars from 'handlebars';

// Handlebars helpers
Handlebars.registerHelper('concat', function concatHelper() {
  var size = arguments.length - 1;
  var output = '';
  var i;
  for (i = 0; i < size; i += 1) {
    output += arguments[i];
  }
  return output;
});

Handlebars.registerHelper('number_format', function numberFormatHelper(value) {
  return Number(value).toLocaleString();
});
Handlebars.registerHelper(
  'date_format',
  function dateFormatHelper(timestamp, block) {
    var f;
    if (window.moment) {
      if (
        timestamp === undefined ||
        Number.isNaN(timestamp) ||
        timestamp <= 0
      ) {
        return undefined;
      }

      // set date format
      f = block.hash.format || 'MMM Do, YYYY';
      // check if we passed a timestamp
      if (/^\s*\d+\s*$/.test(timestamp)) {
        return window.moment.unix(timestamp).format(f);
      }
      return window.moment.utc(timestamp).format(f);
    }
    return timestamp;
  },
);

Handlebars.registerHelper('cycle', function cycleHelper(value, block) {
  var values = value.split(' ');
  return values[block.data.index % (values.length + 1)];
});

Handlebars.registerHelper(
  'ifCond',
  function ifCondHelper(v1, operator, v2, options) {
    switch (operator) {
      case '==':
        return v1 == v2 ? options.fn(this) : options.inverse(this); // eslint-disable-line eqeqeq
      case '===':
        return v1 === v2 ? options.fn(this) : options.inverse(this);
      case '!=':
        return v1 != v2 ? options.fn(this) : options.inverse(this); // eslint-disable-line eqeqeq
      case '!==':
        return v1 !== v2 ? options.fn(this) : options.inverse(this);
      case '<':
        return v1 < v2 ? options.fn(this) : options.inverse(this);
      case '<=':
        return v1 <= v2 ? options.fn(this) : options.inverse(this);
      case '>':
        return v1 > v2 ? options.fn(this) : options.inverse(this);
      case '>=':
        return v1 >= v2 ? options.fn(this) : options.inverse(this);
      case '&&':
        return v1 && v2 ? options.fn(this) : options.inverse(this);
      case '||':
        return v1 || v2 ? options.fn(this) : options.inverse(this);
      case 'in':
        return v2.indexOf(v1) !== -1 ? options.fn(this) : options.inverse(this);
      default:
        return options.inverse(this);
    }
  },
);

Handlebars.registerHelper('nl2br', function nl2brHelper(value) {
  return value.gsub('\n', '<br />');
});

Handlebars.registerHelper('json_encode', function jsonEncodeHelper(value) {
  return JSON.stringify(value);
});

Handlebars.registerHelper('json_decode', function jsonDecodeHelper(value) {
  return JSON.parse(value);
});
Handlebars.registerHelper('url', function urlHelper(value) {
  var url =
    window.location.protocol +
    '//' +
    window.location.host +
    window.location.pathname;

  return url + value;
});
Handlebars.registerHelper(
  'emailFromMailto',
  function emailFromMailtoHelper(value) {
    var mailtoMatchingRegex = /^mailto:/i;
    if (typeof value === 'string' && value.match(mailtoMatchingRegex)) {
      return value.replace(mailtoMatchingRegex, '');
    }
    return value;
  },
);
Handlebars.registerHelper('lookup', function lookupHelper(obj, field) {
  return obj && obj[field];
});

Handlebars.registerHelper('rsa_key', function rsaKeyHelper(value) {
  var lines;
  // extract all lines into an array
  if (value === undefined) return '';

  lines = value.trim().split('\n');

  // remove header & footer
  lines.shift();
  lines.pop();

  // return concatenated lines
  return lines.join('');
});

Handlebars.registerHelper('trim', function trimHelper(value) {
  if (value === null || value === undefined) return '';
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
Handlebars.registerHelper(
  'ellipsis',
  function ellipsisHelper(str, limit, append) {
    var strAppend = append;
    var sanitized = str.replace(/(<([^>]+)>)/g, '');
    if (strAppend === undefined) {
      strAppend = '';
    }
    if (sanitized.length > limit) {
      return sanitized.substr(0, limit - strAppend.length) + strAppend;
    }
    return sanitized;
  },
);

Handlebars.registerHelper('getNumber', function getNumberHelper(string) {
  return parseInt(string, 10);
});

Handlebars.registerHelper(
  'fontWithFallback',
  function fontWithFallbackHelper(font) {
    switch (font) {
      case 'Arial':
        return new Handlebars.SafeString(
          "Arial, 'Helvetica Neue', Helvetica, sans-serif",
        );
      case 'Comic Sans MS':
        return new Handlebars.SafeString(
          "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
        );
      case 'Courier New':
        return new Handlebars.SafeString(
          "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
        );
      case 'Georgia':
        return new Handlebars.SafeString(
          "Georgia, Times, 'Times New Roman', serif",
        );
      case 'Lucida':
        return new Handlebars.SafeString(
          "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
        );
      case 'Tahoma':
        return new Handlebars.SafeString('Tahoma, Verdana, Segoe, sans-serif');
      case 'Times New Roman':
        return new Handlebars.SafeString(
          "'Times New Roman', Times, Baskerville, Georgia, serif",
        );
      case 'Trebuchet MS':
        return new Handlebars.SafeString(
          "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
        );
      case 'Verdana':
        return new Handlebars.SafeString('Verdana, Geneva, sans-serif');
      case 'Arvo':
        return new Handlebars.SafeString('arvo, courier, georgia, serif');
      case 'Lato':
        return new Handlebars.SafeString(
          "lato, 'helvetica neue', helvetica, arial, sans-serif",
        );
      case 'Lora':
        return new Handlebars.SafeString(
          "lora, georgia, 'times new roman', serif",
        );
      case 'Merriweather':
        return new Handlebars.SafeString(
          "merriweather, georgia, 'times new roman', serif",
        );
      case 'Merriweather Sans':
        return new Handlebars.SafeString(
          "'merriweather sans', 'helvetica neue', helvetica, arial, sans-serif",
        );
      case 'Noticia Text':
        return new Handlebars.SafeString(
          "'noticia text', georgia, 'times new roman', serif",
        );
      case 'Open Sans':
        return new Handlebars.SafeString(
          "'open sans', 'helvetica neue', helvetica, arial, sans-serif",
        );
      case 'Playfair Display':
        return new Handlebars.SafeString(
          "playfair display, georgia, 'times new roman', serif",
        );
      case 'Roboto':
        return new Handlebars.SafeString(
          "roboto, 'helvetica neue', helvetica, arial, sans-serif",
        );
      case 'Source Sans Pro':
        return new Handlebars.SafeString(
          "'source sans pro', 'helvetica neue', helvetica, arial, sans-serif",
        );
      case 'Oswald':
        return new Handlebars.SafeString(
          "Oswald, 'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
        );
      case 'Raleway':
        return new Handlebars.SafeString(
          "Raleway, 'Century Gothic', CenturyGothic, AppleGothic, sans-serif",
        );
      case 'Permanent Marker':
        return new Handlebars.SafeString(
          "'Permanent Marker', Tahoma, Verdana, Segoe, sans-serif",
        );
      case 'Pacifico':
        return new Handlebars.SafeString(
          "Pacifico, 'Arial Narrow', Arial, sans-serif",
        );
      default:
        return font;
    }
  },
);

window.Handlebars = Handlebars;
