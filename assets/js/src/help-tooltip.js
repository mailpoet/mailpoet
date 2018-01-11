define('helpTooltip', ['mailpoet', 'react', 'react-dom', 'help-tooltip.jsx'],
  function helpTooltip(mp, React, ReactDOM, TooltipComponent) {
    'use strict';

    var MailPoet = mp;

    MailPoet.helpTooltip = {
      show: function show(domContainerNode, opts) {
        ReactDOM.render(React.createElement(
          TooltipComponent, {
            tooltip: opts.tooltip,
            tooltipId: opts.tooltipId,
            place: opts.place
          }
        ), domContainerNode);
      }
    };
  }
);

