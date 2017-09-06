var chai = require('chai');
var sinon = require('sinon');
var sinonChai = require('sinon-chai');
var chaiJq = require('chai-jq');
var _ = require('underscore');

chai.use(sinonChai);
chai.use(chaiJq);

global.expect = chai.expect;
global.sinon = sinon;

if (!global.document || !global.window) {
  var jsdom = require('jsdom').jsdom;

  global.document = jsdom('<html><head><script></script></head><body></body></html>', {}, {
    FetchExternalResources: ['script'],
    ProcessExternalResources: ['script'],
    MutationEvents: '2.0',
    QuerySelector: false
  });

  global.window = document.parentWindow;
  global.navigator = global.window.navigator;

  global.window.Node.prototype.contains = function (node) {
    return this.compareDocumentPosition(node) & 16;
  };
}
const testHelpers = require('./loadHelpers.js');
global.testHelpers = testHelpers;
const jQuery = require('jquery');
global.$ = jQuery;
global.jQuery = jQuery;
global.window.jQuery = jQuery;


testHelpers.loadScript('tests/javascript/testBundles/vendor.js', global.window);
const Handlebars = global.window.Handlebars;
global.Handlebars = global.window.Handlebars;

// Stub out interact.js
global.interact = function () {
  return {
    draggable: global.interact,
    restrict: global.interact,
    resizable: global.interact,
    on: global.interact,
    dropzone: global.interact,
    preventDefault: global.interact,
    actionChecker: global.interact,
    styleCursor: global.interact
  };
};
global.spectrum = function() { return this; };
jQuery.fn.spectrum = global.spectrum;
jQuery.fn.stick_in_parent = function() { return this; };

// Add global stubs for convenience
// TODO: Extract those to a separate file
global.stubChannel = function (EditorApplication, returnObject) {
  var App = EditorApplication;
  App.getChannel = sinon.stub().returns(_.defaults(returnObject || {}, {
    request: function () {
    },
    trigger: function () {
    },
    on: function () {
    }
  }));
};
global.stubConfig = function (EditorApplication, opts) {
  var App = EditorApplication;
  App.getConfig = sinon.stub().returns(new global.Backbone.SuperModel(opts || {}));
};
global.stubAvailableStyles = function (EditorApplication, styles) {
  var App = EditorApplication;
  App.getAvailableStyles = sinon.stub().returns(new global.Backbone.SuperModel(styles || {}));
};

global.stubImage = function(defaultWidth, defaultHeight) {
  global.Image = function() {
    this.onload = function() {};
    this.naturalWidth = defaultWidth;
    this.naturalHeight = defaultHeight;
    this.address = '';

    Object.defineProperty(this, 'src', {
      get: function() {
        return this.address;
      },
      set: function(src) {
        this.address = src;
        this.onload();
      }
    });
  };
};


testHelpers.loadTemplate('blocks/base/toolsGeneric.hbs', window, {id: 'newsletter_editor_template_tools_generic'});

testHelpers.loadTemplate('blocks/automatedLatestContent/block.hbs', window, {id: 'newsletter_editor_template_automated_latest_content_block'});
testHelpers.loadTemplate('blocks/automatedLatestContent/widget.hbs', window, {id: 'newsletter_editor_template_automated_latest_content_widget'});
testHelpers.loadTemplate('blocks/automatedLatestContent/settings.hbs', window, {id: 'newsletter_editor_template_automated_latest_content_settings'});

testHelpers.loadTemplate('blocks/button/block.hbs', window, {id: 'newsletter_editor_template_button_block'});
testHelpers.loadTemplate('blocks/button/widget.hbs', window, {id: 'newsletter_editor_template_button_widget'});
testHelpers.loadTemplate('blocks/button/settings.hbs', window, {id: 'newsletter_editor_template_button_settings'});

testHelpers.loadTemplate('blocks/container/block.hbs', window, {id: 'newsletter_editor_template_container_block'});
testHelpers.loadTemplate('blocks/container/emptyBlock.hbs', window, {id: 'newsletter_editor_template_container_block_empty'});
testHelpers.loadTemplate('blocks/container/oneColumnLayoutWidget.hbs', window, {id: 'newsletter_editor_template_container_one_column_widget'});
testHelpers.loadTemplate('blocks/container/twoColumnLayoutWidget.hbs', window, {id: 'newsletter_editor_template_container_two_column_widget'});
testHelpers.loadTemplate('blocks/container/threeColumnLayoutWidget.hbs', window, {id: 'newsletter_editor_template_container_three_column_widget'});
testHelpers.loadTemplate('blocks/container/settings.hbs', window, {id: 'newsletter_editor_template_container_settings'});
testHelpers.loadTemplate('blocks/container/columnSettings.hbs', window, {id: 'newsletter_editor_template_container_column_settings'});

testHelpers.loadTemplate('blocks/divider/block.hbs', window, {id: 'newsletter_editor_template_divider_block'});
testHelpers.loadTemplate('blocks/divider/widget.hbs', window, {id: 'newsletter_editor_template_divider_widget'});
testHelpers.loadTemplate('blocks/divider/settings.hbs', window, {id: 'newsletter_editor_template_divider_settings'});

testHelpers.loadTemplate('blocks/footer/block.hbs', window, {id: 'newsletter_editor_template_footer_block'});
testHelpers.loadTemplate('blocks/footer/widget.hbs', window, {id: 'newsletter_editor_template_footer_widget'});
testHelpers.loadTemplate('blocks/footer/settings.hbs', window, {id: 'newsletter_editor_template_footer_settings'});

testHelpers.loadTemplate('blocks/header/block.hbs', window, {id: 'newsletter_editor_template_header_block'});
testHelpers.loadTemplate('blocks/header/widget.hbs', window, {id: 'newsletter_editor_template_header_widget'});
testHelpers.loadTemplate('blocks/header/settings.hbs', window, {id: 'newsletter_editor_template_header_settings'});

testHelpers.loadTemplate('blocks/image/block.hbs', window, {id: 'newsletter_editor_template_image_block'});
testHelpers.loadTemplate('blocks/image/widget.hbs', window, {id: 'newsletter_editor_template_image_widget'});
testHelpers.loadTemplate('blocks/image/settings.hbs', window, {id: 'newsletter_editor_template_image_settings'});

testHelpers.loadTemplate('blocks/posts/block.hbs', window, {id: 'newsletter_editor_template_posts_block'});
testHelpers.loadTemplate('blocks/posts/widget.hbs', window, {id: 'newsletter_editor_template_posts_widget'});
testHelpers.loadTemplate('blocks/posts/settings.hbs', window, {id: 'newsletter_editor_template_posts_settings'});
testHelpers.loadTemplate('blocks/posts/settingsDisplayOptions.hbs', window, {id: 'newsletter_editor_template_posts_settings_display_options'});
testHelpers.loadTemplate('blocks/posts/settingsSelection.hbs', window, {id: 'newsletter_editor_template_posts_settings_selection'});
testHelpers.loadTemplate('blocks/posts/settingsSelectionEmpty.hbs', window, {id: 'newsletter_editor_template_posts_settings_selection_empty'});
testHelpers.loadTemplate('blocks/posts/settingsSinglePost.hbs', window, {id: 'newsletter_editor_template_posts_settings_single_post'});

testHelpers.loadTemplate('blocks/social/block.hbs', window, {id: 'newsletter_editor_template_social_block'});
testHelpers.loadTemplate('blocks/social/blockIcon.hbs', window, {id: 'newsletter_editor_template_social_block_icon'});
testHelpers.loadTemplate('blocks/social/widget.hbs', window, {id: 'newsletter_editor_template_social_widget'});
testHelpers.loadTemplate('blocks/social/settings.hbs', window, {id: 'newsletter_editor_template_social_settings'});
testHelpers.loadTemplate('blocks/social/settingsIcon.hbs', window, {id: 'newsletter_editor_template_social_settings_icon'});
testHelpers.loadTemplate('blocks/social/settingsIconSelector.hbs', window, {id: 'newsletter_editor_template_social_settings_icon_selector'});
testHelpers.loadTemplate('blocks/social/settingsStyles.hbs', window, {id: 'newsletter_editor_template_social_settings_styles'});

testHelpers.loadTemplate('blocks/spacer/block.hbs', window, {id: 'newsletter_editor_template_spacer_block'});
testHelpers.loadTemplate('blocks/spacer/widget.hbs', window, {id: 'newsletter_editor_template_spacer_widget'});
testHelpers.loadTemplate('blocks/spacer/settings.hbs', window, {id: 'newsletter_editor_template_spacer_settings'});

testHelpers.loadTemplate('blocks/text/block.hbs', window, {id: 'newsletter_editor_template_text_block'});
testHelpers.loadTemplate('blocks/text/widget.hbs', window, {id: 'newsletter_editor_template_text_widget'});
testHelpers.loadTemplate('blocks/text/settings.hbs', window, {id: 'newsletter_editor_template_text_settings'});

testHelpers.loadTemplate('components/heading.hbs', window, {id: 'newsletter_editor_template_heading'});
testHelpers.loadTemplate('components/save.hbs', window, {id: 'newsletter_editor_template_save'});
testHelpers.loadTemplate('components/styles.hbs', window, {id: 'newsletter_editor_template_styles'});

testHelpers.loadTemplate('components/sidebar/sidebar.hbs', window, {id: 'newsletter_editor_template_sidebar'});
testHelpers.loadTemplate('components/sidebar/content.hbs', window, {id: 'newsletter_editor_template_sidebar_content'});
testHelpers.loadTemplate('components/sidebar/layout.hbs', window, {id: 'newsletter_editor_template_sidebar_layout'});
testHelpers.loadTemplate('components/sidebar/preview.hbs', window, {id: 'newsletter_editor_template_sidebar_preview'});
testHelpers.loadTemplate('components/sidebar/styles.hbs', window, {id: 'newsletter_editor_template_sidebar_styles'});


global.templates = {
  styles: Handlebars.compile(jQuery('#newsletter_editor_template_styles').html()),
  save: Handlebars.compile(jQuery('#newsletter_editor_template_save').html()),
  heading: Handlebars.compile(jQuery('#newsletter_editor_template_heading').html()),

  sidebar: Handlebars.compile(jQuery('#newsletter_editor_template_sidebar').html()),
  sidebarContent: Handlebars.compile(jQuery('#newsletter_editor_template_sidebar_content').html()),
  sidebarLayout: Handlebars.compile(jQuery('#newsletter_editor_template_sidebar_layout').html()),
  sidebarStyles: Handlebars.compile(jQuery('#newsletter_editor_template_sidebar_styles').html()),
  sidebarPreview: Handlebars.compile(jQuery('#newsletter_editor_template_sidebar_preview').html()),

  genericBlockTools: Handlebars.compile(jQuery('#newsletter_editor_template_tools_generic').html()),

  containerBlock: Handlebars.compile(jQuery('#newsletter_editor_template_container_block').html()),
  containerEmpty: Handlebars.compile(jQuery('#newsletter_editor_template_container_block_empty').html()),
  oneColumnLayoutInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_container_one_column_widget').html()),
  twoColumnLayoutInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_container_two_column_widget').html()),
  threeColumnLayoutInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_container_three_column_widget').html()),
  containerBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_container_settings').html()),
  containerBlockColumnSettings: Handlebars.compile(jQuery('#newsletter_editor_template_container_column_settings').html()),

  buttonBlock: Handlebars.compile(jQuery('#newsletter_editor_template_button_block').html()),
  buttonInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_button_widget').html()),
  buttonBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_button_settings').html()),

  dividerBlock: Handlebars.compile(jQuery('#newsletter_editor_template_divider_block').html()),
  dividerInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_divider_widget').html()),
  dividerBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_divider_settings').html()),

  footerBlock: Handlebars.compile(jQuery('#newsletter_editor_template_footer_block').html()),
  footerInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_footer_widget').html()),
  footerBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_footer_settings').html()),

  headerBlock: Handlebars.compile(jQuery('#newsletter_editor_template_header_block').html()),
  headerInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_header_widget').html()),
  headerBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_header_settings').html()),

  imageBlock: Handlebars.compile(jQuery('#newsletter_editor_template_image_block').html()),
  imageInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_image_widget').html()),
  imageBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_image_settings').html()),

  socialBlock: Handlebars.compile(jQuery('#newsletter_editor_template_social_block').html()),
  socialIconBlock: Handlebars.compile(jQuery('#newsletter_editor_template_social_block_icon').html()),
  socialInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_social_widget').html()),
  socialBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_social_settings').html()),
  socialSettingsIconSelector: Handlebars.compile(jQuery('#newsletter_editor_template_social_settings_icon_selector').html()),
  socialSettingsIcon: Handlebars.compile(jQuery('#newsletter_editor_template_social_settings_icon').html()),
  socialSettingsStyles: Handlebars.compile(jQuery('#newsletter_editor_template_social_settings_styles').html()),

  spacerBlock: Handlebars.compile(jQuery('#newsletter_editor_template_spacer_block').html()),
  spacerInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_spacer_widget').html()),
  spacerBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_spacer_settings').html()),

  automatedLatestContentBlock: Handlebars.compile(jQuery('#newsletter_editor_template_automated_latest_content_block').html()),
  automatedLatestContentInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_automated_latest_content_widget').html()),
  automatedLatestContentBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_automated_latest_content_settings').html()),

  postsBlock: Handlebars.compile(jQuery('#newsletter_editor_template_posts_block').html()),
  postsInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_posts_widget').html()),
  postsBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_posts_settings').html()),
  postSelectionPostsBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_posts_settings_selection').html()),
  emptyPostPostsBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_posts_settings_selection_empty').html()),
  singlePostPostsBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_posts_settings_single_post').html()),
  displayOptionsPostsBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_posts_settings_display_options').html()),

  textBlock: Handlebars.compile(jQuery('#newsletter_editor_template_text_block').html()),
  textInsertion: Handlebars.compile(jQuery('#newsletter_editor_template_text_widget').html()),
  textBlockSettings: Handlebars.compile(jQuery('#newsletter_editor_template_text_settings').html())
};
global.window.templates = global.templates;