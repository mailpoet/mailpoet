import React from 'react';
import MailPoet from 'mailpoet';
import PremiumBannerWithUpgrade from 'common/premium_banner_with_upgrade/premium_banner_with_upgrade';
import Button from 'common/button/button';
import ReactStringReplace from 'react-string-replace';

const DynamicSegmentsPremiumBanner: React.FunctionComponent = () => {
  const getBannerMessage: React.FunctionComponent = () => {
    const message = MailPoet.I18n.t('premiumFeatureMultipleConditions');
    return (
      <p>
        {ReactStringReplace(
          message,
          /\[link](.*?)\[\/link]/g,
          (match) => (
            <a key={match} href={MailPoet.premiumLink} target="_blank" rel="noopener noreferrer">{match}</a>
          )
        )}
      </p>
    );
  };

  const getCtaButton: React.FunctionComponent = () => (
    <Button
      href={MailPoet.MailPoetComUrlFactory.getFreePlanUrl({
        utm_medium: 'segments',
        utm_campaign: 'signup',
      })}
      target="_blank"
      rel="noopener noreferrer"
    >
      {MailPoet.I18n.t('premiumBannerCtaFree')}
    </Button>
  );

  return (
    <PremiumBannerWithUpgrade
      message={getBannerMessage({})}
      actionButton={getCtaButton({})}
    />
  );
};

export default DynamicSegmentsPremiumBanner;
