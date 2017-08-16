/**
 * BehaviorsLookup holds all behaviors and is used by Marionette to glue
 * Behavior instances to Views
 *
 * For more check: http://marionettejs.com/docs/marionette.behaviors.html#behaviorslookup
 */
define([
    'backbone.marionette'
  ], function(BackboneMarionette) {
  var Marionette = BackboneMarionette;
  var BehaviorsLookup = {};
  Marionette.Behaviors.behaviorsLookup = function() {
    return BehaviorsLookup;
  };

  window.BehaviorsLookup = BehaviorsLookup;

  return BehaviorsLookup;
});
