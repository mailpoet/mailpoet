import Marionette from 'backbone.marionette';
import BackboneRadio from 'backbone.radio';
import jQuery from 'jquery';
import _ from 'underscore'; // eslint-disable-line func-names

var Radio;
var AppView;
var EditorApplication;
var app;

// newsletter editor requires underscore on window.
// For other code to be able to use lodash we need to make sure underscore is present on window.
window._ = _;

Radio = BackboneRadio;

AppView = Marionette.View.extend({
  el: '#mailpoet_editor',
  regions: {
    stylesRegion: '#mailpoet_editor_styles',
    contentRegion: '#mailpoet_editor_content',
    sidebarRegion: '#mailpoet_editor_sidebar',
    bottomRegion: '#mailpoet_editor_bottom',
    headingRegion: '#mailpoet_editor_heading',
    historyRegion: '#mailpoet_editor_history',
    topRegion: '#mailpoet_editor_top',
  },

  events: {
    click: 'onClickOutsideContentHideSettings',
  },

  onClickOutsideContentHideSettings: function onClickOutsideContentHideSettings(
    event,
  ) {
    if (jQuery(event.target).parents('#mailpoet_editor_content').length) {
      return;
    }
    window.EditorApplication.getChannel().trigger('hideSettings');
  },
});

EditorApplication = Marionette.Application.extend({
  region: '#mailpoet_editor',

  onStart: function onStart() {
    this._appView = new AppView();
    this.showView(this._appView);
    this.listenTo(
      this.getChannel(),
      'settingsDisplayed',
      this.setDisplayedSettingsId,
    );
  },

  getChannel: function getChannel(channel) {
    if (channel === undefined) {
      return Radio.channel('global');
    }
    return Radio.channel(channel);
  },

  getDisplayedSettingsId: function getDisplayedSettingsId() {
    return this.displayedSettingsId;
  },

  setDisplayedSettingsId: function setDisplayedSettingsId(id) {
    this.displayedSettingsId = id;
  },
});

app = new EditorApplication();
window.EditorApplication = app;

export default app;
