export const getLinkRegex = () => /\[link\](.*?)\[\/link\]/g;
export const isTruthy = (value: string | number | boolean) =>
  [1, '1', true, 'true'].includes(value);

export const stopLinkPropagation = (event: React.MouseEvent) => {
  event.stopPropagation();
};

export const capitalizeFirstLetter = (str: string) => {
  const theString = String(str);
  return theString.charAt(0).toUpperCase() + theString.slice(1);
};
