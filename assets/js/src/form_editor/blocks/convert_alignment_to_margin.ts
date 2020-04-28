import HorizontalAlignment from 'common/styles';

function convert(alignment: HorizontalAlignment): string {
  if (alignment === HorizontalAlignment.Right) {
    return '0 0 0 auto';
  }
  if (alignment === HorizontalAlignment.Center) {
    return '0 auto';
  }
  return 'auto 0';
}

export default convert;
