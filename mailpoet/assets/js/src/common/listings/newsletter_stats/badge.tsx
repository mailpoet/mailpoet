import * as React from 'react';
import { Place } from 'react-tooltip';
import Tag from '../../tag/tag';
import Tooltip from '../../tooltip/tooltip';

type BadgeProps = {
  name: string;
  tooltip?: string | React.ReactNode;
  tooltipId?: string;
  tooltipPlace?: Place;
  type?: 'average' | 'good' | 'excellent' | 'unknown';
  isInverted?: boolean;
}

function Badge({
  name,
  tooltip,
  tooltipId,
  tooltipPlace,
  type,
  isInverted,
}: BadgeProps) {
  return (
    <span>
      <Tag
        isInverted={isInverted}
        variant={type}
        data-tip
        data-for={tooltipId}
      >
        {name}
      </Tag>
      { tooltip && (
        <Tooltip
          place={tooltipPlace || 'top'}
          multiline
          id={tooltipId || tooltip.toString()}
        >
          {tooltip}
        </Tooltip>
      ) }
    </span>
  );
}

Badge.defaultProps = {
  isInverted: true,
};

export default Badge;
