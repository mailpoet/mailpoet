define([
    'newsletter_editor/App',
    'backbone',
    'backbone.marionette',
    'underscore',
    'jquery',
  ], function(App, Backbone, Marionette, _, jQuery) {

  "use strict";

  var Module = {};

  Module.HeadingView = Marionette.ItemView.extend({
    getTemplate: function() { return templates.heading; },
    templateHelpers: function() {
      return {
        model: this.model.toJSON(),
      };
    },
    events: function() {
      return {
        'keyup .mailpoet_input_title': _.partial(this.changeField, "newsletter_subject"),
        'keyup .mailpoet_input_preheader': _.partial(this.changeField, "newsletter_preheader"),
      };
    },
    changeField: function(field, event) {
      this.model.set(field, jQuery(event.target).val());
    },
  });

  App.on('start', function(options) {
    App._appView.headingRegion.show(new Module.HeadingView({ model: App.getNewsletter() }));
  });

  return Module;
});
