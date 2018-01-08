/**
 * BehaviorsLookup holds all behaviors and is used by Marionette to glue
 * Behavior instances to Views
 *
 * For more check: http://marionettejs.com/docs/marionette.behaviors.html#behaviorslookup
 */
define([
  'backbone.marionette'
], function (BackboneMarionette) { // eslint-disable-line func-names
  var Marionette = BackboneMarionette;
  var BehaviorsLookup = {};
  Marionette.Behaviors.behaviorsLookup = function () { // eslint-disable-line func-names
    return BehaviorsLookup;
  };

  window.BehaviorsLookup = BehaviorsLookup;

  return BehaviorsLookup;
});
