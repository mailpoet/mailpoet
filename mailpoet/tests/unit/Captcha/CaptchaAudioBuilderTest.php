<?php declare(strict_types = 1);

namespace MailPoet\Captcha;

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
    $wav = (new CaptchaAudioBuilder($this->sequence(3)))->build('ab', $this->clipDir);

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
    $first = (new CaptchaAudioBuilder($this->sequence(7)))->build('ab', $this->clipDir);
    $second = (new CaptchaAudioBuilder($this->sequence(7)))->build('ab', $this->clipDir);
    $this->assertSame($first, $second);
  }

  public function testItBuildsDifferentAudioForDifferentRandomValues(): void {
    $first = (new CaptchaAudioBuilder($this->sequence(7)))->build('ab', $this->clipDir);
    $second = (new CaptchaAudioBuilder($this->sequence(11)))->build('ab', $this->clipDir);
    $this->assertNotSame($first, $second);
  }

  public function testItBuildsDifferentAudioOnEveryCallWithDefaultRandomness(): void {
    $builder = new CaptchaAudioBuilder();
    $this->assertNotSame($builder->build('ab', $this->clipDir), $builder->build('ab', $this->clipDir));
  }

  public function testItPadsHeadTailAndSilenceAroundEveryClip(): void {
    // Pin every range to its minimum, except resampling which stays at normal speed.
    $pinned = function (int $min, int $max): int {
      return $min === 85 ? 100 : $min;
    };
    $one = (new CaptchaAudioBuilder($pinned))->build('a', $this->clipDir);
    $two = (new CaptchaAudioBuilder($pinned))->build('ab', $this->clipDir);

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
    $slowWav = (new CaptchaAudioBuilder($slow))->build('a', $this->clipDir);
    $fastWav = (new CaptchaAudioBuilder($fast))->build('a', $this->clipDir);
    $padding = (200 + 200) * 8 + 80 * 8;
    $this->assertSame(($padding + (int)floor(self::CLIP_SAMPLES / 1.15)) * 2, strlen($fastWav) - 44);
    $this->assertSame(($padding + (int)floor(self::CLIP_SAMPLES / 0.85)) * 2, strlen($slowWav) - 44);
  }

  public function testItClampsSamplesToSixteenBitRange(): void {
    // Full-scale clip, full gain, positive noise at its maximum.
    $high = function (int $min, int $max): int {
      return $min === 85 ? 100 : $max;
    };
    $wav = (new CaptchaAudioBuilder($high))->build('c', $this->clipDir);
    $samples = $this->samplesOf($wav);
    $this->assertContains(32767, $samples);

    // Same clip with the noise at its most negative.
    $low = function (int $min, int $max): int {
      return $min === 85 ? 100 : ($min < 0 ? $min : $max);
    };
    $samples = $this->samplesOf((new CaptchaAudioBuilder($low))->build('c', $this->clipDir));
    $this->assertContains(-32768, $samples);
  }

  public function testItThrowsForCharacterWithoutClip(): void {
    $this->expectException(\RuntimeException::class);
    (new CaptchaAudioBuilder($this->sequence(3)))->build('a!', $this->clipDir);
  }

  public function testItLowercasesCharactersToFindClips(): void {
    $lower = (new CaptchaAudioBuilder($this->sequence(5)))->build('ab', $this->clipDir);
    $upper = (new CaptchaAudioBuilder($this->sequence(5)))->build('AB', $this->clipDir);
    $this->assertSame($lower, $upper);
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
