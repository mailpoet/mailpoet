import Marionette from 'backbone.marionette';
import BackboneRadio from 'backbone.radio';
import jQuery from 'jquery';

var Radio = BackboneRadio;

var AppView = Marionette.View.extend({
  el: '#mailpoet_editor',
  regions: {
    stylesRegion: '#mailpoet_editor_styles',
    contentRegion: '#mailpoet_editor_content',
    sidebarRegion: '#mailpoet_editor_sidebar',
    bottomRegion: '#mailpoet_editor_bottom',
    headingRegion: '#mailpoet_editor_heading',
    topRegion: '#mailpoet_editor_top',
  },

  events: {
    click: 'onClickOutsideContentHideSettings',
  },

  onClickOutsideContentHideSettings: function onClickOutsideContentHideSettings(event) {
    if (jQuery(event.target).parents('#mailpoet_editor_content').length) {
      return;
    }
    window.EditorApplication.getChannel().trigger('hideSettings');
  },
});

var EditorApplication = Marionette.Application.extend({
  region: '#mailpoet_editor',

  onStart: function onStart() {
    this._appView = new AppView();
    this.showView(this._appView);
    this.listenTo(this.getChannel(), 'settingsShowed', this.setShowedSettingsId);
  },

  getChannel: function getChannel(channel) {
    if (channel === undefined) {
      return Radio.channel('global');
    }
    return Radio.channel(channel);
  },

  getShowedSettingsId: function getShowedSettingsId() {
    return this.showedSettingsId;
  },

  setShowedSettingsId: function setShowedSettingsId(id) {
    this.showedSettingsId = id;
  },
});

var app = new EditorApplication();
window.EditorApplication = app;

export default app;
