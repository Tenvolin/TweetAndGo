<?php


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
  private $certificatePath;
  private $baseLink = 'https://mobile.twitter.com/';

  function __construct()
  {
    $this->ch = curl_init();
    $this->certificatePath = "C:/development/php-7.3.0-nts-Win32-VC15-x64/certs/cacert.pem";

    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($this->ch, CURLOPT_CAINFO, $this->certificatePath);
  }

  /**
   * Gets html data from link or an account's first page of tweets.
   * Fetching is delayed for a couple seconds to prevent spamming.
   * INVARIANT: $link is empty or the next page link of an account.
   * @param $accountName
   * @param string $link
   * @return string
   */
  public function delayedFetch($accountName, $link = "")
  {
    usleep(rand(1000000, 1500000));

    if (mb_strlen($link, "UTF-8") <= 0)
      $link = $this->baseLink . $accountName;

    // Set up fetching configuration
    $ch = $this->ch;
    curl_setopt($ch, CURLOPT_URL, $link);

    // fetch $pagesToFetch page, holding on to all info.
    $result = curl_exec($ch);
    if ($result === false)
      return '';

    return $result;
  }

  public function close()
  {
    curl_close($this->ch);
  }
}
//    $time_post = microtime(true);
//    $exact_time = $time_post - $time_pre;
//    print($exact_time); // todo: use debug parameter to display this information.


//$dataParser = new DataParser();
//$arrayOfPages = [];
//for ($i = 0; $i < $pagesToFetch; $i++) {
//  if (mb_strlen($link, "utf-8") <= 0) {
//    break;
//  }
//
//  // fetch
//  curl_setopt($ch, CURLOPT_URL, $link);
//  usleep(rand(1000000, 1500000));
//
//
//  // Parse a page of tweets from mobile link.
//  $dataParser->loadHtmlStr($output);
//  $onePageOfTweets = $dataParser->parseTweetsAndFeatures();
//  if (is_null($onePageOfTweets))
//    break;
//  $link = $dataParser->parseNextPageLink();
//  if (is_null($link))
//    break;
//
//  array_push($arrayOfPages, $onePageOfTweets);
//}