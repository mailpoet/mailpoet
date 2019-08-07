const addReferralId = (url) => {
  if (!window.mailpoet_referral_id) {
    return url;
  }
  const parser = document.createElement('a');
  parser.href = url;
  parser.search += parser.search ? '&' : '?';
  parser.search += `ref=${encodeURIComponent(window.mailpoet_referral_id)}`;
  return parser.toString();

  // const refUrl = new URL(url);
  // refUrl.searchParams.set('ref', window.mailpoet_referral_id);
  // return refUrl.toString();
};

export default addReferralId;
