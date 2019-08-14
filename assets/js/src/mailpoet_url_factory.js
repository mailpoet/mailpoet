const addReferralId = (url, referralId) => {
  if (!referralId) {
    return url;
  }
  const refUrl = new URL(url);
  refUrl.searchParams.set('ref', referralId);
  return refUrl.toString();
};

const MailPoetUrlFactory = referralId => ({
  getFreePlanUrl: (utmCampaign = null, utmMedium = null) => {
    const url = new URL(addReferralId('https://www.mailpoet.com/free-plan/?utm_source=plugin', referralId));
    if (utmCampaign) {
      url.searchParams.set('utm_campaign', utmCampaign);
    }
    if (utmMedium) {
      url.searchParams.set('utm_medium', utmMedium);
    }
    return url.toString();
  },

  getPricingPageUrl: subscribersCount => (
    addReferralId(`https://www.mailpoet.com/pricing/?subscribers=${subscribersCount}`, referralId)
  ),
});

export default MailPoetUrlFactory;
