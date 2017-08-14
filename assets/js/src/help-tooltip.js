define('helpTooltip', ['mailpoet', 'react', 'react-dom', 'help-tooltip.jsx'],
  function (MailPoet, React, ReactDOM, TooltipComponent) {
    'use strict';

    MailPoet.helpTooltip = {
      show: function (domContainerNode, opts) {

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

