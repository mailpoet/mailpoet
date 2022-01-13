const notFreeAddress = ({ name, address }) => ({
  sender: { name, address },
  reply_to: { name, address },
  'signup_confirmation.from.address': address,
  'signup_confirmation.from.name': name,
  'signup_confirmation.reply_to.address': address,
  'signup_confirmation.reply_to.name': name,
});

const freeAddress = ({ name, address }) => {
  const userHostDomain = window.location.hostname.replace('www.', '');
  const replacementEmailAddress = `wordpress@${userHostDomain}`;

  return {
    sender: { name, address: replacementEmailAddress },
    reply_to: { name, address },
    'signup_confirmation.from.address': replacementEmailAddress,
    'signup_confirmation.from.name': name,
    'signup_confirmation.reply_to.address': address,
    'signup_confirmation.reply_to.name': name,
  };
};

export default ({ name, address }) => {
  const emailAddressDomain = address.split('@').pop().toLowerCase();
  if (window.mailpoet_free_domains.indexOf(emailAddressDomain) > -1) {
    return freeAddress({ name, address });
  }
  return notFreeAddress({ name, address });
};
