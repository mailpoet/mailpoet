'use strict';

/**
 * Footer content block
 */
define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/base',
  'underscore',
  'mailpoet'
], function footerBlock(App, BaseBlock, _, MailPoet) {
  var Module = {};
  var base = BaseBlock;

  Module.FooterBlockModel = base.BlockModel.extend({
    defaults: function defaults() {
      return this._getDefaults({
        type: 'footer',
        text: '<a href="[link:subscription_unsubscribe_url]">Unsubscribe</a> | <a href="[link:subscription_manage_url]">Manage subscription</a><br /><b>Add your postal address here!</b>',
        styles: {
          block: {
            backgroundColor: 'transparent'
          },
          text: {
            fontColor: '#000000',
            fontFamily: 'Arial',
            fontSize: '12px',
            textAlign: 'center'
          },
          link: {
            fontColor: '#0000ff',
            textDecoration: 'none'
          }
        }
      }, App.getConfig().get('blockDefaults.footer'));
    },
    _updateDefaults: function updateDefaults() {
      App.getConfig().set('blockDefaults.footer', _.omit(this.toJSON(), 'text'));
    }
  });

  Module.FooterBlockView = base.BlockView.extend({
    className: 'mailpoet_block mailpoet_footer_block mailpoet_droppable_block',
    getTemplate: function getTemplate() { return window.templates.footerBlock; },
    modelEvents: _.extend({
      'change:styles.block.backgroundColor change:styles.text.fontColor change:styles.text.fontFamily change:styles.text.fontSize change:styles.text.textAlign change:styles.link.fontColor change:styles.link.textDecoration': 'render'
    }, _.omit(base.BlockView.prototype.modelEvents, 'change')),
    behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
      TextEditorBehavior: {
        configurationFilter: function configurationFilter(originalSettings) {
          return _.extend({}, originalSettings, {
            mailpoet_shortcodes: App.getConfig().get('shortcodes').toJSON(),
            mailpoet_shortcodes_window_title: MailPoet.I18n.t('shortcodesWindowTitle')
          });
        }
      }
    }),
    onDragSubstituteBy: function onDragSubstituteBy() { return Module.FooterWidgetView; },
    onRender: function onRender() {
      this.toolsView = new Module.FooterBlockToolsView({ model: this.model });
      this.showChildView('toolsRegion', this.toolsView);
    },
    onTextEditorChange: function onTextEditorChange(newContent) {
      this.model.set('text', newContent);
    },
    onTextEditorFocus: function onTextEditorFocus() {
      this.disableDragging();
      this.disableShowingTools();
    },
    onTextEditorBlur: function onTextEditorBlur() {
      this.enableDragging();
      this.enableShowingTools();
    }
  });

  Module.FooterBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function getSettingsView() { return Module.FooterBlockSettingsView; }
  });

  Module.FooterBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function getTemplate() { return window.templates.footerBlockSettings; },
    events: function events() {
      return {
        'change .mailpoet_field_footer_text_color': _.partial(this.changeColorField, 'styles.text.fontColor'),
        'change .mailpoet_field_footer_text_font_family': _.partial(this.changeField, 'styles.text.fontFamily'),
        'change .mailpoet_field_footer_text_size': _.partial(this.changeField, 'styles.text.fontSize'),
        'change #mailpoet_field_footer_link_color': _.partial(this.changeColorField, 'styles.link.fontColor'),
        'change #mailpoet_field_footer_link_underline': function linkUnderline(event) {
          this.model.set('styles.link.textDecoration', (event.target.checked) ? event.target.value : 'none');
        },
        'change .mailpoet_field_footer_background_color': _.partial(this.changeColorField, 'styles.block.backgroundColor'),
        'change .mailpoet_field_footer_alignment': _.partial(this.changeField, 'styles.text.textAlign'),
        'click .mailpoet_done_editing': 'close'
      };
    },
    templateContext: function templateContext() {
      return _.extend({}, base.BlockView.prototype.templateContext.apply(this, arguments), {
        availableStyles: App.getAvailableStyles().toJSON()
      });
    }
  });

  Module.FooterWidgetView = base.WidgetView.extend({
    getTemplate: function getTemplate() { return window.templates.footerInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function drop() {
          return new Module.FooterBlockModel();
        }
      }
    }
  });

  App.on('before:start', function beforeAppStart(BeforeStartApp) {
    BeforeStartApp.registerBlockType('footer', {
      blockModel: Module.FooterBlockModel,
      blockView: Module.FooterBlockView
    });

    BeforeStartApp.registerWidget({
      name: 'footer',
      widgetView: Module.FooterWidgetView,
      priority: 99
    });
  });

  return Module;
});
