/**
 * Show Settings Behavior
 *
 * Opens up settings of a BlockView if contents are clicked upon
 */
import Marionette from 'backbone.marionette';
import jQuery from 'jquery';
import BehaviorsLookup from 'newsletter_editor/behaviors/BehaviorsLookup'; // eslint-disable-line func-names

var BL = BehaviorsLookup;

BL.ShowSettingsBehavior = Marionette.Behavior.extend({
  defaults: {
    ignoreFrom: '', // selector
  },
  events: {
    'click .mailpoet_content': 'showSettings',
  },
  // eslint-disable-next-line func-names
  showSettings: function (event) {
    if (!this.isIgnoredElement(event.target)) {
      this.view.triggerMethod('showSettings');
    }
  },
  // eslint-disable-next-line func-names
  isIgnoredElement: function (element) {
    return (
      this.options.ignoreFrom &&
      this.options.ignoreFrom.length > 0 &&
      jQuery(element).is(this.options.ignoreFrom)
    );
  },
});
