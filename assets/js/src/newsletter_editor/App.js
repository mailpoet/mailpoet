define([
  'backbone',
  'backbone.marionette',
  'backbone.radio'
], function (Backbone, Marionette, BackboneRadio) {
  var Radio = BackboneRadio;

  var AppView = Marionette.View.extend({
    el: '#mailpoet_editor',
    regions: {
      stylesRegion: '#mailpoet_editor_styles',
      contentRegion: '#mailpoet_editor_content',
      sidebarRegion: '#mailpoet_editor_sidebar',
      bottomRegion: '#mailpoet_editor_bottom',
      headingRegion: '#mailpoet_editor_heading'
    }
  });

  var EditorApplication = Marionette.Application.extend({
    region: '#mailpoet_editor',

    onStart: function () {
      this._appView = new AppView();
      this.showView(this._appView);
    },

    getChannel: function (channel) {
      if (channel === undefined) {
        return Radio.channel('global');
      }
      return Radio.channel(channel);
    }
  });

  var app = new EditorApplication();
  window.EditorApplication = app;

  return app;

});
