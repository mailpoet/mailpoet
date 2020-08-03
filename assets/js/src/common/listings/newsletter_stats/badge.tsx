import React from 'react';
import Tag from '../../tag/tag';
import Tooltip from '../../tooltip/tooltip';

type BadgeProps = {
  name: string,
  tooltip?: string | React.ReactNode,
  tooltipId?: string,
  type?: 'average' | 'good' | 'excellent',
}

function Badge(props: BadgeProps) {
  const tooltip = props.tooltip || false;
  // tooltip ID must be unique, defaults to tooltip text
  const tooltipId = props.tooltipId || tooltip.toString();

  return (
    <span>
      <Tag
        isInverted
        variant={props.type}
        data-tip
        data-for={tooltipId}
      >
        {props.name}
      </Tag>
      { tooltip && (
        <Tooltip
          place="top"
          multiline
          id={tooltipId}
        >
          {tooltip}
        </Tooltip>
      ) }
    </span>
  );
}

export default Badge;
