<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Replaces strings in a file and fails when nothing matched, so a fix that
 * no longer fits the scoper output can't pass silently.
 *
 * @param string[] $find All spellings of one fix (e.g. double and single backslash variants).
 * @param string|string[] $replace
 */
function replaceInFile(string $file, array $find, $replace): void {
  $data = file_get_contents($file);
  $data = str_replace($find, $replace, $data, $count);
  if ($count === 0) {
    throw new RuntimeException("Prefixer fix matched nothing in $file. Looked for: " . implode(' | ', $find));
  }
  file_put_contents($file, $data);
}
