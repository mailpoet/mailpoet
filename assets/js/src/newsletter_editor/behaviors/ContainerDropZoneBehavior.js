
/**
 * ContainerDropZoneBehavior
 *
 * A receiving behavior for drag&drop.
 * Allows CollectionView instances that use this behavior to act as drop zones and
 * accept droppables
 */
define([
  'backbone.marionette',
  'underscore',
  'jquery',
  'newsletter_editor/behaviors/BehaviorsLookup',
  'interact'
], function (Marionette, _, jQuery, BL, interact) {
  var BehaviorsLookup = BL;

  BehaviorsLookup.ContainerDropZoneBehavior = Marionette.Behavior.extend({
    defaults: {
      columnLimit: 3
    },
    onRender: function () {
      var dragAndDropDisabled = _.isObject(this.view.options.renderOptions) && this.view.options.renderOptions.disableDragAndDrop === true;
      if (!dragAndDropDisabled) {
        this.addDropZone();
      }
    },
    addDropZone: function () {
      var that = this;
      var view = this.view;
      var domElement = that.$el.get(0);
      var acceptableElementSelector;

      // TODO: Extract this limitation code to be controlled from containers
      if (this.view.renderOptions.depth === 0) {
        // Root level accept. Allow only layouts
        acceptableElementSelector = '.mailpoet_droppable_block.mailpoet_droppable_layout_block';
      } else if (this.view.renderOptions.depth === 2) {
        // Column level accept. Disallow layouts, allow only content blocks
        acceptableElementSelector = '.mailpoet_droppable_block:not(.mailpoet_droppable_layout_block)';
      } else {
        // Layout section container level. Allow nothing.
        return;
      }

      // TODO: Simplify target block identification, remove special insertion support
      interact(domElement).dropzone({
        accept: acceptableElementSelector,
        overlap: 'pointer', // Mouse pointer denotes location of a droppable
        ondragenter: function () {
          // 1. Visually mark block as active for dropping
          view.$el.addClass('mailpoet_drop_active');
        },
        ondragleave: function () {
          // 1. Remove visual markings of active dropping container
          // 2. Remove visual markings of drop position visualization
          that.cleanup();
        },
        ondropmove: function (event) {
          // 1. Compute actual location of the mouse within the container
          // 2. Check if insertion is regular (between blocks) or special (with container insertion)
          // 3a. If insertion is regular, compute position where insertion should happen
          // 3b. If insertion is special, compute position (which side) and which cell the insertion belongs to
          // 4. If insertion at that position is not visualized, display position visualization there, remove other visualizations from this container
          var dropPosition = that.getDropPosition(
            event.dragmove.pageX,
            event.dragmove.pageY,
            view.$el,
            view.model.get('orientation'),
            view.model.get('blocks').length
          );
          var element = view.$el;
          var markerWidth = '';
          var markerHeight = '';
          var containerOffset = element.offset();
          var viewCollection = that.getCollection();
          var marker;
          var targetModel;
          var targetView;
          var targetElement;
          var topOffset;
          var leftOffset;
          var isLastBlockInsertion;
          var $targetBlock;
          var margin;

          if (dropPosition === undefined) return;

          element.find('.mailpoet_drop_marker').remove();

          // Allow empty collections to handle their own drop marking
          if (viewCollection.isEmpty()) return;

          if (viewCollection.length === 0) {
            targetElement = element.find(view.childViewContainer);
            topOffset = targetElement.offset().top - element.offset().top;
            leftOffset = targetElement.offset().left - element.offset().left;
            markerWidth = targetElement.width();
            markerHeight = targetElement.height();
          } else {
            isLastBlockInsertion = that.getCollection().length === dropPosition.index;
            targetModel = isLastBlockInsertion ? viewCollection.at(dropPosition.index - 1) : viewCollection.at(dropPosition.index);

            targetView = that.getChildren().findByModel(targetModel);
            targetElement = targetView.$el;

            topOffset = targetElement.offset().top - containerOffset.top;
            leftOffset = targetElement.offset().left - containerOffset.left;
            if (dropPosition.insertionType === 'normal') {
              if (dropPosition.position === 'after') {
                // Move the marker to the opposite side of the block
                if (view.model.get('orientation') === 'vertical') {
                  topOffset += targetElement.outerHeight(true);
                } else {
                  leftOffset += targetElement.outerWidth();
                }
              }

              if (view.model.get('orientation') === 'vertical') {
                markerWidth = targetElement.outerWidth();
              } else {
                markerHeight = targetElement.outerHeight();
              }
            } else {
              if (dropPosition.position === 'after') {
                // Move the marker to the opposite side of the block
                if (view.model.get('orientation') === 'vertical') {
                  leftOffset += targetElement.outerWidth();
                } else {
                  topOffset += targetElement.outerHeight();
                }
              }

              if (view.model.get('orientation') === 'vertical') {
                markerHeight = targetElement.outerHeight(true);
              } else {
                markerWidth = targetElement.outerWidth(true);
              }
            }
          }

          marker = jQuery('<div class="mailpoet_drop_marker"></div>');
          // Add apropriate CSS classes for position refinement with CSS
          if (dropPosition.index === 0) {
            marker.addClass('mailpoet_drop_marker_first');
          }
          if (viewCollection.length - 1 === dropPosition.index) {
            marker.addClass('mailpoet_drop_marker_last');
          }
          if (dropPosition.index > 0 && viewCollection.length - 1 > dropPosition.index) {
            marker.addClass('mailpoet_drop_marker_middle');
          }
          marker.addClass('mailpoet_drop_marker_' + dropPosition.position);

          // Compute margin (optional for each block) that needs to be
          // compensated for to position marker right in the middle of two
          // blocks
          if (dropPosition.position === 'before') {
            $targetBlock = that.getChildren().findByModel(viewCollection.at(dropPosition.index - 1)).$el;
          } else {
            $targetBlock = that.getChildren().findByModel(viewCollection.at(dropPosition.index)).$el;
          }
          margin = $targetBlock.outerHeight(true) - $targetBlock.outerHeight();

          marker.css('top', topOffset - (margin / 2));
          marker.css('left', leftOffset);
          marker.css('width', markerWidth);
          marker.css('height', markerHeight);

          element.append(marker);
        },
        ondrop: function (event) {
          // 1. Compute actual location of the mouse
          // 2. Check if insertion is regular (between blocks) or special (with container insertion)
          // 3a. If insertion is regular
          //     3a1. compute position where insertion should happen
          //     3a2. insert the drop model there
          // 3b. If insertion is special
          //     3b1. compute position (which side) and which cell the insertion belongs to
          //     3b2. remove element at that position from the collection
          //     3b3. create a new collection, insert the removed element to it
          //     3b4. insert the droppable model at the start or end of the new collection, depending on 3b1. position
          //     3b5. insert the new collection into the old collection to cell from 3b1.
          // 4. Perform cleanup actions

          var dropPosition = that.getDropPosition(
            event.dragEvent.pageX,
            event.dragEvent.pageY,
            view.$el,
            view.model.get('orientation'),
            view.model.get('blocks').length
          );
          var droppableModel = event.draggable.getDropModel();
          var viewCollection = that.getCollection();
          var droppedView;
          var index;
          var tempCollection;
          var tempCollection2;
          var tempModel;

          if (dropPosition === undefined) return;

          if (dropPosition.insertionType === 'normal') {
            // Normal insertion of dropModel into existing collection
            index = (dropPosition.position === 'after') ? dropPosition.index + 1 : dropPosition.index;

            if (view.model.get('orientation') === 'horizontal' && droppableModel.get('type') !== 'container') {
              // Regular blocks always need to be inserted into columns - vertical containers

              tempCollection = new (window.EditorApplication.getBlockTypeModel('container'))({
                orientation: 'vertical'
              });
              tempCollection.get('blocks').add(droppableModel);
              viewCollection.add(tempCollection, { at: index });
            } else {
              viewCollection.add(droppableModel, { at: index });
            }

            droppedView = that.getChildren().findByModel(droppableModel);
          } else {
            // Special insertion by replacing target block with collection
            // and inserting dropModel into that
            tempModel = viewCollection.at(dropPosition.index);

            tempCollection = new (window.EditorApplication.getBlockTypeModel('container'))({
              orientation: (view.model.get('orientation') === 'vertical') ? 'horizontal' : 'vertical'
            });

            viewCollection.remove(tempModel);

            if (tempCollection.get('orientation') === 'horizontal') {
              if (dropPosition.position === 'before') {
                tempCollection2 = new (window.EditorApplication.getBlockTypeModel('container'))({
                  orientation: 'vertical'
                });
                tempCollection2.get('blocks').add(droppableModel);
                tempCollection.get('blocks').add(tempCollection2);
              }
              tempCollection2 = new (window.EditorApplication.getBlockTypeModel('container'))({
                orientation: 'vertical'
              });
              tempCollection2.get('blocks').add(tempModel);
              tempCollection.get('blocks').add(tempCollection2);
              if (dropPosition.position === 'after') {
                tempCollection2 = new (window.EditorApplication.getBlockTypeModel('container'))({
                  orientation: 'vertical'
                });
                tempCollection2.get('blocks').add(droppableModel);
                tempCollection.get('blocks').add(tempCollection2);
              }
            } else {
              if (dropPosition.position === 'before') {
                tempCollection.get('blocks').add(droppableModel);
              }
              tempCollection.get('blocks').add(tempModel);
              if (dropPosition.position === 'after') {
                tempCollection.get('blocks').add(droppableModel);
              }
            }
            viewCollection.add(tempCollection, { at: dropPosition.index });

            // Call post add actions
            droppedView = that.getChildren().findByModel(tempCollection).children.findByModel(droppableModel);
          }

          // Call post add actions
          event.draggable.onDrop({
            dropBehavior: that,
            droppedModel: droppableModel,
            droppedView: droppedView
          });

          that.cleanup();
        }
      });
    },
    cleanup: function () {
      // 1. Remove visual markings of active dropping container
      this.view.$el.removeClass('mailpoet_drop_active');

      // 2. Remove visual markings of drop position visualization
      this.view.$('.mailpoet_drop_marker').remove();
    },
    getDropPosition: function (eventX, eventY, is_unsafe) {
      var SPECIAL_AREA_INSERTION_WIDTH = 0.00; // Disable special insertion. Default: 0.3

      var element = this.view.$el;
      var orientation = this.view.model.get('orientation');

      var elementOffset = element.offset();
      var elementPageX = elementOffset.left;
      var elementPageY = elementOffset.top;
      var elementWidth = element.outerWidth(true);
      var elementHeight = element.outerHeight(true);

      var relativeX = eventX - elementPageX;
      var relativeY = eventY - elementPageY;

      var relativeOffset;
      var elementLength;

      var canAcceptNormalInsertion = this._canAcceptNormalInsertion();
      var canAcceptSpecialInsertion = this._canAcceptSpecialInsertion();

      var insertionType;
      var index;
      var position;
      var indexAndPosition;

      var unsafe = !!is_unsafe;

      if (this.getCollection().length === 0) {
        return {
          insertionType: 'normal',
          index: 0,
          position: 'inside'
        };
      }

      if (orientation === 'vertical') {
        relativeOffset = relativeX;
        elementLength = elementWidth;
      } else {
        relativeOffset = relativeY;
        elementLength = elementHeight;
      }

      if (canAcceptSpecialInsertion && !canAcceptNormalInsertion) {
        // If normal insertion is not available, dedicate whole element area
        // to special insertion
        SPECIAL_AREA_INSERTION_WIDTH = 0.5;
      }

      if (relativeOffset <= elementLength * SPECIAL_AREA_INSERTION_WIDTH && (unsafe || canAcceptSpecialInsertion)) {
        insertionType = 'special';
        position = 'before';
        index = this._computeSpecialIndex(eventX, eventY);
      } else if (relativeOffset > elementLength * (1 - SPECIAL_AREA_INSERTION_WIDTH) && (unsafe || canAcceptSpecialInsertion)) {
        insertionType = 'special';
        position = 'after';
        index = this._computeSpecialIndex(eventX, eventY);
      } else {
        indexAndPosition = this._computeNormalIndex(eventX, eventY);
        insertionType = 'normal';
        position = indexAndPosition.position;
        index = indexAndPosition.index;
      }

      if (!unsafe && orientation === 'vertical' && insertionType === 'special' && this.getCollection().at(index).get('orientation') === 'horizontal') {
        // Prevent placing horizontal container in another horizontal container,
        // which would allow breaking the column limit.
        // Switch that to normal insertion
        indexAndPosition = this._computeNormalIndex(eventX, eventY);
        insertionType = 'normal';
        position = indexAndPosition.position;
        index = indexAndPosition.index;
      }

      if (orientation === 'horizontal' && insertionType === 'special') {
        // Disable special insertion for horizontal containers
        return undefined;
      }

      return {
        insertionType: insertionType, // 'normal'|'special'
        index: index,
        position: position // 'inside'|'before'|'after'
      };
    },
    _computeNormalIndex: function (eventX, eventY) {
      // Normal insertion inserts dropModel before target element if
      // event happens on the first half of the element and after the
      // target element if event happens on the second half of the element.
      // Halves depend on orientation.

      var index = this._computeCellIndex(eventX, eventY);
      // TODO: Handle case when there are no children, container is empty
      var targetView = this.getChildren().findByModel(this.getCollection().at(index));
      var orientation = this.view.model.get('orientation');
      var element = targetView.$el;
      var eventOffset;
      var closeOffset;
      var elementDimension;

      if (orientation === 'vertical') {
        eventOffset = eventY;
        closeOffset = element.offset().top;
        elementDimension = element.outerHeight(true);
      } else {
        eventOffset = eventX;
        closeOffset = element.offset().left;
        elementDimension = element.outerWidth(true);
      }

      if (eventOffset <= closeOffset + elementDimension / 2) {
        // First half of the element
        return {
          index: index,
          position: 'before'
        };
      }
        // Second half of the element
      return {
        index: index,
        position: 'after'
      };

    },
    _computeSpecialIndex: function (eventX, eventY) {
      return this._computeCellIndex(eventX, eventY);
    },
    _computeCellIndex: function (eventX, eventY) {
      var orientation = this.view.model.get('orientation');
      var eventOffset = (orientation === 'vertical') ? eventY : eventX;
      var resultView = this.getChildren().find(function (view) {
        var element = view.$el;
        var closeOffset;
        var farOffset;

        if (orientation === 'vertical') {
          closeOffset = element.offset().top;
          farOffset = element.outerHeight(true);
        } else {
          closeOffset = element.offset().left;
          farOffset = element.outerWidth(true);
        }
        farOffset += closeOffset;

        return closeOffset <= eventOffset && eventOffset <= farOffset;
      });

      var index = (typeof resultView === 'object') ? resultView._index : 0;

      return index;
    },
    _canAcceptNormalInsertion: function () {
      var orientation = this.view.model.get('orientation');
      var depth = this.view.renderOptions.depth;
      var childCount = this.getChildren().length;
      // Note that depth is zero indexed. Root container has depth=0
      return orientation === 'vertical' || (orientation === 'horizontal' && depth === 1 && childCount < this.options.columnLimit);
    },
    _canAcceptSpecialInsertion: function () {
      var orientation = this.view.model.get('orientation');
      var depth = this.view.renderOptions.depth;
      var childCount = this.getChildren().length;
      return depth === 0 || (depth === 1 && orientation === 'horizontal' && childCount <= this.options.columnLimit);
    },
    getCollectionView: function () {
      return this.view.getChildView('blocks');
    },
    getChildren: function () {
      return this.getCollectionView().children;
    },
    getCollection: function () {
      return this.getCollectionView().collection;
    }
  });
});
