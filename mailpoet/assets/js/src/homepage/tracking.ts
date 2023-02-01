import { MailPoet } from 'mailpoet';

export function trackCtaAndRedirect(event: string, cta: string, link: string) {
  MailPoet.trackEvent(
    event,
    {
      ctaLabel: cta,
    },
    { send_immediately: true },
    () => {
      window.location.href = link;
    },
  );
}
