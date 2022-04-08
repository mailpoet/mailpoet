export const MailPoetNum = {
  // eslint-disable-next-line func-names
  toLocaleFixed: function (num, precisionOpts) {
    var precision = precisionOpts || 0;
    var factor = 10 ** precision;
    return (Math.round(num * factor) / factor).toLocaleString(undefined, {
      minimumFractionDigits: precision,
      maximumFractionDigits: precision,
    });
  },
};
