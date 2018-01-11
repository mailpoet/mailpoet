/**
 * Show Settings Behavior
 *
 * Opens up settings of a BlockView if contents are clicked upon
 */
define([
  'backbone.marionette',
  'jquery',
  'newsletter_editor/behaviors/BehaviorsLookup'
], function (Marionette, jQuery, BehaviorsLookup) { // eslint-disable-line func-names
  var BL = BehaviorsLookup;

  BL.ShowSettingsBehavior = Marionette.Behavior.extend({
    defaults: {
      ignoreFrom: '' // selector
    },
    events: {
      'click .mailpoet_content': 'showSettings'
    },
    showSettings: function (event) { // eslint-disable-line func-names
      if (!this.isIgnoredElement(event.target)) {
        this.view.triggerMethod('showSettings');
      }
    },
    isIgnoredElement: function (element) { // eslint-disable-line func-names
      return this.options.ignoreFrom
        && this.options.ignoreFrom.length > 0
        && jQuery(element).is(this.options.ignoreFrom);
    }
  });
});

