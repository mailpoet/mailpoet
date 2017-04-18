import React from 'react'
import classNames from 'classnames'
import ReactTooltip from 'react-tooltip'

class Badge extends React.Component {
  render() {
    const badgeClasses = classNames(
      'mailpoet_badge',
      this.props.type ? `mailpoet_badge_${this.props.type}` : '',
      this.props.size ? `mailpoet_badge_size_${this.props.size}` : ''
    );

    const tooltip = this.props.tooltip ? this.props.tooltip.replace(/\n/g, '<br />') : false;
    // tooltip ID must be unique, defaults to tooltip text
    const tooltipId = this.props.tooltipId || tooltip;

    return (
      <span>
        <span
          className={badgeClasses}
          data-tip={tooltip}
          data-for={tooltipId}
        >
          {this.props.name}
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
}

export default Badge;
