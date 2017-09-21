define('num',
  [
    'mailpoet'
  ], function (
    mp
) {
    'use strict';

    var MailPoet = mp;
    MailPoet.Num = {
      toLocaleFixed: function (num, precisionOpts) {
        var precision = precisionOpts || 0;
        var factor = Math.pow(10, precision);
        return (Math.round(num * factor) / factor)
        .toLocaleString(
          undefined,
          {minimumFractionDigits: precision, maximumFractionDigits: precision}
        );
      }
    };

  });
