import React from 'react';
import classNames from 'classnames';
import ReactTooltip from 'react-tooltip';
import PropTypes from 'prop-types';

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
          multiline
          id={tooltipId}
        />
      ) }
    </span>
  );
}

Badge.propTypes = {
  name: PropTypes.string.isRequired,
  tooltip: PropTypes.string,
  tooltipId: PropTypes.string,
  type: PropTypes.string,
};

Badge.defaultProps = {
  type: undefined,
  tooltipId: undefined,
  tooltip: undefined,
};


export default Badge;
