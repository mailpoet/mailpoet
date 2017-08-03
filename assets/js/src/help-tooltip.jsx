import React from 'react';
import ReactTooltip from 'react-tooltip';
import ReactHtmlParser from 'react-html-parser';

function Tooltip(props) {
  let tooltipId = props.tooltipId;
  let tooltip = props.tooltip;
  // tooltip ID must be unique, defaults to tooltip text
  if(!props.tooltipId && typeof props.tooltip === "string") {
    tooltipId = props.tooltip;
  }

  if(typeof props.tooltip === "string") {
    tooltip = ReactHtmlParser(props.tooltip);
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
          {tooltip}
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
