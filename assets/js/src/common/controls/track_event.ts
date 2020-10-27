import MailPoet from 'mailpoet';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export default async function ({ name, data, timeout = 0 }) {
  MailPoet.trackEvent(name, data);
  return sleep(timeout);
}
