/* eslint-disable func-names */
/*
 * name: MailPoet Form Editor
 * author: Jonathan Labreuille
 * company: Wysija
 * framework: prototype 1.7.2
 */
var Observable;
var WysijaHistory;
var WysijaForm;

/* LOGGING */
function info(value) {
  if (WysijaForm.options.debug === false) return;

  if (!(window.console && console.log)) { // eslint-disable-line no-console
    (function () {
      var noop = function () {};
      var methods = [
        'assert',
        'clear',
        'count',
        'debug',
        'dir',
        'dirxml',
        'error',
        'exception',
        'group',
        'groupCollapsed',
        'groupEnd',
        'info',
        'log',
        'markTimeline',
        'profile',
        'profileEnd',
        'markTimeline',
        'table',
        'time',
        'timeEnd',
        'timeStamp',
        'trace',
        'warn',
      ];
      var length = methods.length;
      var console = {};
      window.console = {};
      while (length) {
        length -= 1;
        console[methods[length]] = noop;
      }
    }());
  }
  try {
    console.log('[DEBUG] ' + value); // eslint-disable-line no-console
  } catch (e) {
    // continue regardless of error
  }
}

Event.cacheDelegated = {};
Object.extend(document, (function () {
  var cache = Event.cacheDelegated;

  function getCacheForSelector(selector) {
    cache[selector] = cache[selector] || {};
    return cache[selector];
  }

  function getWrappersForSelector(selector, eventName) {
    var c = getCacheForSelector(selector);
    c[eventName] = c[eventName] || [];
    return c[eventName];
  }

  function findWrapper(selector, eventName, handler) {
    var c = getWrappersForSelector(selector, eventName);
    return c.find(function (wrapper) {
      return wrapper.handler === handler;
    });
  }

  function destroyWrapper(selector, eventName, handler) {
    var wrapper;
    var c = getCacheForSelector(selector);
    if (!c[eventName]) return false;
    wrapper = findWrapper(selector, eventName, handler);
    c[eventName] = c[eventName].without(wrapper);
    return wrapper;
  }

  function createWrapper(selector, eventName, handler, context) {
    var wrapper;
    var element;
    var c = getWrappersForSelector(selector, eventName);
    if (c.pluck('handler').include(handler)) return false;
    wrapper = function (event) {
      element = event.findElement(selector);
      if (element) handler.call(context || element, event, element);
    };
    wrapper.handler = handler;
    c.push(wrapper);
    return wrapper;
  }
  return {
    delegate: function (selector, eventName) {
      var wrapper = createWrapper.apply(null, arguments);
      if (wrapper) document.observe(eventName, wrapper);
      return document;
    },
    stopDelegating: function (selector, eventName) {
      var length = arguments.length;
      var wrapper;
      switch (length) {
        case 2:
          getWrappersForSelector(selector, eventName).each(function (selectorWrapper) {
            document.stopDelegating(selector, eventName, selectorWrapper.handler);
          });
          break;
        case 1:
          Object.keys(getCacheForSelector(selector)).each(function (event) {
            document.stopDelegating(selector, event);
          });
          break;
        case 0:
          Object.keys(cache).each(function (cacheSelector) {
            document.stopDelegating(cacheSelector);
          });
          break;
        default:
          wrapper = destroyWrapper.apply(null, arguments);
          if (wrapper) document.stopObserving(eventName, wrapper);
      }
      return document;
    },
  };
}()));

Observable = (function () {
  function getEventName(nameA, namespace) {
    var name = nameA.substring(2);
    if (namespace) name = namespace + ':' + name;
    return name.underscore().split('_').join(':');
  }

  function getWrapper(handler, Klass) {
    return function (event) {
      return handler.call(new Klass(this), event, event.memo);
    };
  }

  function getHandlers(klass) {
    var proto = klass.prototype;
    var namespace = proto.namespace;
    return Object.keys(proto).grep(/^on/).inject(window.$H(), function (handlers, name) {
      if (name === 'onDomLoaded') return handlers;
      handlers.set(getEventName(name, namespace), getWrapper(proto[name], klass));
      return handlers;
    });
  }

  function onDomLoad(selector, Klass) {
    window.$$(selector).each(function (element) {
      new Klass(element).onDomLoaded();
    });
  }
  return {
    observe: function (selector) {
      var klass = this;
      if (!this.handlers) this.handlers = {};
      if (this.handlers[selector]) return;
      if (this.prototype.onDomLoaded) {
        if (document.loaded) {
          onDomLoad(selector, klass);
        } else {
          document.observe('dom:loaded', onDomLoad.curry(selector, klass));
        }
      }
      this.handlers[selector] = getHandlers(klass).each(function (handler) {
        document.delegate(selector, handler.key, handler.value);
      });
    },
    stopObserving: function (selector) {
      if (!this.handlers || !this.handlers[selector]) return;
      this.handlers[selector].each(function (handler) {
        document.stopDelegating(selector, handler.key, handler.value);
      });
      delete this.handlers[selector];
    },
  };
}());

// override droppables
Object.extend(window.Droppables, {
  deactivate: window.Droppables.deactivate.wrap(function (proceed, drop, draggable) {
    if (drop.onLeave) drop.onLeave(draggable, drop.element);
    return proceed(drop);
  }),
  activate: window.Droppables.activate.wrap(function (proceed, drop, draggable) {
    if (drop.onEnter) drop.onEnter(draggable, drop.element);
    return proceed(drop);
  }),
  show: function (point, element) {
    var drop;
    var affected = [];
    if (!this.drops.length) return;
    this.drops.each(function (dropsDrop) {
      if (window.Droppables.isAffected(point, element, dropsDrop)) affected.push(dropsDrop);
    });
    if (affected.length > 0) drop = window.Droppables.findDeepestChild(affected);
    if (this.last_active && this.last_active !== drop) this.deactivate(this.last_active, element);
    if (drop) {
      window.Position.within(drop.element, point[0], point[1]);
      if (drop.onHover) {
        drop.onHover(element, drop.element, window.Position.overlap(drop.overlap, drop.element));
      }
      if (drop !== this.last_active) window.Droppables.activate(drop, element);
    }
  },
  displayArea: function () {
    if (!this.drops.length) return;

    // hide controls when displaying drop areas.
    WysijaForm.hideBlockControls();

    this.drops.each(function (drop) {
      if (drop.element.hasClassName('block_placeholder')) {
        drop.element.addClassName('active');
      }
    });
  },
  hideArea: function () {
    if (!this.drops.length) return;
    this.drops.each(function (drop) {
      if (drop.element.hasClassName('block_placeholder')) {
        drop.element.removeClassName('active');
      } else if (drop.element.hasClassName('image_placeholder')) {
        drop.element.removeClassName('active');
        drop.element.up().removeClassName('active');
      } else if (drop.element.hasClassName('text_placeholder')) {
        drop.element.removeClassName('active');
      }
    });
  },
  reset: function (draggable) {
    if (this.last_active) this.deactivate(this.last_active, draggable);
  },
});

/*
    Wysija History handling
    POTENTIAL FEATURES:
        - set a maximum number of items to be stored

*/
WysijaHistory = {
  container: 'mailpoet_form_history',
  size: 30,
  enqueue: function (element) {
    // create deep clone (includes child elements) of passed element
    var clone = element.clone(true);

    // check if the field is unique
    if (parseInt(clone.readAttribute('wysija_unique'), 10) === 1) {
      // check if the field is already in the queue
      window.PJS$(WysijaHistory.container).select('[wysija_name="' + clone.readAttribute('wysija_name') + '"]').invoke('remove');
    }

    // check history size
    if (window.PJS$(WysijaHistory.container).select('> div').length >= WysijaHistory.size) {
      // remove oldest element (last in the list)
      window.PJS$(WysijaHistory.container).select('> div').last().remove();
    }

    // store block in history
    window.PJS$(WysijaHistory.container).insert({
      top: clone,
    });
  },
  dequeue: function () {
    // pop last block off the history
    var block = window.PJS$(WysijaHistory.container).select('div').first();

    if (block !== undefined) {
      // insert block back into the editor
      window.PJS$(WysijaForm.options.body).insert({
        top: block,
      });
    }
  },
  clear: function () {
    window.PJS$(WysijaHistory.container).innerHTML = '';
  },
  remove: function (field) {
    window.PJS$(WysijaHistory.container).select('[wysija_name="' + field + '"]').invoke('remove');
  },
};

/* MailPoet Form */
WysijaForm = {
  version: '0.7',
  options: {
    container: 'mailpoet_form_container',
    editor: 'mailpoet_form_editor',
    body: 'mailpoet_form_body',
    toolbar: 'mailpoet_form_toolbar',
    templates: 'wysija_widget_templates',
    debug: false,
  },
  toolbar: {
    effect: null,
    x: null,
    y: null,
    top: null,
    left: null,
  },
  scroll: {
    top: 0,
    left: 0,
  },
  flags: {
    doSave: false,
  },
  locks: {
    dragging: false,
    selectingColor: false,
    showingTools: false,
  },
  encodeHtmlValue: function (str) {
    return str.replace(/&/g, '&amp;').replace(/>/g, '&gt;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    // ": fix for FileMerge because the previous line fucks up its syntax coloring
  },
  decodeHtmlValue: function (str) {
    return str.replace(/&amp;/g, '&').replace(/&gt;/g, '>').replace(/&lt;/g, '<').replace(/&quot;/g, '"');
    // ": fix for FileMerge because the previous line fucks up its syntax coloring
  },
  loading: function (isLoading) {
    if (isLoading) {
      window.PJS$(WysijaForm.options.editor).addClassName('loading');
      window.PJS$(WysijaForm.options.toolbar).addClassName('loading');
    } else {
      window.PJS$(WysijaForm.options.editor).removeClassName('loading');
      window.PJS$(WysijaForm.options.toolbar).removeClassName('loading');
    }
  },
  loadStatic: function (blocks) {
    window.$A(blocks).each(function (block) {
      // create block
      WysijaForm.Block.create(block, window.PJS$('block_placeholder'));
    });
  },
  load: function (data) {
    var settingsElements;
    if (data === undefined) return;

    // load body
    if (data.body !== undefined) {
      window.$A(data.body).each(function (block) {
        // create block
        WysijaForm.Block.create(block, window.PJS$('block_placeholder'));
      });

      // load settings
      settingsElements = window.PJS$('mailpoet_form_settings').getElements();
      settingsElements.each(function (setting) {
        // skip lists
        if (setting.name === 'segments') {
          return true;
        }
        if (setting.name === 'on_success') {
          // if the input value is equal to the one stored in the settings
          if (setting.value === data.settings[setting.name]) {
            // check selected value
            window.PJS$(setting).checked = true;
          }
        } else if (data.settings[setting.name] !== undefined) {
          if (typeof data.settings[setting.name] === 'string') {
            setting.setValue(WysijaForm.decodeHtmlValue(data.settings[setting.name]));
          } else {
            setting.setValue(data.settings[setting.name]);
          }
        }
        return true;
      });
    }
  },
  save: function () {
    var position = 1;
    var styles = null;
    var data;
    if (window.MailPoet.CodeEditor !== undefined) {
      styles = window.MailPoet.CodeEditor.getValue();
    }
    data = {
      name: window.$F('mailpoet_form_name'),
      settings: window.PJS$('mailpoet_form_settings').serialize(true),
      body: [],
      styles: styles,
    };
    // body
    WysijaForm.getBlocks().each(function (b) {
      var blockData = (typeof (b.block.save) === 'function') ? b.block.save() : null;

      if (blockData !== null) {
        // set block position
        blockData.position = position;

        // increment position
        position += 1;

        // add block data to body
        data.body.push(blockData);
      }
    });

    return data;
  },
  init: function () {
    // set document scroll
    info('init -> set scroll offsets');
    WysijaForm.setScrollOffsets();

    // position toolbar
    info('init -> set toolbar position');
    WysijaForm.setToolbarPosition();

    // enable droppable targets
    info('init -> make droppable');
    WysijaForm.makeDroppable();

    // enable sortable
    info('init -> make sortable');
    WysijaForm.makeSortable();

    // hide controls
    info('init -> hide controls');
    WysijaForm.hideControls();

    // hide settings
    info('init -> hide settings');
    WysijaForm.hideSettings();

    // set settings buttons position
    info('init -> init settings');
    WysijaForm.setSettingsPosition();

    // toggle widgets
    info('init -> toggle widgets');
    WysijaForm.toggleWidgets();
  },
  getFieldData: function (element) {
    // get basic field data
    var data = {
      type: element.readAttribute('wysija_type'),
      name: element.readAttribute('wysija_name'),
      id: element.readAttribute('wysija_id'),
      unique: parseInt(element.readAttribute('wysija_unique') || 0, 10),
      static: parseInt(element.readAttribute('wysija_static') || 0, 10),
      element: element,
      params: '',
    };

    // get params (may be empty)
    if (element.readAttribute('wysija_params') !== null && element.readAttribute('wysija_params').length > 0) {
      data.params = JSON.parse(element.readAttribute('wysija_params'));
    }
    return data;
  },
  toggleWidgets: function () {
    var hasSegmentSelection;
    window.$$('a[wysija_unique="1"]').invoke('removeClassName', 'disabled');

    // loop through each unique field already inserted in the editor
    // and disable its toolbar equivalent
    window.$$('#' + WysijaForm.options.editor + ' [wysija_unique="1"]').forEach(function (element) {
      var field = window.$$(
        '#' + WysijaForm.options.toolbar + ' [wysija_id="' + element.readAttribute('wysija_id') + '"]'
      );
      if (field.length > 0) {
        field.first().addClassName('disabled');
      }
    });

    hasSegmentSelection = WysijaForm.hasSegmentSelection();

    if (hasSegmentSelection) {
      window.PJS$('mailpoet_form_segments').writeAttribute('required', false).disable();
      window.PJS$('mailpoet_settings_segment_selection').hide();
    } else {
      window.PJS$('mailpoet_form_segments').writeAttribute('required', true).enable();
      window.PJS$('mailpoet_settings_segment_selection').show();
    }
  },
  hasSegmentSelection: function () {
    return (window.$$('#' + WysijaForm.options.editor + ' [wysija_id="segments"]').length > 0);
  },
  isSegmentSelectionValid: function () {
    var segmentSelection = window.$$('#' + WysijaForm.options.editor + ' [wysija_id="segments"]')[0];
    var block;
    if (segmentSelection !== undefined) {
      block = WysijaForm.get(segmentSelection).block.getData();
      return (
        (block.params.values !== undefined)
        && (block.params.values.length > 0)
      );
    }
    return false;
  },
  setBlockPositions: function (event, target) {
    var index = 1;
    var blockPlaceholder;
    var previousPlaceholder;
    // release dragging lock
    WysijaForm.locks.dragging = false;

    WysijaForm.getBlocks().each(function (container) {
      container.setPosition(index);
      index += 1;
      // remove z-index value to avoid issues when resizing images
      if (container.block !== undefined) {
        container.block.element.setStyle({
          zIndex: '',
        });
      }
    });

    if (target !== undefined) {
      // get placeholders (previous placeholder matches the placeholder linked to the next block)
      blockPlaceholder = window.PJS$(target.element.readAttribute('wysija_placeholder'));
      previousPlaceholder = target.element.previous('.block_placeholder');

      if (blockPlaceholder !== null) {
        // put block placeholder before the current block
        target.element.insert({
          before: blockPlaceholder,
        });

        // if the next block is a wysija_block, insert previous placeholder
        if (target.element.next() !== undefined && target.element.next().hasClassName('mailpoet_form_block') && previousPlaceholder !== undefined) {
          target.element.insert({
            after: previousPlaceholder,
          });
        }
      }
    }
  },
  setScrollOffsets: function () {
    WysijaForm.scroll = document.viewport.getScrollOffsets();
  },
  hideSettings: function () {
    window.PJS$(WysijaForm.options.container).select('.wysija_settings').invoke('hide');
  },
  setSettingsPosition: function () {
    // get viewport offsets and dimensions
    var viewportHeight = document.viewport.getHeight();

    window.PJS$(WysijaForm.options.container).select('.wysija_settings').each(function (element) {
      // get parent dimensions and position
      var parentDim = element.up('.mailpoet_form_block').getDimensions();
      var parentPos = element.up('.mailpoet_form_block').cumulativeOffset();
      var isVisible = (parentPos.top <= (WysijaForm.scroll.top + viewportHeight));
      var buttonMargin = 5;
      var relativeTop = buttonMargin;

      if (isVisible) {
        // always center
        relativeTop = parseInt((parentDim.height / 2) - (element.getHeight() / 2), 10);
      }
      // set position for button
      window.PJS$(element).setStyle({
        left: parseInt((parentDim.width / 2) - (element.getWidth() / 2), 10) + 'px',
        top: relativeTop + 'px',
      });
    });
  },
  initToolbarPosition: function () {
    if (WysijaForm.toolbar.top === null) {
      WysijaForm.toolbar.top = parseInt(
        window.PJS$(WysijaForm.options.container).positionedOffset().top, 10
      );
    }
    if (WysijaForm.toolbar.y === null) {
      WysijaForm.toolbar.y = parseInt(WysijaForm.toolbar.top, 10);
    }

    if (window.isRtl) {
      if (WysijaForm.toolbar.left === null) WysijaForm.toolbar.left = 0;
    } else if (WysijaForm.toolbar.left === null) {
      WysijaForm.toolbar.left = parseInt(
        window.PJS$(WysijaForm.options.container).positionedOffset().left, 10
      );
    }
    if (WysijaForm.toolbar.x === null) {
      WysijaForm.toolbar.x = parseInt(
        WysijaForm.toolbar.left
          + window.PJS$(WysijaForm.options.container).getDimensions().width
          + 15,
        10
      );
    }
  },
  setToolbarPosition: function () {
    var position;
    WysijaForm.initToolbarPosition();

    position = {
      top: WysijaForm.toolbar.y + 'px',
      visibility: 'visible',
    };

    if (window.isRtl) {
      position.right = WysijaForm.toolbar.x + 'px';
    } else {
      position.left = WysijaForm.toolbar.x + 'px';
    }

    window.PJS$(WysijaForm.options.toolbar).setStyle(position);
  },
  updateToolbarPosition: function () {
    // init toolbar position (updates scroll and toolbar y)
    WysijaForm.initToolbarPosition();

    // cancel previous effect
    if (WysijaForm.toolbar.effect !== null) WysijaForm.toolbar.effect.cancel();

    if (WysijaForm.scroll.top >= (WysijaForm.toolbar.top - 20)) {
      WysijaForm.toolbar.y = parseInt(20 + WysijaForm.scroll.top, 10);
      // start effect
      WysijaForm.toolbar.effect = new window.Effect.Move(WysijaForm.options.toolbar, {
        x: WysijaForm.toolbar.x,
        y: WysijaForm.toolbar.y,
        mode: 'absolute',
        duration: 0.2,
      });
    } else {
      window.PJS$(WysijaForm.options.toolbar).setStyle({
        left: WysijaForm.toolbar.x + 'px',
        top: WysijaForm.toolbar.top + 'px',
      });
    }
  },
  blockDropOptions: {
    accept: window.$w('mailpoet_form_field'), // acceptable items (classes array)
    onEnter: function (draggable, droppable) {
      window.PJS$(droppable).addClassName('hover');
    },
    onLeave: function (draggable, droppable) {
      window.PJS$(droppable).removeClassName('hover');
    },
    onDrop: function (draggable, droppable) {
      // custom data for images
      droppable.fire('wjfe:item:drop', WysijaForm.getFieldData(draggable));
      window.PJS$(droppable).removeClassName('hover');
    },
  },
  hideControls: function () {
    try {
      WysijaForm.getBlocks().invoke('hideControls');
    } catch (e) {
      // continue regardless of error
    }
  },
  hideTools: function () {
    window.$$('.wysija_tools').invoke('hide');
    WysijaForm.locks.showingTools = false;
  },
  instances: {},
  get: function (element, typ) {
    var type = typ;
    var id;
    var instance;
    if (type === undefined) type = 'block';
    // identify element
    id = element.identify();
    instance = WysijaForm.instances[id] || new (WysijaForm[type.capitalize().camelize()])(id);

    WysijaForm.instances[id] = instance;
    return instance;
  },
  makeDroppable: function () {
    window.Droppables.add('block_placeholder', WysijaForm.blockDropOptions);
  },
  makeSortable: function () {
    var body = window.PJS$(WysijaForm.options.body);
    window.Sortable.create(body, {
      tag: 'div',
      only: 'mailpoet_form_block',
      scroll: window,
      handle: 'handle',
      constraint: 'vertical',

    });
    window.Draggables.removeObserver(body);
    window.Draggables.addObserver({
      element: body,
      onStart: WysijaForm.startBlockPositions,
      onEnd: WysijaForm.setBlockPositions,
    });
  },
  hideBlockControls: function () {
    window.$$('.wysija_controls').invoke('hide');
    this.getBlockElements().invoke('removeClassName', 'hover');
  },
  getBlocks: function () {
    return WysijaForm.getBlockElements().map(function (element) {
      return WysijaForm.get(element);
    });
  },
  getBlockElements: function () {
    return window.PJS$(WysijaForm.options.container).select('.mailpoet_form_block');
  },
  startBlockPositions: function (event, target) {
    if (target.element.hasClassName('mailpoet_form_block')) {
      // store block placeholder id for the block that is being repositionned
      if (target.element.previous('.block_placeholder') !== undefined) {
        target.element.writeAttribute('wysija_placeholder', target.element.previous('.block_placeholder').identify());
      }
    }
    WysijaForm.locks.dragging = true;
  },
  encodeURIComponent: function (str) {
    // check if it's a url and if so, prevent encoding of protocol
    var regexp = new RegExp(/^http[s]?:\/\//);
    var protocol = regexp.exec(str);

    if (protocol === null) {
      // this is not a url so encode the whole thing
      return encodeURIComponent(str).replace(/[!'()*]/g, escape);
    }
    if (protocol.length === 1) {
      // this is a url, so do not encode the protocol
      return encodeURI(str).replace(/[!'()*]/g, escape);
    }
    return str;
  },
  updateBlock: function (field) {
    var hasUpdated = false;
    WysijaForm.getBlocks().each(function (b) {
      if (b.block.getData().id === field.id) {
        hasUpdated = true;
        b.block.redraw(field);
      }
    });

    return hasUpdated;
  },
  removeBlock: function (field, callback) {
    var hasRemoved = false;
    WysijaForm.getBlocks().each(function (b) {
      if (b.block.getData().id === field.id) {
        hasRemoved = true;
        b.block.removeBlock(callback);
      }
    });

    return hasRemoved;
  },
};

WysijaForm.DraggableItem = window.Class.create({
  initialize: function (element) {
    this.elementType = window.PJS$(element).readAttribute('wysija_type');
    this.element = window.PJS$(element).down() || window.PJS$(element);
    this.clone = this.cloneElement();
    this.insert();
  },
  STYLES: new window.Template('position: absolute; top: #{top}px; left: #{left}px;'),
  cloneElement: function () {
    var clone = this.element.clone();
    var offset = this.element.cumulativeOffset();
    var list = this.getList();
    var styles = this.STYLES.evaluate({
      top: offset.top - list.scrollTop,
      left: offset.left - list.scrollLeft,
    });
    clone.setStyle(styles);

    clone.addClassName('mailpoet_form_widget');
    clone.addClassName(this.elementType);
    clone.innerHTML = this.element.innerHTML;
    return clone;
  },
  getOffset: function () {
    return this.element.offsetTop - this.getList().scrollTop;
  },
  getList: function () {
    return this.element.up('ul');
  },
  insert: function () {
    window.$$('body')[0].insert(this.clone);
  },
  onMousedown: function (event) {
    var draggable = new window.Draggable(this.clone, {
      scroll: window,
      onStart: function () {
        window.Droppables.displayArea(draggable);
      },
      onEnd: function (drag) {
        drag.destroy();
        drag.element.remove();
        window.Droppables.hideArea();
      },
      starteffect: function (element) {
        new window.Effect.Opacity(element, { // eslint-disable-line no-new
          duration: 0.2,
          from: element.getOpacity(),
          to: 0.7,
        });
      },
      endeffect: window.Prototype.emptyFunction,
    });
    draggable.initDrag(event);
    draggable.startDrag(event);
    return draggable;
  },
});
Object.extend(WysijaForm.DraggableItem, Observable).observe('a[class="mailpoet_form_field"]');


WysijaForm.Block = window.Class.create({
  /* Invoked on load */
  initialize: function (element) {
    info('block -> init');

    this.element = window.PJS$(element);
    this.block = new WysijaForm.Widget(this.element);

    // enable block placeholder
    this.block.makeBlockDroppable();

    // setup events
    if (this.block.setup !== undefined) {
      this.block.setup();
    }
    return this;
  },
  setPosition: function (position) {
    this.element.writeAttribute('wysija_position', position);
  },
  hideControls: function () {
    if (this.getControls) {
      this.element.removeClassName('hover');
      this.getControls().hide();
    }
  },
  showControls: function () {
    if (this.getControls) {
      this.element.addClassName('hover');
      try {
        this.getControls().show();
      } catch (e) {
        // continue regardless of error
      }
    }
  },
  makeBlockDroppable: function () {
    var blockPlaceholder;
    if (this.isBlockDroppableEnabled() === false) {
      blockPlaceholder = this.getBlockDroppable();
      window.Droppables.add(blockPlaceholder.identify(), WysijaForm.blockDropOptions);
      blockPlaceholder.addClassName('enabled');
    }
  },
  removeBlockDroppable: function () {
    var blockPlaceholder;
    if (this.isBlockDroppableEnabled()) {
      blockPlaceholder = this.getBlockDroppable();
      window.Droppables.remove(blockPlaceholder.identify());
      blockPlaceholder.removeClassName('enabled');
    }
  },
  isBlockDroppableEnabled: function () {
    // if the block_placeholder does not exist, create it
    var blockPlaceholder = this.getBlockDroppable();
    if (blockPlaceholder === null) {
      return this.createBlockDroppable().hasClassName('enabled');
    }
    return blockPlaceholder.hasClassName('enabled');
  },
  createBlockDroppable: function () {
    info('block -> createBlockDroppable');
    this.element.insert({
      before: '<div class="block_placeholder">' + window.PJS$('block_placeholder').innerHTML + '</div>',
    });
    return this.element.previous('.block_placeholder');
  },
  getBlockDroppable: function () {
    if (this.element.previous() === undefined || this.element.previous().hasClassName('block_placeholder') === false) {
      return null;
    }
    return this.element.previous();
  },
  getControls: function () {
    return this.element.down('.wysija_controls');
  },
  setupControls: function () {
    var block;
    // enable controls
    this.controls = this.getControls();

    if (this.controls) {
      // setup events for block controls
      this.element.observe('mouseover', function () {
        // special cases where controls shouldn't be displayed
        if (
          WysijaForm.locks.dragging === true
          || WysijaForm.locks.selectingColor === true
          || WysijaForm.locks.showingTools === true
        ) return;

        // set block flag
        this.element.addClassName('hover');

        // show controls
        this.showControls();

        // show settings if present
        if (this.element.down('.wysija_settings') !== undefined) {
          this.element.down('.wysija_settings').show();
        }
      }.bind(this));

      this.element.observe('mouseout', function () {
        // special cases where controls shouldn't hide
        if (WysijaForm.locks.dragging === true || WysijaForm.locks.selectingColor === true) return;

        // hide controls
        this.hideControls();

        // hide settings if present
        if (this.element.down('.wysija_settings') !== undefined) {
          this.element.down('.wysija_settings').hide();
        }
      }.bind(this));


      // setup click event for remove button
      this.removeButton = this.controls.down('.remove') || null;
      if (this.removeButton !== null) {
        this.removeButton.observe('click', function () {
          this.removeBlock();
          this.removeButton.stopObserving('click');
        }.bind(this));
      }

      // setup click event for settings button
      this.settingsButton = this.element.down('.settings') || null;

      if (this.settingsButton !== null) {
        this.settingsButton.observe('click', function (event) {
          // TODO: refactor
          block = window.PJS$(event.target).up('.mailpoet_form_block') || null;
          if (block !== null) {
            this.editSettings();
          }
        }.bind(this));
      }
    }
    return this;
  },
  removeBlock: function (callback) {
    info('block -> removeBlock');

    // save block in history
    WysijaHistory.enqueue(this.element);

    window.Effect.Fade(this.element.identify(), {
      duration: 0.2,
      afterFinish: function (effect) {
        // remove placeholder
        if (effect.element.previous('.block_placeholder') !== undefined) {
          effect.element.previous('.block_placeholder').remove();
        }

        // remove element from the DOM
        this.element.remove();

        // reset block positions
        WysijaForm.setBlockPositions();

        // toggle widgets
        WysijaForm.toggleWidgets();

        // optional callback execution after completely removing block
        if (callback !== undefined && typeof (callback) === 'function') {
          callback();
        }

        // remove block instance
        delete WysijaForm.instances[this.element.identify()];
      }.bind(this),
    });
  },
});

/* Invoked on item dropped */
WysijaForm.Block.create = function (createBlock, target) {
  var block = createBlock;
  var body;
  var blockTemplate;
  var template;
  var output;
  var settingsSegments;
  if (window.PJS$('form_template_' + block.type) === null) {
    return false;
  }

  body = window.PJS$(WysijaForm.options.body);
  blockTemplate = window.Handlebars.compile(window.PJS$('form_template_block').innerHTML);
  template = window.Handlebars.compile(window.PJS$('form_template_' + block.type).innerHTML);
  output = '';

  if (block.type === 'segment') {
    if (block.params.values === undefined) {
      settingsSegments = window.jQuery('#mailpoet_form_segments').val();
      if (settingsSegments !== null && settingsSegments.length > 0) {
        block.params.values = window.mailpoet_segments.filter(function (segment) {
          return (settingsSegments.indexOf(segment.id) !== -1);
        });
      }
    }
  }

  // set block template (depending on the block type)
  block.template = template(block);
  output = blockTemplate(block);

  // check if the new block is unique and if there's already an instance
  // of it in the history. If so, remove its former instance from the history
  if (block.unique === 1) {
    WysijaHistory.remove(block.field);
  }

  // if the drop target was the bottom placeholder
  if (target.identify() === 'block_placeholder') {
    // insert block at the bottom
    body.insert(output);
    // block = body.childElements().last();
  } else {
    // insert block before the drop target
    target.insert({
      before: output,
    });
    // block = target.previous('.mailpoet_form_block');
  }
  // refresh sortable items
  WysijaForm.makeSortable();

  // refresh block positions
  WysijaForm.setBlockPositions();

  // position settings
  WysijaForm.setSettingsPosition();
  return true;
};

document.observe('wjfe:item:drop', function (event) {
  info('create block');
  WysijaForm.Block.create(event.memo, event.target);

  // hide block controls
  info('hide controls');
  WysijaForm.hideBlockControls();

  // toggle widgets
  setTimeout(function () {
    WysijaForm.toggleWidgets();
  }, 1);
});

/* Form Widget */
WysijaForm.Widget = window.Class.create(WysijaForm.Block, {
  initialize: function (element) {
    info('widget -> init');
    this.element = window.PJS$(element);
    return this;
  },
  setup: function () {
    info('widget -> setup');
    this.setupControls();
  },
  save: function () {
    var data = this.getData();
    info('widget -> save');

    if (data.element !== undefined) {
      delete data.element;
    }

    return data;
  },
  setData: function (data) {
    var currentData = this.getData();
    var params = window.$H(currentData.params).merge(data.params).toObject();

    // update type if it changed
    if (data.type !== undefined && data.type !== currentData.type) {
      this.element.writeAttribute('wysija_type', data.type);
    }

    // update params
    this.element.writeAttribute('wysija_params', JSON.stringify(params));
  },
  getData: function () {
    var data = WysijaForm.getFieldData(this.element);
    // decode params
    if (data.params.length > 0) {
      data.params = JSON.parse(data.params);
    }
    return data;
  },
  getControls: function () {
    return this.element.down('.wysija_controls');
  },
  remove: function () {
    this.removeBlock();
  },
  redraw: function (data) {
    var options;
    var blockTemplate;
    var template;
    var params;
    // set parameters
    this.setData(data);
    options = this.getData();
    // redraw block
    blockTemplate = window.Handlebars.compile(window.PJS$('form_template_block').innerHTML);
    template = window.Handlebars.compile(window.PJS$('form_template_' + options.type).innerHTML);
    params = window.$H(options).merge({
      template: template(options),
    }).toObject();
    this.element.replace(blockTemplate(params));

    WysijaForm.init();
  },
  editSettings: function () {
    window.MailPoet.Modal.popup({
      title: window.MailPoet.I18n.t('editFieldSettings'),
      template: window.jQuery('#form_template_field_settings').html(),
      data: this.getData(),
      minWidth: '500px',
      onSuccess: function () {
        var data = window.jQuery('#form_field_settings').mailpoetSerializeObject();
        this.redraw(data);
      }.bind(this),
    });
  },
  getSettings: function () {
    return this.element.down('.wysija_settings');
  },
});

/* When dom is loaded, initialize WysijaForm */
document.observe('dom:loaded', WysijaForm.init);

module.exports = WysijaForm;
