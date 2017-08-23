import React from 'react';
import ReactTooltip from 'react-tooltip';
import ReactHtmlParser from 'react-html-parser';

function Tooltip(props) {
  let tooltipId = props.tooltipId;
  let tooltip = props.tooltip;
  // tooltip ID must be unique, defaults to tooltip text
  if(!props.tooltipId && typeof props.tooltip === 'string') {
    tooltipId = props.tooltip;
  }

  if(typeof props.tooltip === 'string') {
    tooltip = (<span
      style={{
        pointerEvents: 'all',
        maxWidth: '400',
        display: 'inline-block',
      }}
    >
      {ReactHtmlParser(props.tooltip)}
    </span>);
  }

  return (
    <span className={props.className}>
      <span
        style={{
          cursor: 'pointer',
        }}
        className="tooltip dashicons dashicons-editor-help"
        data-event="click"
        data-tip
        data-for={tooltipId}
      >
      </span>
        <ReactTooltip
          globalEventOff="click"
          multiline={true}
          id={tooltipId}
          efect="solid"
          place={props.place}
        >
          {tooltip}
        </ReactTooltip>
    </span>
  );
}

Tooltip.propTypes = {
  tooltipId: React.PropTypes.string,
  tooltip: React.PropTypes.node.isRequired,
  place: React.PropTypes.string,
  className: React.PropTypes.string,
};

Tooltip.defaultProps = {
  tooltipId: undefined,
  place: undefined,
  className: undefined,
};

module.exports = Tooltip;
