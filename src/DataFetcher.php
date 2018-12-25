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
  function __construct() {

  }

  /**
   * @param $pagesToFetch int
   * @return array
   */
  function fetch($pagesToFetch)
  {
    $time_pre = microtime(true);
    if ($pagesToFetch <= 0) {
      return [];
    }

    $certificatePath = "C:/development/php-7.3.0-nts-Win32-VC15-x64/certs/cacert.pem";
    $link = "https://mobile.twitter.com/miraieu";

    // Set up fetching configuration
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CAINFO, $certificatePath);

    // fetch $pagesToFetch page, holding on to all info.
    $dataParser = new DataParser();
    $arrayOfPages = [];
    for ($i = 0; $i < $pagesToFetch; $i++) {
      if (mb_strlen($link, "utf-8") <= 0) {
        break;
      }

      // fetch
      curl_setopt($ch, CURLOPT_URL, $link);
      usleep(rand(1000000, 1500000));
      $output = curl_exec($ch);

      // Parse a page of tweets from mobile link.
      $dataParser->loadHtmlStr($output);
      $onePageOfTweets = $dataParser->parseTweetsAndFeatures();
      if (is_null($onePageOfTweets))
        break;
      $link = $dataParser->parseNextPageLink();
      if (is_null($link))
        break;

      array_push($arrayOfPages, $onePageOfTweets);
    }
    $time_post = microtime(true);
    $exact_time = $time_post - $time_pre;
    print($exact_time);
    curl_close($ch);
    return $arrayOfPages;
  }
}