import PropTypes from 'prop-types';
import { Tooltip as ReactTooltip } from 'react-tooltip';
import ReactHtmlParser from 'react-html-parser';

function Tooltip(props) {
  let tooltipId = props.tooltipId;
  let tooltip = props.tooltip;
  // tooltip ID must be unique, defaults to tooltip text
  if (!props.tooltipId && typeof props.tooltip === 'string') {
    tooltipId = props.tooltip;
  }
  if (!tooltipId) {
    tooltipId = (Math.random() + 1).toString(36).substring(7);
  }

  if (typeof props.tooltip === 'string') {
    tooltip = (
      <span
        style={{
          pointerEvents: 'all',
          maxWidth: '400px',
          display: 'inline-block',
        }}
      >
        {ReactHtmlParser(props.tooltip)}
      </span>
    );
  }

  return (
    <span className={props.className}>
      <span
        style={{
          cursor: 'pointer',
        }}
        className="tooltip dashicons dashicons-editor-help"
        data-tip
        data-tooltip-id={tooltipId}
      />
      <ReactTooltip
        globalEventOff="click"
        openOnClick
        className="mailpoet-tooltip-message"
        id={tooltipId}
        place={props.place}
      >
        {tooltip}
      </ReactTooltip>
    </span>
  );
}

Tooltip.propTypes = {
  tooltipId: PropTypes.string,
  tooltip: PropTypes.node.isRequired,
  place: PropTypes.string,
  className: PropTypes.string,
};

export { Tooltip };
