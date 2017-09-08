/**
 * Footer content block
 */
define([
  'newsletter_editor/App',
  'newsletter_editor/blocks/base',
  'underscore'
], function(App, BaseBlock, _) {

  'use strict';

  var Module = {},
    base = BaseBlock;

  Module.FooterBlockModel = base.BlockModel.extend({
    defaults: function() {
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
    }
  });

  Module.FooterBlockView = base.BlockView.extend({
    className: 'mailpoet_block mailpoet_footer_block mailpoet_droppable_block',
    getTemplate: function() { return templates.footerBlock; },
    modelEvents: _.extend({
      'change:styles.block.backgroundColor change:styles.text.fontColor change:styles.text.fontFamily change:styles.text.fontSize change:styles.text.textAlign change:styles.link.fontColor change:styles.link.textDecoration': 'render'
    }, _.omit(base.BlockView.prototype.modelEvents, 'change')),
    behaviors: _.extend({}, base.BlockView.prototype.behaviors, {
      TextEditorBehavior: {
        configurationFilter: function(originalSettings) {
          return _.extend({}, originalSettings, {
            mailpoet_shortcodes: App.getConfig().get('shortcodes').toJSON(),
            mailpoet_shortcodes_window_title: MailPoet.I18n.t('shortcodesWindowTitle')
          });
        }
      }
    }),
    onDragSubstituteBy: function() { return Module.FooterWidgetView; },
    onRender: function() {
      this.toolsView = new Module.FooterBlockToolsView({ model: this.model });
      this.showChildView('toolsRegion', this.toolsView);
    },
    onTextEditorChange: function(newContent) {
      this.model.set('text', newContent);
    },
    onTextEditorFocus: function() {
      this.disableDragging();
      this.disableShowingTools();
    },
    onTextEditorBlur: function() {
      this.enableDragging();
      this.enableShowingTools();
    }
  });

  Module.FooterBlockToolsView = base.BlockToolsView.extend({
    getSettingsView: function() { return Module.FooterBlockSettingsView; }
  });

  Module.FooterBlockSettingsView = base.BlockSettingsView.extend({
    getTemplate: function() { return templates.footerBlockSettings; },
    events: function() {
      return {
        'change .mailpoet_field_footer_text_color': _.partial(this.changeColorField, 'styles.text.fontColor'),
        'change .mailpoet_field_footer_text_font_family': _.partial(this.changeField, 'styles.text.fontFamily'),
        'change .mailpoet_field_footer_text_size': _.partial(this.changeField, 'styles.text.fontSize'),
        'change #mailpoet_field_footer_link_color': _.partial(this.changeColorField, 'styles.link.fontColor'),
        'change #mailpoet_field_footer_link_underline': function(event) {
          this.model.set('styles.link.textDecoration', (event.target.checked) ? event.target.value : 'none');
        },
        'change .mailpoet_field_footer_background_color': _.partial(this.changeColorField, 'styles.block.backgroundColor'),
        'change .mailpoet_field_footer_alignment': _.partial(this.changeField, 'styles.text.textAlign'),
        'click .mailpoet_done_editing': 'close'
      };
    },
    templateContext: function() {
      return _.extend({}, base.BlockView.prototype.templateContext.apply(this, arguments), {
        availableStyles: App.getAvailableStyles().toJSON()
      });
    }
  });

  Module.FooterWidgetView = base.WidgetView.extend({
    getTemplate: function() { return templates.footerInsertion; },
    behaviors: {
      DraggableBehavior: {
        cloneOriginal: true,
        drop: function() {
          return new Module.FooterBlockModel();
        }
      }
    }
  });

  App.on('before:start', function(App, options) {
    App.registerBlockType('footer', {
      blockModel: Module.FooterBlockModel,
      blockView: Module.FooterBlockView
    });

    App.registerWidget({
      name: 'footer',
      widgetView: Module.FooterWidgetView,
      priority: 99
    });
  });

  return Module;
});
