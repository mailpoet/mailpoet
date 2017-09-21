/**
 * Show Settings Behavior
 *
 * Opens up settings of a BlockView if contents are clicked upon
 */
define([
  'backbone.marionette',
  'jquery',
  'newsletter_editor/behaviors/BehaviorsLookup'
], function (Marionette, jQuery, BehaviorsLookup) {
  var BL = BehaviorsLookup;

  BL.ShowSettingsBehavior = Marionette.Behavior.extend({
    defaults: {
      ignoreFrom: '' // selector
    },
    events: {
      'click .mailpoet_content': 'showSettings'
    },
    showSettings: function (event) {
      if (!this.isIgnoredElement(event.target)) {
        this.view.triggerMethod('showSettings');
      }
    },
    isIgnoredElement: function (element) {
      return this.options.ignoreFrom
        && this.options.ignoreFrom.length > 0
        && jQuery(element).is(this.options.ignoreFrom);
    }
  });
});

