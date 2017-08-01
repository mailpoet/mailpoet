import React from 'react';
import ReactTooltip from 'react-tooltip';

function Tooltip(props) {
  let tooltipId = props.tooltipId;
  // tooltip ID must be unique, defaults to tooltip text
  if(!props.tooltipId && typeof props.tooltip === "string") {
    tooltipId = props.tooltip;
  }

  return (
    <span>
      <span
        style={{
          cursor: "help",
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
        >
          {props.tooltip}
        </ReactTooltip>
    </span>
  );
}

Tooltip.propTypes = {
  tooltipId: React.PropTypes.string,
  tooltip: React.PropTypes.node.isRequired,
};

Tooltip.defaultProps = {
  tooltipId: undefined,
};

module.exports = Tooltip;
