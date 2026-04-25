import { __, sprintf } from '@wordpress/i18n';
import { Flex, FlexBlock, FlexItem } from '@wordpress/components';
import { useLocation, useNavigate } from 'react-router-dom';
import { MailPoet } from 'mailpoet';
import { BackButton } from 'common/page-header';
import { LocationState } from 'subscribers/location-state';

export type PropTypes = {
  email: string;
  avatarUrl: string | null;
  subscribedAt: string | null;
  sourceLabel: string | null;
};

function buildSubtitle(
  subscribedAt: string | null,
  sourceLabel: string | null,
): string | null {
  if (!subscribedAt) {
    return null;
  }
  const formattedDate = MailPoet.Date.short(subscribedAt);
  if (!sourceLabel) {
    return sprintf(
      // translators: %s is a date, e.g. "21 January, 2021"
      __('Subscribed on %s', 'mailpoet'),
      formattedDate,
    );
  }
  return sprintf(
    // translators: 1: date, e.g. "21 January, 2021"; 2: source label, e.g. "WooCommerce checkout"
    __('Subscribed on %1$s / via %2$s', 'mailpoet'),
    formattedDate,
    sourceLabel,
  );
}

export function StatsHeading({
  email,
  avatarUrl,
  subscribedAt,
  sourceLabel,
}: PropTypes): JSX.Element {
  const location = useLocation();
  const navigate = useNavigate();
  const backUrl = (location.state as LocationState)?.backUrl || '/';
  const subtitle = buildSubtitle(subscribedAt, sourceLabel);

  return (
    <Flex className="mailpoet-subscriber-stats-heading" align="center" gap={4}>
      <FlexItem>
        <BackButton
          onClick={() => navigate(backUrl)}
          label={MailPoet.I18n.t('backToList')}
        />
      </FlexItem>
      {avatarUrl ? (
        <FlexItem>
          <img
            className="mailpoet-subscriber-stats-heading-avatar"
            src={avatarUrl}
            alt=""
            width={48}
            height={48}
          />
        </FlexItem>
      ) : null}
      <FlexBlock>
        <div className="mailpoet-subscriber-stats-heading-email">{email}</div>
        {subtitle ? (
          <div className="mailpoet-subscriber-stats-heading-subtitle">
            {subtitle}
          </div>
        ) : null}
      </FlexBlock>
    </Flex>
  );
}

StatsHeading.displayName = 'StatsHeading';
