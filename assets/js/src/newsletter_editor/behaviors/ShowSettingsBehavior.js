/**
 * Show Settings Behavior
 *
 * Adds a color picker integration with the view
 */
define([
    'backbone.marionette',
    'jquery',
    'newsletter_editor/behaviors/BehaviorsLookup',
  ], function(Marionette, jQuery, BehaviorsLookup) {

  BehaviorsLookup.ShowSettingsBehavior = Marionette.Behavior.extend({
    defaults: {
      ignoreFrom: '',
    },
    events: {
      'click .mailpoet_content': 'showSettings',
    },
    showSettings: function(event) {
      if(!this.isIgnoredEvent(event.target)) {
        this.view.triggerMethod('showSettings');
      }
    },
    isIgnoredEvent: function(element) {
      return this.options.ignoreFrom
        && this.options.ignoreFrom.length > 0
        && jQuery(element).is(this.options.ignoreFrom);
    },
  });
});

