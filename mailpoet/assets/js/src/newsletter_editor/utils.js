export const isEventInsideElement = (event, $el) => {
  var offset = $el.offset();
  var height = $el.height();
  var width = $el.width();
  if (
    event.pageX < offset.left ||
    event.pageX > offset.left + width ||
    event.pageY < offset.top ||
    event.pageY > offset.top + height
  ) {
    return false;
  }
  return true;
};
