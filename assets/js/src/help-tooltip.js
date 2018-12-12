var TooltipComponent = require('help-tooltip.jsx').default;
var React = require('react');
var ReactDOM = require('react-dom');
var MailPoet = require('mailpoet');

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
