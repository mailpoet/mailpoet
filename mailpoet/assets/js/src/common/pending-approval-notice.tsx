import { MouseEvent } from 'react';
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

function PendingApprovalTitle(): JSX.Element {
  return createInterpolateElement(
    __('MailPoet is <link>reviewing your subscription</link>.', 'mailpoet'),
    {
      link: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          href="https://kb.mailpoet.com/article/379-our-approval-process"
          target="_blank"
          rel="noreferrer"
        />
      ),
    },
  );
}

function PendingApprovalBody(): JSX.Element {
  return createInterpolateElement(
    __(
      `You can use all MailPoet features and send <link1>email previews</link1> to your <link2>authorized email addresses</link2>, but sending to your email list contacts is temporarily paused until we review your subscription. If you don't hear from us within 48 hours, please check the inbox and spam folders of your MailPoet account email for follow-up emails with the subject "<emailSubject/>" and reply, or <link3>contact us</link3>.`,
      'mailpoet',
    ),
    {
      link1: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          href="https://kb.mailpoet.com/article/290-check-your-newsletter-before-sending-it"
          target="_blank"
          rel="noreferrer"
        />
      ),
      link2: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          href="https://kb.mailpoet.com/article/266-how-to-add-an-authorized-email-address-as-the-from-address#how-to-authorize-an-email-address"
          target="_blank"
          rel="noreferrer"
        />
      ),
      link3: (
        // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
        <a
          href="https://www.mailpoet.com/support/"
          target="_blank"
          rel="noreferrer"
        />
      ),
      emailSubject: <>Your MailPoet Subscription Review</>,
    },
  );
}

function PendingApprovalMessage(): JSX.Element {
  return (
    <>
      <PendingApprovalTitle /> <PendingApprovalBody />
    </>
  );
}

interface ClickToRefreshProps {
  onRefreshClick: (e: MouseEvent<HTMLAnchorElement>) => Promise<void>;
}

function ClickToRefresh({ onRefreshClick }: ClickToRefreshProps): JSX.Element {
  return createInterpolateElement(
    __(
      `If you have already received approval email, click <link>here</link> to update the status.`,
      'mailpoet',
    ),
    {
      // eslint-disable-next-line jsx-a11y/anchor-has-content, jsx-a11y/control-has-associated-label
      link: <a onClick={onRefreshClick} href="#" />,
    },
  );
}

export {
  PendingApprovalTitle,
  PendingApprovalBody,
  PendingApprovalMessage,
  ClickToRefresh,
};
