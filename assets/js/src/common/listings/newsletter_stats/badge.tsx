import React from 'react';
import Tag from '../../tag/tag';
import Tooltip from '../../tooltip/tooltip';

type BadgeProps = {
  name: string,
  tooltip?: string | React.ReactNode,
  tooltipId?: string,
  type?: 'average' | 'good' | 'excellent',
  isInverted?: boolean,
}

function Badge({
  name,
  tooltip,
  tooltipId,
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
          place="top"
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
