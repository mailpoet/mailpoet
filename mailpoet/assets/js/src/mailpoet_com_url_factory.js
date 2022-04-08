const MailPoetComUrlFactory = (referralId) => {
  const baseUrl = 'https://www.mailpoet.com/';
  const baseShopUrl = 'https://account.mailpoet.com/';

  const getUrl = (base, path, params) => {
    let finalParams = params;
    if (referralId) {
      finalParams.ref = referralId;
    }
    const url = new URL(path, base);
    Object.keys(finalParams).map((key) =>
      url.searchParams.set(key, finalParams[key]),
    );
    return url.toString();
  };

  return {
    getFreePlanUrl: (params) => {
      const paramsObject = typeof params === 'object' ? params : {};
      paramsObject.utm_source = 'plugin';
      return getUrl(baseUrl, 'free-plan', paramsObject);
    },

    getPricingPageUrl: (subscribers) =>
      getUrl(baseUrl, 'pricing', { subscribers }),

    getUpgradeUrl: (key) => getUrl(baseShopUrl, '/orders/upgrade/' + key, {}),

    getPurchasePlanUrl: (
      subscribersCount,
      subscriberEmail,
      planGroup,
      trackingObject,
    ) => {
      let paramsObject = { s: subscribersCount };
      if (typeof subscriberEmail === 'string') {
        paramsObject.email = subscriberEmail;
      }
      if (typeof planGroup === 'string') {
        paramsObject.g = planGroup;
      }
      if (typeof trackingObject === 'object') {
        paramsObject.utm_source = 'plugin';
        paramsObject = Object.assign(paramsObject, trackingObject);
      }
      return getUrl(baseShopUrl, '/', paramsObject);
    },
  };
};

export default MailPoetComUrlFactory;
