import { MailPoet } from 'mailpoet';

const sleep = (ms: number) =>
  new Promise((resolve) => {
    setTimeout(resolve, ms);
  });

export async function trackEvent(actionData) {
  const name: string = actionData.name;
  const data: object = actionData.data;
  const timeout: number = actionData.timeout ?? 0;

  MailPoet.trackEvent(name, data);
  return sleep(timeout);
}
