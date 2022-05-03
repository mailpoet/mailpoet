import { Tooltip } from 'help-tooltip.jsx';
import { createElement } from 'react';
import ReactDOM from 'react-dom';

export const MailPoetHelpTooltip = {
  show: function show(domContainerNode, opts) {
    ReactDOM.render(
      createElement(Tooltip, {
        tooltip: opts.tooltip,
        tooltipId: opts.tooltipId,
        place: opts.place,
      }),
      domContainerNode,
    );
  },
};
