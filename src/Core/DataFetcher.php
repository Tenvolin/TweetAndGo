<?php
namespace App\Core;

/**
 * Crawler used to fetch all the posts from the user from a certain time. The mobile subdomain of an account is what we
 * want, as it allows for transfer of less data, and provides the ability to scroll through lists of data.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:41 PM
 */

class DataFetcher
{
  private $ch;
  private $certificatePath = "../../cacert.pem"; // TODO: extract out constants.
  private $baseLink = 'https://mobile.twitter.com/';

  function __construct()
  {
    $this->ch = curl_init();
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($this->ch, CURLOPT_CAINFO, $this->certificatePath);
  }

  /**
   * PURPOSE: Gets html data from link or an account's first page of tweets.
   * Fetching is delayed for a couple seconds to prevent spamming.
   * INVARIANT: $link is empty or the next page link of an account.
   * @param $accountName
   * @param string $link
   * @param bool $overrideDelay
   * @return string
   */
  public function delayedFetch(String $accountName, String $link = "", bool $overrideDelay = false)
  {
    if (!$overrideDelay) {
      usleep(rand(1500000, 2000000));
    }

    if (mb_strlen($link, "UTF-8") <= 0) {
      $link = $this->baseLink . $accountName;
    }

    // Set up fetching configuration
    $ch = $this->ch;
    curl_setopt($ch, CURLOPT_URL, $link);

    $result = curl_exec($ch);
    if ($result === false)
      return '';

    return $result;
  }

  public function close()
  {
    curl_close($this->ch);
  }

  public function getBaseLink()
  {
    return $this->baseLink;
  }
}

