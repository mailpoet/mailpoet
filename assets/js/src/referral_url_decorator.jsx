const addReferralId = (url) => {
  if (!window.mailpoet_referral_id) {
    return url;
  }
  const refUrl = new URL(url);
  refUrl.searchParams.set('ref', window.mailpoet_referral_id);
  return refUrl.toString();
};

export default addReferralId;
