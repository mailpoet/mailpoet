const MailPoetComUrlFactory = referralId => {
  const baseUrl = 'https://www.mailpoet.com/';

  const getUrl = (path, params) => {
    let finalParams = params;
    if (referralId) {
      finalParams.ref = referralId;
    }
    const url = new URL(path, baseUrl);
    Object.keys(finalParams).map(key => (url.searchParams.set(key, finalParams[key])));
    return url.toString();
  };

  return {
    getFreePlanUrl: (params) => {
      const paramsObject = typeof params === 'object' ? params : {};
      paramsObject.utm_source = 'plugin';
      return getUrl('free-plan', paramsObject);
    },

    getPricingPageUrl: subscribers => (
      getUrl('pricing', { subscribers })
    ),
  };
};

export default MailPoetComUrlFactory;
