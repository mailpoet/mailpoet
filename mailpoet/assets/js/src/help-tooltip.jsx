import PropTypes from 'prop-types';
import { Tooltip as ReactTooltip } from 'react-tooltip';
import { HelpTooltipContent } from './common/tooltip-html';

function Tooltip(props) {
  let tooltipId = props.tooltipId;
  // tooltip ID must be unique, defaults to tooltip text
  if (!props.tooltipId && typeof props.tooltip === 'string') {
    tooltipId = props.tooltip;
  }
  if (!tooltipId) {
    tooltipId = (Math.random() + 1).toString(36).substring(7);
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
        <HelpTooltipContent tooltip={props.tooltip} />
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
