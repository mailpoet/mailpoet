import { FunctionComponent } from 'react';
import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { PremiumBannerWithUpgrade } from 'common/premium_banner_with_upgrade/premium_banner_with_upgrade';
import { Button } from 'common/button/button';
import ReactStringReplace from 'react-string-replace';

export function DynamicSegmentsPremiumBanner(): JSX.Element {
  const getBannerMessage: FunctionComponent = () => {
    const message = __(
      'Your current MailPoet plan does not support advanced segments with multiple conditions. Upgrade to the MailPoet Business plan to more precisely target your emails based on how people engage with your business. [link]Learn more.[/link]',
      'mailpoet',
    );
    return (
      <p>
        {ReactStringReplace(message, /\[link](.*?)\[\/link]/g, (match) => (
          <a
            key={match}
            href={MailPoet.premiumLink}
            target="_blank"
            rel="noopener noreferrer"
          >
            {match}
          </a>
        ))}
      </p>
    );
  };

  const getCtaButton: FunctionComponent = () => (
    <Button
      href={MailPoet.MailPoetComUrlFactory.getPurchasePlanUrl(
        MailPoet.subscribersCount,
        MailPoet.currentWpUserEmail,
        null,
        { utm_medium: 'segments', utm_campaign: 'signup' },
      )}
      target="_blank"
      rel="noopener noreferrer"
    >
      {__('Upgrade', 'mailpoet')}
    </Button>
  );

  return (
    <PremiumBannerWithUpgrade
      message={getBannerMessage({})}
      actionButton={getCtaButton({})}
    />
  );
}
