const MailPoetComUrlFactory = referralId => {
  const baseUrl = 'https://www.mailpoet.com/';
  const baseShopUrl = 'https://account.mailpoet.com/';

  const getUrl = (base, path, params) => {
    let finalParams = params;
    if (referralId) {
      finalParams.ref = referralId;
    }
    const url = new URL(path, base);
    Object.keys(finalParams).map(key => (url.searchParams.set(key, finalParams[key])));
    return url.toString();
  };

  return {
    getFreePlanUrl: (params) => {
      const paramsObject = typeof params === 'object' ? params : {};
      paramsObject.utm_source = 'plugin';
      return getUrl(baseUrl, 'free-plan', paramsObject);
    },

    getPricingPageUrl: subscribers => (
      getUrl(baseUrl, 'pricing', { subscribers })
    ),

    getUpgradeUrl: () => (
      getUrl(baseShopUrl, '/upgrade', {})
    ),

    getPurchasePlanUrl: (subscribersCount) => (
      getUrl(baseShopUrl, '/', { s: subscribersCount })
    ),
  };
};

export default MailPoetComUrlFactory;
