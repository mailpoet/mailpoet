import MailPoet from 'mailpoet';

const sleep = (ms: number) =>
  new Promise((resolve) => {
    setTimeout(resolve, ms);
  });

export default async function trackEvent({
  name,
  data,
  timeout = 0,
}: {
  name: string;
  data: object;
  timeout: number;
}) {
  MailPoet.trackEvent(name, data);
  return sleep(timeout);
}
