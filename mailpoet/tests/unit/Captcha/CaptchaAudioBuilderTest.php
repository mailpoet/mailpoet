<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

use MailPoet\WP\Functions as WPFunctions;

class CaptchaAudioBuilderTest extends \MailPoetUnitTest {
  const CLIP_SAMPLES = 400;

  private string $clipDir;

  public function _before() {
    parent::_before();
    $this->clipDir = sys_get_temp_dir() . '/mailpoet-captcha-audio-' . uniqid('', true);
    mkdir($this->clipDir);
    $ramp = [];
    for ($i = 0; $i < self::CLIP_SAMPLES; $i++) {
      $ramp[] = ($i * 150) % 20000 - 10000;
    }
    // "a" has an extra LIST chunk before the data chunk, "b" has none
    file_put_contents($this->clipDir . '/a.wav', $this->makeWav($ramp, true));
    // "d" has the same audio as "a", so only the seed can tell their phrases apart
    file_put_contents($this->clipDir . '/d.wav', $this->makeWav($ramp, true));
    file_put_contents($this->clipDir . '/b.wav', $this->makeWav(array_reverse($ramp), false));
    file_put_contents($this->clipDir . '/c.wav', $this->makeWav(array_map(function (int $i): int {
      return $i % 2 ? 32767 : -32768;
    }, range(0, self::CLIP_SAMPLES - 1)), false));
  }

  public function _after() {
    foreach (glob($this->clipDir . '/*') ?: [] as $file) {
      unlink($file);
    }
    rmdir($this->clipDir);
    parent::_after();
  }

  public function testItBuildsValidWavHeader(): void {
    $wav = $this->builder($this->sequence(3))->build('ab', $this->clipDir, 'session');

    $this->assertSame('RIFF', substr($wav, 0, 4));
    $this->assertSame('WAVE', substr($wav, 8, 4));
    $header = $this->unpackOrFail('VchunkSize/a4wave/a4fmt/VfmtSize/vformat/vchannels/VsampleRate/VbyteRate/vblockAlign/vbits/a4data/VdataSize', substr($wav, 4, 40));
    $this->assertSame(strlen($wav) - 8, $header['chunkSize']);
    $this->assertSame('fmt ', $header['fmt']);
    $this->assertSame(16, $header['fmtSize']);
    $this->assertSame(1, $header['format']);
    $this->assertSame(1, $header['channels']);
    $this->assertSame(8000, $header['sampleRate']);
    $this->assertSame(16000, $header['byteRate']);
    $this->assertSame(2, $header['blockAlign']);
    $this->assertSame(16, $header['bits']);
    $this->assertSame('data', $header['data']);
    $this->assertSame(strlen($wav) - 44, $header['dataSize']);
  }

  public function testItBuildsTheSameAudioForTheSameRandomValues(): void {
    $first = $this->builder($this->sequence(7))->build('ab', $this->clipDir, 'session');
    $second = $this->builder($this->sequence(7))->build('ab', $this->clipDir, 'session');
    $this->assertSame($first, $second);
  }

  public function testItBuildsDifferentAudioForDifferentRandomValues(): void {
    $first = $this->builder($this->sequence(7))->build('ab', $this->clipDir, 'session');
    $second = $this->builder($this->sequence(11))->build('ab', $this->clipDir, 'session');
    $this->assertNotSame($first, $second);
  }

  public function testItBuildsTheSameAudioForTheSamePhraseAndSession(): void {
    $builder = $this->builder();
    $first = $builder->build('ab', $this->clipDir, 'session');
    $this->assertSame($first, $builder->build('ab', $this->clipDir, 'session'));
    $this->assertSame($first, $this->builder()->build('ab', $this->clipDir, 'session'));
  }

  public function testItBuildsDifferentAudioForDifferentSessions(): void {
    $builder = $this->builder();
    $this->assertNotSame(
      $builder->build('ab', $this->clipDir, 'session-one'),
      $builder->build('ab', $this->clipDir, 'session-two')
    );
  }

  public function testItBuildsDifferentAudioForDifferentPhrases(): void {
    $builder = $this->builder();
    $this->assertNotSame(
      $builder->build('a', $this->clipDir, 'session'),
      $builder->build('d', $this->clipDir, 'session')
    );
  }

  public function testItBuildsDifferentAudioForDifferentSalts(): void {
    $this->assertNotSame(
      $this->builder(null, 'salt-one')->build('ab', $this->clipDir, 'session'),
      $this->builder(null, 'salt-two')->build('ab', $this->clipDir, 'session')
    );
  }

  public function testItKeepsSeededRandomValuesInRange(): void {
    $builder = $this->builder();
    $method = new \ReflectionMethod($builder, 'rand');
    $method->setAccessible(true);
    $this->callBuild($builder);
    // Every range the builder uses, including the widest noise amplitude.
    foreach ([[-7, 9], [200, 600], [80, 350], [85, 115], [60, 100], [2, 6], [-1966, 1966], [-655, 655], [5, 5]] as [$min, $max]) {
      $values = [];
      for ($i = 0; $i < 4000; $i++) {
        $values[] = $this->intOrFail($method->invoke($builder, $min, $max));
      }
      $this->assertGreaterThanOrEqual($min, min($values));
      $this->assertLessThanOrEqual($max, max($values));
    }
    $values = [];
    for ($i = 0; $i < 2000; $i++) {
      $values[] = $this->intOrFail($method->invoke($builder, -7, 9));
    }
    $unique = array_unique($values);
    sort($unique);
    $this->assertSame(range(-7, 9), $unique);
  }

  public function testItPadsHeadTailAndSilenceAroundEveryClip(): void {
    // Pin every range to its minimum, except resampling which stays at normal speed.
    $pinned = function (int $min, int $max): int {
      return $min === 85 ? 100 : $min;
    };
    $one = ($this->builder($pinned))->build('a', $this->clipDir, 'session');
    $two = ($this->builder($pinned))->build('ab', $this->clipDir, 'session');

    $headAndTail = (200 + 200) * 8;
    $perCharacter = 80 * 8 + self::CLIP_SAMPLES;
    $this->assertSame(($headAndTail + $perCharacter) * 2, strlen($one) - 44);
    $this->assertSame(($headAndTail + 2 * $perCharacter) * 2, strlen($two) - 44);
  }

  public function testItChangesLengthWithResamplingSpeed(): void {
    $slow = function (int $min, int $max): int {
      return $min === 85 ? 85 : $min;
    };
    $fast = function (int $min, int $max): int {
      return $min === 85 ? 115 : $min;
    };
    $slowWav = ($this->builder($slow))->build('a', $this->clipDir, 'session');
    $fastWav = ($this->builder($fast))->build('a', $this->clipDir, 'session');
    $padding = (200 + 200) * 8 + 80 * 8;
    $this->assertSame(($padding + (int)floor(self::CLIP_SAMPLES / 1.15)) * 2, strlen($fastWav) - 44);
    $this->assertSame(($padding + (int)floor(self::CLIP_SAMPLES / 0.85)) * 2, strlen($slowWav) - 44);
  }

  public function testItClampsSamplesToSixteenBitRange(): void {
    // Full-scale clip, full gain, positive noise at its maximum.
    $high = function (int $min, int $max): int {
      return $min === 85 ? 100 : $max;
    };
    $wav = ($this->builder($high))->build('c', $this->clipDir, 'session');
    $samples = $this->samplesOf($wav);
    $this->assertContains(32767, $samples);

    // Same clip with the noise at its most negative.
    $low = function (int $min, int $max): int {
      return $min === 85 ? 100 : ($min < 0 ? $min : $max);
    };
    $samples = $this->samplesOf(($this->builder($low))->build('c', $this->clipDir, 'session'));
    $this->assertContains(-32768, $samples);
  }

  public function testItThrowsForCharacterWithoutClip(): void {
    $this->expectException(\RuntimeException::class);
    $this->builder($this->sequence(3))->build('a!', $this->clipDir, 'session');
  }

  public function testItLowercasesCharactersToFindClips(): void {
    $lower = $this->builder($this->sequence(5))->build('ab', $this->clipDir, 'session');
    $upper = $this->builder($this->sequence(5))->build('AB', $this->clipDir, 'session');
    $this->assertSame($lower, $upper);
  }

  /** @param mixed $value */
  private function intOrFail($value): int {
    if (!is_int($value)) {
      throw new \RuntimeException('Expected an integer.');
    }
    return $value;
  }

  private function callBuild(CaptchaAudioBuilder $builder): void {
    $builder->build('a', $this->clipDir, 'session');
  }

  private function builder(?callable $random = null, string $salt = 'test-salt'): CaptchaAudioBuilder {
    $wp = $this->createMock(WPFunctions::class);
    $wp->method('wpSalt')->willReturn($salt);
    return new CaptchaAudioBuilder($wp, $random);
  }

  /** @return int[] */
  private function samplesOf(string $wav): array {
    /** @var array<int, int> $samples */
    $samples = $this->unpackOrFail('s*', substr($wav, 44));
    return array_values($samples);
  }

  /** @return array<int|string, mixed> */
  private function unpackOrFail(string $format, string $data): array {
    $result = unpack($format, $data);
    if ($result === false) {
      throw new \RuntimeException('Unpack failed.');
    }
    return $result;
  }

  private function sequence(int $step): callable {
    $counter = 0;
    return function (int $min, int $max) use (&$counter, $step): int {
      $counter++;
      return $min + ($counter * $step) % ($max - $min + 1);
    };
  }

  /** @param int[] $samples */
  private function makeWav(array $samples, bool $withListChunk): string {
    $data = pack('v*', ...array_map(function (int $sample): int {
      return $sample & 0xFFFF;
    }, $samples));
    $list = $withListChunk ? 'LIST' . pack('V', 6) . 'INFOab' : '';
    $body = 'WAVE' . 'fmt ' . pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16) . $list . 'data' . pack('V', strlen($data)) . $data;
    return 'RIFF' . pack('V', strlen($body)) . $body;
  }
}
