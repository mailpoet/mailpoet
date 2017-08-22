/**
 * This shim replaces the default Backbone.Marionette communication library
 * Backbone.Wreqr with Backbone.Radio ahead of time,
 * since this libraries will be switched in Marionette 3.x anyway
 *
 * Courtesy of https://gist.github.com/jmeas/7992474cdb1c5672d88b
 */

(function(root, factory) {
  if (typeof define === 'function' && define.amd) {
    define(['backbone.marionette', 'backbone.radio', 'underscore'], function(Marionette, Radio, _) {
      return factory(Marionette, Radio, _);
    });
  }
  else if (typeof exports !== 'undefined') {
    var Marionette = require('backbone.marionette');
    var Radio = require('backbone.radio');
    var _ = require('underscore');
    module.exports = factory(Marionette, Radio, _);
  }
  else {
    factory(root.Backbone.Marionette, root.Backbone.Radio, root._);
  }
}(this, function(Marionette, Radio, _) {
  'use strict';
  var MarionetteApplication = Marionette.Application;
  MarionetteApplication.prototype._initChannel = function () {
    this.channelName = _.result(this, 'channelName') || 'global';
    this.channel = _.result(this, 'channel') || Radio.channel(this.channelName);
  };
}));
