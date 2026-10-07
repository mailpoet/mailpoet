<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

class CaptchaAudioBuilder {
  const SAMPLE_RATE = 8000;

  /** @var callable */
  private $random;

  public function __construct(
    ?callable $random = null
  ) {
    $this->random = $random ?? 'random_int';
  }

  public function build(string $phrase, string $clipDir): string {
    $samples = $this->silence($this->rand(200, 600));
    foreach (str_split(strtolower($phrase)) as $character) {
      $samples = array_merge(
        $samples,
        $this->silence($this->rand(80, 350)),
        $this->applyGain(
          $this->resample($this->readClip($clipDir, $character), $this->rand(85, 115) / 100),
          $this->rand(60, 100) / 100
        )
      );
    }
    $samples = array_merge($samples, $this->silence($this->rand(200, 600)));

    $noiseAmplitude = (int)round($this->rand(2, 6) / 100 * 32767);
    $pcm = '';
    foreach (array_chunk($samples, 4096) as $chunk) {
      foreach ($chunk as $i => $sample) {
        $chunk[$i] = max(-32768, min(32767, $sample + $this->rand(-$noiseAmplitude, $noiseAmplitude))) & 0xFFFF;
      }
      $pcm .= pack('v*', ...$chunk);
    }

    return $this->wavHeader(strlen($pcm)) . $pcm;
  }

  private function rand(int $min, int $max): int {
    return (int)($this->random)($min, $max);
  }

  /** @return int[] */
  private function silence(int $milliseconds): array {
    return array_pad([], (int)($milliseconds * self::SAMPLE_RATE / 1000), 0);
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
   * @param int[] $samples
   * @return int[]
   */
  private function resample(array $samples, float $speed): array {
    $count = count($samples);
    $result = [];
    $newCount = (int)floor($count / $speed);
    for ($i = 0; $i < $newCount; $i++) {
      $position = $i * $speed;
      $index = (int)floor($position);
      $fraction = $position - $index;
      $next = $samples[min($index + 1, $count - 1)];
      $result[] = (int)round($samples[$index] + ($next - $samples[$index]) * $fraction);
    }
    return $result;
  }

  /**
   * @param int[] $samples
   * @return int[]
   */
  private function applyGain(array $samples, float $gain): array {
    foreach ($samples as $i => $sample) {
      $samples[$i] = (int)round($sample * $gain);
    }
    return $samples;
  }

  private function wavHeader(int $dataSize): string {
    return 'RIFF' . pack('V', 36 + $dataSize) . 'WAVE'
      . 'fmt ' . pack('VvvVVvv', 16, 1, 1, self::SAMPLE_RATE, self::SAMPLE_RATE * 2, 2, 16)
      . 'data' . pack('V', $dataSize);
  }
}
