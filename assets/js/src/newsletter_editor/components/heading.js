import App from 'newsletter_editor/App';
import Marionette from 'backbone.marionette';
import _ from 'underscore';
import jQuery from 'jquery';
import MailPoet from 'mailpoet'; // eslint-disable-line func-names

var Module = {};

Module.HeadingView = Marionette.View.extend({
  getTemplate: function () { return window.templates.heading; }, // eslint-disable-line func-names
  templateContext: function () { // eslint-disable-line func-names
    return {
      model: this.model.toJSON(),
      isWoocommerceTransactional: this.model.isWoocommerceTransactional(),
    };
  },
  events: function () { // eslint-disable-line func-names
    return {
      'keyup .mailpoet_input_title': _.partial(this.changeField, 'subject'),
      'keyup .mailpoet_input_preheader': _.partial(this.changeField, 'preheader'),
      'change #mailpoet_heading_email_type': (event) => {
        App.getChannel().trigger('changeWCEmailType', event.target.value);
      },
    };
  },
  changeField: function (field, event) { // eslint-disable-line func-names
    this.model.set(field, jQuery(event.target).val());
  },
});

App.on('start', function (StartApp) { // eslint-disable-line func-names
  var model = StartApp.getNewsletter();
  StartApp._appView.showChildView('headingRegion', new Module.HeadingView({ model: model }));
  if (!model.isWoocommerceTransactional()) {
    MailPoet.helpTooltip.show(document.getElementById('tooltip-designer-subject-line'), {
      tooltipId: 'tooltip-designer-subject-line-ti',
      tooltip: MailPoet.I18n.t('helpTooltipDesignerSubjectLine'),
      place: 'right',
    });
    MailPoet.helpTooltip.show(document.getElementById('tooltip-designer-preheader'), {
      tooltipId: 'tooltip-designer-preheader-ti',
      tooltip: MailPoet.I18n.t('helpTooltipDesignerPreheader')
        + ' '
        + MailPoet.I18n.t('helpTooltipDesignerPreheaderWarning'),
    });
  }
});

export default Module;
