<?php

use PHPUnit\Framework\TestCase;


class FetchTest extends TestCase
{
  private $testTwitterAcc = 'jaredpar';

  // Curl fields
  private $certificatePath = "../../cacert.pem";
  private $baseLink = 'https://twitter.com/';
  private $mobileBaseLink = 'https://mobile.twitter.com/';

  /** @test */
  public function curl_regularTwitterFetch(): void
  {
    $link = $this->baseLink . $this->testTwitterAcc;

    $ch = $this->curlInitAndOptions();
    curl_setopt($ch, CURLOPT_URL, $link);
    $result = curl_exec($ch);

    $this->assertNotEquals(false, $result,
      "Curl failed");
    $this->assertGreaterThan(0, mb_strlen($result),
      "No valid string content fetched.");
  }

  /** @test */
  public function curl_mobileTwitterFetch(): void
  {
    // look for non-empty str
    $link = $this->mobileBaseLink . $this->testTwitterAcc;

    $ch = $this->curlInitAndOptions();
    curl_setopt($ch, CURLOPT_URL, $link);
    $result = curl_exec($ch);

    $this->assertNotEquals(false, $result,
      "Curl failed");
    $this->assertGreaterThan(0, mb_strlen($result),
      "No valid string content fetched.");
  }

  /** @test */
  public function dataFetch_delayedFetchTest(): void
  {
    $df = new DataFetcher();
    $link = $this->mobileBaseLink . $this->testTwitterAcc;
    $result = $df->delayedFetch($this->testTwitterAcc, $link);

    $this->assertGreaterThan(0, mb_strlen($result),
      "No valid string content fetched.");
  }

  public function curlInitAndOptions()
  {
    $outputCh = curl_init();
    curl_setopt($outputCh, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($outputCh, CURLOPT_TIMEOUT, 5);
    curl_setopt($outputCh, CURLOPT_CAINFO, $this->certificatePath);
    return $outputCh;
  }
}
