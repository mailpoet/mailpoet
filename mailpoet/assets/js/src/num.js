export const MailPoetNum = {
  toLocaleFixed: function (num, precisionOpts) { // eslint-disable-line func-names
    var precision = precisionOpts || 0;
    var factor = Math.pow(10, precision);
    return (Math.round(num * factor) / factor)
      .toLocaleString(
        undefined,
        { minimumFractionDigits: precision, maximumFractionDigits: precision }
      );
  },
};
