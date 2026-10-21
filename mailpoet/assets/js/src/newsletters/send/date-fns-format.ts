// Convert moment format to date-fns, see: https://git.io/fxCyr
const tokenReplacements: Record<string, string> = {
  D: 'd',
  Y: 'y',
  A: 'a',
};

// MailPoet.Date.convertFormat converts the PHP 'S' suffix to the moment 'o'
// ordinal. date-fns supports it only as the "do" day of month token.
const convertOrdinal = (result: string): string =>
  result.endsWith('d') && !result.endsWith('dd') ? 'o' : '';

// date-fns literal text goes in single quotes, a quote inside is doubled
const quoteLiteral = (literal: string): string =>
  literal ? `'${literal.split("'").join("''")}'` : '';

export const momentToDateFnsFormat = (momentFormat: string): string => {
  let result = '';
  let literal = '';
  let index = 0;
  while (index < momentFormat.length) {
    const char = momentFormat[index];
    // Moment literal text is wrapped in brackets, e.g. "[d][e]". Searching
    // for "]" from index + 2 keeps a bracketed "]" ("[]]") as literal text.
    const closingIndex =
      char === '[' ? momentFormat.indexOf(']', index + 2) : -1;
    if (closingIndex !== -1) {
      literal += momentFormat.slice(index + 1, closingIndex);
      index = closingIndex + 1;
    } else {
      result += quoteLiteral(literal);
      literal = '';
      result +=
        char === 'o' ? convertOrdinal(result) : tokenReplacements[char] ?? char;
      index += 1;
    }
  }
  return result + quoteLiteral(literal);
};
