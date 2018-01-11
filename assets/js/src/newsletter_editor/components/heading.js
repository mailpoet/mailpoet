'use strict';

define([
  'newsletter_editor/App',
  'backbone',
  'backbone.marionette',
  'underscore',
  'jquery',
  'mailpoet'
], function (App, Backbone, Marionette, _, jQuery, MailPoet) { // eslint-disable-line func-names
  var Module = {};

  Module.HeadingView = Marionette.View.extend({
    getTemplate: function () { return window.templates.heading; }, // eslint-disable-line func-names
    templateContext: function () { // eslint-disable-line func-names
      return {
        model: this.model.toJSON()
      };
    },
    events: function () { // eslint-disable-line func-names
      return {
        'keyup .mailpoet_input_title': _.partial(this.changeField, 'subject'),
        'keyup .mailpoet_input_preheader': _.partial(this.changeField, 'preheader')
      };
    },
    changeField: function (field, event) { // eslint-disable-line func-names
      this.model.set(field, jQuery(event.target).val());
    }
  });

  App.on('start', function (StartApp) { // eslint-disable-line func-names
    StartApp._appView.showChildView('headingRegion', new Module.HeadingView({ model: StartApp.getNewsletter() }));
    MailPoet.helpTooltip.show(document.getElementById('tooltip-designer-subject-line'), {
      tooltipId: 'tooltip-designer-subject-line-ti',
      tooltip: MailPoet.I18n.t('helpTooltipDesignerSubjectLine'),
      place: 'right'
    });
    MailPoet.helpTooltip.show(document.getElementById('tooltip-designer-preheader'), {
      tooltipId: 'tooltip-designer-preheader-ti',
      tooltip: MailPoet.I18n.t('helpTooltipDesignerPreheader')
    });
  });

  return Module;
});
