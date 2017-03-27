define([
    'newsletter_editor/App',
    'backbone',
    'backbone.marionette',
    'underscore',
    'jquery'
  ], function(App, Backbone, Marionette, _, jQuery) {

  "use strict";

  var Module = {};

  Module.HeadingView = Marionette.View.extend({
    getTemplate: function() { return templates.heading; },
    templateContext: function() {
      return {
        model: this.model.toJSON(),
      };
    },
    events: function() {
      return {
        'keyup .mailpoet_input_title': _.partial(this.changeField, "subject"),
        'keyup .mailpoet_input_preheader': _.partial(this.changeField, "preheader"),
      };
    },
    changeField: function(field, event) {
      this.model.set(field, jQuery(event.target).val());
    },
  });

  App.on('start', function(App, options) {
    App._appView.showChildView('headingRegion', new Module.HeadingView({ model: App.getNewsletter() }));
  });

  return Module;
});
