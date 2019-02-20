import Backbone from 'backbone';
import Marionette from 'backbone.marionette';
import BackboneRadio from 'backbone.radio'; // eslint-disable-line func-names

var Radio = BackboneRadio;

var AppView = Marionette.View.extend({
  el: '#mailpoet_editor',
  regions: {
    stylesRegion: '#mailpoet_editor_styles',
    contentRegion: '#mailpoet_editor_content',
    sidebarRegion: '#mailpoet_editor_sidebar',
    bottomRegion: '#mailpoet_editor_bottom',
    headingRegion: '#mailpoet_editor_heading',
    topRegion: '#mailpoet_editor_top'
  }
});

var EditorApplication = Marionette.Application.extend({
  region: '#mailpoet_editor',

  onStart: function () { // eslint-disable-line func-names
    this._appView = new AppView();
    this.showView(this._appView);
  },

  getChannel: function (channel) { // eslint-disable-line func-names
    if (channel === undefined) {
      return Radio.channel('global');
    }
    return Radio.channel(channel);
  }
});

var app = new EditorApplication();
window.EditorApplication = app;

export default app;
