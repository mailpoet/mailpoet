<?php declare(strict_types = 1);

namespace MailPoet\Tasks;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Strips comments and redundant whitespace from PHP code using the tokenizer.
 * Newlines are kept (collapsed to one) so that line comments, attributes and heredocs stay valid.
 * Needs PHP 8+ (PhpToken, and PHP 8 tokenization of comments and attributes).
 */
class StripWhitespaces {
  public function __construct(
    int $phpVersionId = PHP_VERSION_ID
  ) {
    if ($phpVersionId < 80000) {
      throw new \RuntimeException('StripWhitespaces requires PHP 8.0 or newer.');
    }
  }

  public function strip(string $code): string {
    $output = '';
    $pending = '';
    foreach (\PhpToken::tokenize($code) as $token) {
      if ($token->id === T_WHITESPACE || $token->id === T_COMMENT || $token->id === T_DOC_COMMENT) {
        if (strpos($token->text, "\n") !== false || strpos($token->text, "\r") !== false) {
          $pending = "\n";
        } elseif ($pending === '') {
          $pending = ' ';
        }
        continue;
      }
      if ($pending !== '' && !$this->endsWithBreak($output, $pending)) {
        $output .= $pending;
      }
      $pending = '';
      $output .= $token->text;
    }
    if ($pending !== '' && !$this->endsWithBreak($output, $pending)) {
      $output .= $pending;
    }
    return $output;
  }

  public function stripFile(string $path): void {
    $code = file_get_contents($path);
    if ($code === false) {
      throw new \RuntimeException("Cannot read $path");
    }
    $result = $this->strip($code);
    if ($result === '' && $code !== '') {
      throw new \RuntimeException("Stripping $path produced an empty file");
    }
    if ($result !== $code) {
      file_put_contents($path, $result);
    }
  }

  public function stripDirectory(string $dir): int {
    $count = 0;
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($files as $file) {
      if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
      }
      $this->stripFile($file->getPathname());
      $count++;
    }
    return $count;
  }

  private function endsWithBreak(string $output, string $pending): bool {
    $last = substr($output, -1);
    return $last === "\n" || ($pending === ' ' && $last === ' ');
  }
}
