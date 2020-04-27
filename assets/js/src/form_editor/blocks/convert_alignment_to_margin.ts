
function convert(alignment: 'left' | 'center' | 'right'): string {
  if (alignment === 'right') {
    return '0 0 0 auto';
  }
  if (alignment === 'center') {
    return '0 auto';
  }
  return 'auto 0';
}

export default convert;
