export const isEventInsideElement = (event, $el: JQuery): boolean => {
  const offset = $el.offset();
  const height = $el.height();
  const width = $el.width();
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
