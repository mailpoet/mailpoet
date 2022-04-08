/**
 * BehaviorsLookup holds all behaviors and is used by Marionette to glue
 * Behavior instances to Views
 *
 * For more check: http://marionettejs.com/docs/marionette.behaviors.html#behaviorslookup
 */
import BackboneMarionette from 'backbone.marionette'; // eslint-disable-line func-names

var Marionette = BackboneMarionette;
var BehaviorsLookup = {};

// eslint-disable-next-line func-names
Marionette.Behaviors.behaviorsLookup = function () {
  return BehaviorsLookup;
};

window.BehaviorsLookup = BehaviorsLookup;

export default BehaviorsLookup;
