import React from 'react';
import classNames from 'classnames';
import ReactTooltip from 'react-tooltip';

function Badge(props) {
  const badgeClasses = classNames(
    'mailpoet_badge',
    props.type ? `mailpoet_badge_${props.type}` : ''
  );

  const tooltip = props.tooltip ? props.tooltip.replace(/\n/g, '<br />') : false;
  // tooltip ID must be unique, defaults to tooltip text
  const tooltipId = props.tooltipId || tooltip;

  return (
    <span>
      <span
        className={badgeClasses}
        data-tip={tooltip}
        data-for={tooltipId}
      >
        {props.name}
      </span>
      { tooltip && (
        <ReactTooltip
          place="right"
          multiline={true}
          id={tooltipId}
        />
      ) }
    </span>
  );
}

export default Badge;
