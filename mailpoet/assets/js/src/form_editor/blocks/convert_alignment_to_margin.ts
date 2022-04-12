import { HorizontalAlignment } from 'common/styles';

export function convertAlignmentToMargin(
  alignment: HorizontalAlignment,
): string {
  if (alignment === HorizontalAlignment.Right) {
    return '0 0 0 auto';
  }
  if (alignment === HorizontalAlignment.Center) {
    return '0 auto';
  }
  return 'auto 0';
}
