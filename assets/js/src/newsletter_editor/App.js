define([
    'backbone',
    'backbone.marionette',
    'backbone.supermodel',
    'jquery',
    'underscore',
    'handlebars',
    'handlebars_helpers',
    ], function(Backbone, Marionette, SuperModel, jQuery, _, Handlebars) {
  var app = new Marionette.Application(), AppView;

  // Decoupled communication between application components
  app.getChannel = function(channel) {
    if (channel === undefined) return app.channel;
    return Radio.channel(channel);
  };

  AppView = Marionette.LayoutView.extend({
    el: '#mailpoet_editor',
    regions: {
      stylesRegion: '#mailpoet_editor_styles',
      contentRegion: '#mailpoet_editor_content',
      sidebarRegion: '#mailpoet_editor_sidebar',
      bottomRegion: '#mailpoet_editor_bottom',
      headingRegion: '#mailpoet_editor_heading',
    },
  });

  app.on('start', function(options) {
    app._appView = new AppView();
  });

  window.EditorApplication = app;
  return app;
});
