/* eslint no-restricted-globals: 0 */
import jQuery from 'jquery';

var $ = jQuery;
// Combination of jQuery.deparam and jQuery.serializeObject by Ben Alman.
/*!
     * jQuery BBQ: Back Button & Query Library - v1.2.1 - 2/17/2010
     * http://benalman.com/projects/jquery-bbq-plugin/
     *
     * Copyright (c) 2010 "Cowboy" Ben Alman
     * Dual licensed under the MIT and GPL licenses.
     * http://benalman.com/about/license/
     */
/*!
     * jQuery serializeObject - v0.2 - 1/20/2010
     * http://benalman.com/projects/jquery-misc-plugins/
     *
     * Copyright (c) 2010 "Cowboy" Ben Alman
     * Dual licensed under the MIT and GPL licenses.
     * http://benalman.com/about/license/
     */
$.fn.mailpoetSerializeObject = function (coerce) { // eslint-disable-line func-names
  var obj = {};
  var coerceTypes = { true: !0, false: !1, null: null };

  // Iterate over all name=value pairs.
  $.each(this.serializeArray(), function (j, v) { // eslint-disable-line func-names
    var key = v.name;
    var val = v.value;
    var cur = obj;
    var i = 0;

    // If key is more complex than 'foo', like 'a[]' or 'a[b][c]', split it
    // into its component parts.
    var keys = key.split('][');
    var keysLast = keys.length - 1;

    // If the first keys part contains [ and the last ends with ], then []
    // are correctly balanced.
    if (/\[/.test(keys[0]) && /\]$/.test(keys[keysLast])) {
      // Remove the trailing ] from the last keys part.
      keys[keysLast] = keys[keysLast].replace(/\]$/, '');

      // Split first keys part into two parts on the [ and add them back onto
      // the beginning of the keys array.
      keys = keys.shift().split('[').concat(keys);

      keysLast = keys.length - 1;
    } else {
      // Basic 'foo' style key.
      keysLast = 0;
    }

    // Coerce values.
    if (coerce) {
      if (val && !Number.isNaN(val)) { // number
        val = +val;
      } else if (val === 'undefined') { // undefined
        val = undefined;
      } else if (coerceTypes[val] !== undefined) { // true, false, null
        val = coerceTypes[val];
      }
    }

    if (keysLast) {
      // Complex key, build deep object structure based on a few rules:
      // * The 'cur' pointer starts at the object top-level.
      // * [] = array push (n is set to array length), [n] = array if n is
      //   numeric, otherwise object.
      // * If at the last keys part, set the value.
      // * For each keys part, if the current level is undefined create an
      //   object or array based on the type of the next keys part.
      // * Move the 'cur' pointer to the next level.
      // * Rinse & repeat.
      for (; i <= keysLast; i += 1) {
        key = keys[i] === '' ? cur.length : keys[i];
        cur[key] = i < keysLast
          ? cur[key] || (keys[i + 1] && isNaN(keys[i + 1]) ? {} : [])
          : val;
        cur = cur[key];
      }
    } else if (Array.isArray(obj[key])) {
      // val is already an array, so push on the next value.
      obj[key].push(val);
    } else if (obj[key] !== undefined) {
      // val isn't an array, but since a second value has been specified,
      // convert val into an array.
      obj[key] = [obj[key], val];
    } else {
      // val is a scalar.
      obj[key] = val;
    }
  });

  return obj;
};

export default $;
