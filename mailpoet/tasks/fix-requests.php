<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

// throw exception if anything fails
set_error_handler(function ($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});

// Skip when requests library is missing
if (!file_exists(__DIR__ . '/../vendor/rmccue/requests/library/Requests/Cookie/Jar.php')) {
  exit;
}

// Add the attribute #[\ReturnTypeWillChange] for compatibility with PHP8.1
$replacements = [
  [
    'file' => __DIR__ . '/../vendor/rmccue/requests/library/Requests/Cookie/Jar.php',
    'find' => [
      '*/' . PHP_EOL . '	public function offsetExists($key) {',
      '*/' . PHP_EOL . '	public function offsetGet($key) {',
      '*/' . PHP_EOL . '	public function offsetSet($key, $value) {',
      '*/' . PHP_EOL . '	public function offsetUnset($key) {',
      '*/' . PHP_EOL . '	public function getIterator() {',
    ],
    'replace' => [
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function offsetExists($key) {',
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function offsetGet($key) {',
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function offsetSet($key, $value) {',
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function offsetUnset($key) {',
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function getIterator() {',
    ],
  ],
  [
    'file' => __DIR__ . '/../vendor/rmccue/requests/library/Requests/Utility/CaseInsensitiveDictionary.php',
    'find' => [
      '*/' . PHP_EOL . '	public function offsetExists($key) {',
      '*/' . PHP_EOL . '	public function offsetGet($key) {',
      '*/' . PHP_EOL . '	public function offsetSet($key, $value) {',
      '*/' . PHP_EOL . '	public function offsetUnset($key) {',
      '*/' . PHP_EOL . '	public function getIterator() {',
    ],
    'replace' => [
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function offsetExists($key) {',
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function offsetGet($key) {',
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function offsetSet($key, $value) {',
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function offsetUnset($key) {',
      '*/' . PHP_EOL . '	#[\ReturnTypeWillChange]' . PHP_EOL . '	public function getIterator() {',
    ],
  ],
];

// Apply replacements
foreach ($replacements as $singleFile) {
  $data = file_get_contents($singleFile['file']);
  $data = str_replace($singleFile['find'], $singleFile['replace'], $data);
  file_put_contents($singleFile['file'], $data);
}
