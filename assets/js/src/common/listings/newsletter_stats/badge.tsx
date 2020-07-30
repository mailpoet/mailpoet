import React from 'react';
import classNames from 'classnames';
import ReactStringReplace from 'react-string-replace';
import Tooltip from '../../tooltip/tooltip';

type BadgeProps = {
  name: string,
  tooltip?: string | React.ReactNode,
  tooltipId?: string,
  type?: string,
}

function Badge(props: BadgeProps) {
  const badgeClasses = classNames(
    'mailpoet-listing-stats-badge',
    props.type ? `mailpoet-listing-stats-badge-${props.type}` : ''
  );

  const tooltip = props.tooltip || false;
  // tooltip ID must be unique, defaults to tooltip text
  const tooltipId = props.tooltipId || tooltip.toString();

  return (
    <span>
      <span
        className={badgeClasses}
        data-tip
        data-for={tooltipId}
      >
        {props.name}
      </span>
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
