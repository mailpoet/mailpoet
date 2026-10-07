<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\WP\Functions as WPFunctions;

class CaptchaAudioBuilder {
  const SAMPLE_RATE = 8000;
  const CHUNK_SIZE = 4096;

  private WPFunctions $wp;

  /** @var callable|null */
  private $random;

  private string $seedKey = '';

  private int $counter = 0;

  /** @var int[] */
  private array $draws = [];

  public function __construct(
    WPFunctions $wp,
    ?callable $random = null
  ) {
    $this->wp = $wp;
    $this->random = $random;
  }

  /**
   * The same phrase in the same session always produces the same audio.
   */
  public function build(string $phrase, string $clipDir, string $sessionId): string {
    $this->seed($sessionId . '|' . $phrase);

    $samples = [];
    $this->appendSilence($samples, $this->rand(200, 600));
    foreach (str_split(strtolower($phrase)) as $character) {
      $this->appendSilence($samples, $this->rand(80, 350));
      $this->appendClip(
        $samples,
        $this->readClip($clipDir, $character),
        $this->rand(85, 115) / 100,
        $this->rand(60, 100) / 100
      );
    }
    $this->appendSilence($samples, $this->rand(200, 600));

    $noiseAmplitude = (int)round($this->rand(2, 6) / 100 * 32767);
    $pcm = '';
    foreach (array_chunk($samples, self::CHUNK_SIZE) as $chunk) {
      foreach ($chunk as $i => $sample) {
        $value = $sample + $this->rand(-$noiseAmplitude, $noiseAmplitude);
        $chunk[$i] = ($value > 32767 ? 32767 : ($value < -32768 ? -32768 : $value)) & 0xFFFF;
      }
      $pcm .= pack('v*', ...$chunk);
    }

    return $this->wavHeader(strlen($pcm)) . $pcm;
  }

  private function seed(string $input): void {
    $this->seedKey = hash_hmac('sha256', $input, (string)$this->wp->wpSalt('nonce'));
    $this->counter = 0;
    $this->draws = [];
  }

  /**
   * Refills the pool with 16-bit values taken from a SHA-256 stream, so results do not depend on integer size.
   */
  private function refillDraws(): void {
    $bytes = '';
    for ($i = 0; $i < 16; $i++) {
      $bytes .= hash('sha256', $this->seedKey . '|' . $this->counter++, true);
    }
    $this->draws = unpack('n*', $bytes) ?: [0];
  }

  /**
   * Ranges are at most 65536 values wide. Rejection sampling avoids modulo bias.
   */
  private function rand(int $min, int $max): int {
    if ($this->random !== null) {
      return (int)($this->random)($min, $max);
    }
    $span = $max - $min + 1;
    $limit = 65536 - 65536 % $span;
    do {
      if (!$this->draws) {
        $this->refillDraws();
      }
      $draw = (int)array_pop($this->draws);
    } while ($draw >= $limit);
    return $min + $draw % $span;
  }

  /** @param int[] $samples */
  private function appendSilence(array &$samples, int $milliseconds): void {
    $count = (int)($milliseconds * self::SAMPLE_RATE / 1000);
    for ($i = 0; $i < $count; $i++) {
      $samples[] = 0;
    }
  }

  /** @return int[] */
  private function readClip(string $clipDir, string $character): array {
    $file = $clipDir . '/' . $character . '.wav';
    $contents = is_file($file) ? file_get_contents($file) : false;
    if ($contents === false) {
      throw new \RuntimeException('File not found.');
    }

    // Walk the RIFF chunks to the data chunk, as the header length varies.
    $offset = 12;
    $length = strlen($contents);
    while ($offset + 8 <= $length) {
      $chunk = unpack('a4id/Vsize', $contents, $offset);
      if ($chunk === false) {
        break;
      }
      if ($chunk['id'] === 'data') {
        $pcm = (string)substr($contents, $offset + 8, $chunk['size']);
        $pcm = substr($pcm, 0, strlen($pcm) - strlen($pcm) % 2);
        $values = $pcm === '' ? [] : array_values(unpack('s*', $pcm) ?: []);
        return $values;
      }
      $offset += 8 + $chunk['size'] + ($chunk['size'] % 2);
    }
    throw new \RuntimeException('Invalid audio file.');
  }

  /**
   * Appends the clip resampled to the given speed and scaled by the gain.
   *
   * @param int[] $samples
   * @param int[] $clip
   */
  private function appendClip(array &$samples, array $clip, float $speed, float $gain): void {
    $count = count($clip);
    $newCount = (int)floor($count / $speed);
    for ($i = 0; $i < $newCount; $i++) {
      $position = $i * $speed;
      $index = (int)floor($position);
      $fraction = $position - $index;
      $next = $clip[min($index + 1, $count - 1)];
      $resampled = (int)round($clip[$index] + ($next - $clip[$index]) * $fraction);
      $samples[] = (int)round($resampled * $gain);
    }
  }

  private function wavHeader(int $dataSize): string {
    return 'RIFF' . pack('V', 36 + $dataSize) . 'WAVE'
      . 'fmt ' . pack('VvvVVvv', 16, 1, 1, self::SAMPLE_RATE, self::SAMPLE_RATE * 2, 2, 16)
      . 'data' . pack('V', $dataSize);
  }
}
