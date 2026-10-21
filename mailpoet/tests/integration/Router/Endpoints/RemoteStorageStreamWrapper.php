<?php declare(strict_types = 1);

namespace MailPoet\Test\Router\Endpoints;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- PHP calls stream wrapper methods by these exact names.

/**
 * In-memory stream wrapper that mimics remote uploads storage such as vip://.
 * Folders always exist, and every write is recorded.
 */
class RemoteStorageStreamWrapper {
  public const PROTOCOL = 'mailpoet-test-remote';

  /** @var array<string, string> */
  public static $files = [];

  /** @var string[] */
  public static $writes = [];

  /** @var bool */
  public static $rejectWrites = false;

  /**
   * Opens for writing, then fails to save on flush, like vip:// does.
   *
   * @var bool
   */
  public static $failSaves = false;

  /** @var resource|null */
  public $context;

  /** @var string */
  private $path = '';

  /** @var string */
  private $buffer = '';

  /** @var int */
  private $position = 0;

  /** @var bool */
  private $isWriting = false;

  public static function register(): void {
    if (!in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
      stream_wrapper_register(self::PROTOCOL, self::class, STREAM_IS_URL);
    }
    self::$files = [];
    self::$writes = [];
    self::$rejectWrites = false;
    self::$failSaves = false;
  }

  public static function unregister(): void {
    if (in_array(self::PROTOCOL, stream_get_wrappers(), true)) {
      stream_wrapper_unregister(self::PROTOCOL);
    }
    self::$files = [];
    self::$writes = [];
    self::$rejectWrites = false;
    self::$failSaves = false;
  }

  public static function getRoot(): string {
    return self::PROTOCOL . '://uploads';
  }

  public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool {
    $this->path = $path;
    $this->position = 0;
    $this->isWriting = strpbrk($mode, 'waxc') !== false;
    if ($this->isWriting && self::$rejectWrites) {
      return false;
    }
    if ($this->isWriting) {
      self::$writes[] = $path;
      $this->buffer = '';
      return true;
    }
    if (!isset(self::$files[$path])) {
      return false;
    }
    $this->buffer = self::$files[$path];
    return true;
  }

  public function stream_write(string $data): int {
    $this->buffer .= $data;
    return strlen($data);
  }

  public function stream_read(int $count): string {
    $chunk = substr($this->buffer, $this->position, $count);
    $this->position += strlen($chunk);
    return $chunk;
  }

  public function stream_eof(): bool {
    return $this->position >= strlen($this->buffer);
  }

  public function stream_flush(): bool {
    if ($this->isWriting && self::$failSaves) {
      return false;
    }
    if ($this->isWriting) {
      self::$files[$this->path] = $this->buffer;
    }
    return true;
  }

  public function stream_close(): void {
    $this->stream_flush();
  }

  /**
   * @return array<string, int>
   */
  public function stream_stat(): array {
    return ['mode' => 0100666, 'size' => strlen($this->buffer)];
  }

  /**
   * @return array<string, int>|false
   */
  public function url_stat(string $path, int $flags) {
    if (isset(self::$files[$path])) {
      return ['mode' => 0100666, 'size' => strlen(self::$files[$path]), 'mtime' => time()];
    }
    if (pathinfo($path, PATHINFO_EXTENSION) === '') {
      return ['mode' => 040777, 'size' => 0, 'mtime' => time()];
    }
    return false;
  }

  public function mkdir(string $path, int $mode, int $options): bool {
    return true;
  }

  public function unlink(string $path): bool {
    unset(self::$files[$path]);
    return true;
  }
}
