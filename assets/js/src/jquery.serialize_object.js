define(
  [
    'jquery'
  ],
  function (
    jQuery
  ) {
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
    $.fn.serializeObject = function (coerce) {
      var obj = {},
        coerce_types = { true: !0, false: !1, null: null };

      // Iterate over all name=value pairs.
      $.each( this.serializeArray(), function (j, v){
        var key = v.name,
          val = v.value,
          cur = obj,
          i = 0,

          // If key is more complex than 'foo', like 'a[]' or 'a[b][c]', split it
          // into its component parts.
          keys = key.split( '][' ),
          keys_last = keys.length - 1;

        // If the first keys part contains [ and the last ends with ], then []
        // are correctly balanced.
        if ( /\[/.test( keys[0] ) && /\]$/.test( keys[ keys_last ] ) ) {
          // Remove the trailing ] from the last keys part.
          keys[ keys_last ] = keys[ keys_last ].replace( /\]$/, '' );

          // Split first keys part into two parts on the [ and add them back onto
          // the beginning of the keys array.
          keys = keys.shift().split('[').concat( keys );

          keys_last = keys.length - 1;
        } else {
          // Basic 'foo' style key.
          keys_last = 0;
        }

        // Coerce values.
        if ( coerce ) {
          val = val && !isNaN(val) ? +val              // number
            : val === 'undefined' ? undefined         // undefined
            : coerce_types[val] !== undefined ? coerce_types[val] // true, false, null
            : val;                                                // string
        }

        if ( keys_last ) {
          // Complex key, build deep object structure based on a few rules:
          // * The 'cur' pointer starts at the object top-level.
          // * [] = array push (n is set to array length), [n] = array if n is
          //   numeric, otherwise object.
          // * If at the last keys part, set the value.
          // * For each keys part, if the current level is undefined create an
          //   object or array based on the type of the next keys part.
          // * Move the 'cur' pointer to the next level.
          // * Rinse & repeat.
          for ( ; i <= keys_last; i++ ) {
            key = keys[i] === '' ? cur.length : keys[i];
            cur[key] = i < keys_last
              ? cur[key] || ( keys[i+1] && isNaN( keys[i+1] ) ? {} : [] )
              : val;
            cur = cur[key];
          }

        } else {
          // Simple key, even simpler rules, since only scalars and shallow
          // arrays are allowed.

          if ( $.isArray( obj[key] ) ) {
            // val is already an array, so push on the next value.
            obj[key].push( val );

          } else if ( obj[key] !== undefined ) {
            // val isn't an array, but since a second value has been specified,
            // convert val into an array.
            obj[key] = [ obj[key], val ];

          } else {
            // val is a scalar.
            obj[key] = val;
          }
        }
      });

      return obj;
    };

    return $;
  }
);