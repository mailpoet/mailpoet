import { Tooltip } from 'help-tooltip.jsx';
import { createElement } from 'react';
import { createRoot } from 'react-dom/client';

export const MailPoetHelpTooltip = {
  show: function show(domContainerNode, opts) {
    const root = createRoot(domContainerNode);
    root.render(
      createElement(Tooltip, {
        tooltip: opts.tooltip,
        tooltipId: opts.tooltipId,
        place: opts.place,
      }),
    );
  },
};
