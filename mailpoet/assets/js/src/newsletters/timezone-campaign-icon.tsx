import { __ } from '@wordpress/i18n';
import { Icon, globe } from '@wordpress/icons';
import { Tooltip } from 'common/tooltip/tooltip';

type Props = {
  newsletterId: string;
};

export function TimezoneCampaignIcon({ newsletterId }: Props) {
  const tooltipId = `timezone-campaign-${newsletterId}`;
  return (
    <span
      data-tip
      data-tooltip-id={tooltipId}
      data-automation-id={`timezone_campaign_status_${newsletterId}`}
      style={{ marginLeft: '4px' }}
    >
      <Icon
        icon={globe}
        size={20}
        style={{ fill: 'currentColor', verticalAlign: 'middle' }}
      />
      <Tooltip place="right" id={tooltipId}>
        {__("Sent in subscriber's time zone", 'mailpoet')}
      </Tooltip>
    </span>
  );
}

TimezoneCampaignIcon.displayName = 'TimezoneCampaignIcon';
