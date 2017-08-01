define('helpTooltip', ['mailpoet', 'React', 'react-dom', 'help-tooltip.jsx'],
  function (MailPoet, React, ReactDOM, TooltipComponent) {
    'use strict';

    MailPoet.helpTooltip = {
      show: function (domContainerNode, opts) {

        var tooltipText = React.createElement(
          "span",
          {
            style: {
              pointerEvents: "all",
            },
            "dangerouslySetInnerHTML": {
              __html: opts.tooltip,
            },
          },
          null
        );

        ReactDOM.render(React.createElement(
          TooltipComponent, {
            tooltip: tooltipText,
            tooltipId: opts.tooltipId,
          }
        ), domContainerNode);
      },
    };

  }
);

