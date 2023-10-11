/**
 * Sort segment filters alphabetically using their label
 * after it is translated.
 */
export function sortFilters(optionA, optionB) {
  const labelA = optionA.label.toLowerCase();
  const labelB = optionB.label.toLowerCase();

  if (labelA < labelB) {
    return -1;
  }
  if (labelA > labelB) {
    return 1;
  }
  return 0;
}
