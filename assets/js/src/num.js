define('num',
  [
    'mailpoet'
  ], function(
    MailPoet
) {
  'use strict';

  MailPoet.Num = {
    toLocaleFixed: function (num, precision) {
      precision = precision || 0;
      var factor = Math.pow(10, precision);
      return (Math.round(num * factor) / factor)
        .toLocaleString(
          undefined,
          {minimumFractionDigits: precision, maximumFractionDigits: precision}
        );
    }
  };

});
