import { ReactNode } from 'react';
import { PlacesType } from 'react-tooltip';
import { Tag } from '../../tag';
import { Tooltip } from '../../tooltip/tooltip';

type BadgeProps = {
  name: string;
  tooltip?: string | ReactNode;
  tooltipId?: string;
  tooltipPlace?: PlacesType;
  type?: 'average' | 'good' | 'excellent' | 'critical' | 'unknown';
  isInverted?: boolean;
};

function Badge({
  name,
  tooltip,
  tooltipId,
  tooltipPlace,
  type,
  isInverted = true,
}: BadgeProps) {
  return (
    <span>
      <Tag isInverted={isInverted} variant={type} data-tip data-for={tooltipId}>
        {name}
      </Tag>
      {tooltip && (
        <Tooltip
          place={tooltipPlace || 'top'}
          multiline
          id={tooltipId || tooltip.toString()}
        >
          {tooltip}
        </Tooltip>
      )}
    </span>
  );
}

export { Badge };
