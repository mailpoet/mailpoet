import { App } from 'newsletter_editor/App';
import Marionette from 'backbone.marionette';
import _ from 'underscore';
import jQuery from 'jquery';
import { MailPoet } from 'mailpoet'; // eslint-disable-line func-names

var Module = {};

Module.HeadingView = Marionette.View.extend({
  // eslint-disable-next-line func-names
  getTemplate: function () {
    return window.templates.heading;
  },
  // eslint-disable-next-line func-names
  templateContext: function () {
    return {
      model: this.model.toJSON(),
      isWoocommerceTransactional: this.model.isWoocommerceTransactional(),
      isAutomationEmail: this.model.isAutomationEmail(),
    };
  },
  // eslint-disable-next-line func-names
  events: function () {
    return {
      'change .mailpoet_input_title': _.partial(this.changeField, 'subject'),
      'change .mailpoet_input_preheader': _.partial(
        this.changeField,
        'preheader',
      ),
      'change #mailpoet_heading_email_type': (event) => {
        App.getChannel().trigger('changeWCEmailType', event.target.value);
      },
    };
  },
  // eslint-disable-next-line func-names
  changeField: function (field, event) {
    this.model.set(field, jQuery(event.target).val());
  },
});

// eslint-disable-next-line func-names
App.on('start', function (StartApp) {
  var model = StartApp.getNewsletter();
  StartApp._appView.showChildView(
    'headingRegion',
    new Module.HeadingView({ model: model }),
  );
  if (!model.isWoocommerceTransactional() && !model.isAutomationEmail()) {
    MailPoet.helpTooltip.show(
      document.getElementById('tooltip-designer-subject-line'),
      {
        tooltipId: 'tooltip-designer-subject-line-ti',
        tooltip: MailPoet.I18n.t('helpTooltipDesignerSubjectLine'),
        place: 'right',
      },
    );
    MailPoet.helpTooltip.show(
      document.getElementById('tooltip-designer-preheader'),
      {
        tooltipId: 'tooltip-designer-preheader-ti',
        tooltip:
          MailPoet.I18n.t('helpTooltipDesignerPreheader') +
          ' ' +
          MailPoet.I18n.t('helpTooltipDesignerPreheaderWarning'),
      },
    );
  }
});

export { Module as HeadingComponent };
