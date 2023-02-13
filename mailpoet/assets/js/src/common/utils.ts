export const getLinkRegex = () => /\[link\](.*?)\[\/link\]/g;
export const isTruthy = (value: string | number | boolean) =>
  [1, '1', true, 'true'].includes(value);

