import TooltipComponent from 'help-tooltip.jsx';
import React from 'react';
import ReactDOM from 'react-dom';

export const MailPoetHelpTooltip = {
  show: function show(domContainerNode, opts) {
    ReactDOM.render(React.createElement(TooltipComponent, {
      tooltip: opts.tooltip,
      tooltipId: opts.tooltipId,
      place: opts.place,
    }), domContainerNode);
  },
};

export default TooltipComponent;
