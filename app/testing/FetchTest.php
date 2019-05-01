<?php
use PHPUnit\Framework\TestCase;

class FetchTest extends TestCase
{
  /** @test */
  public function myFirstTest() : void
  {
    $i = 1;
    $this->assertEquals(1, $i, "Nice meme");
  }

  /** @test */
  public function test2() : void
  {
    $i = 5;
    $this->assertNotEquals(2, $i, "Nice meme");
  }
}
