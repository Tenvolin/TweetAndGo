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
   * Fetch list of tweets from $days back.
   * @param $days
   */
  function fetch()
  {
    $certificatePath = "C:/development/php-7.3.0-nts-Win32-VC15-x64/certs/cacert.pem";
    $testSite1 = "https://twitter.com/miraieu";
    $testSite2 = "http://www.google.com";
    $testSite3 = "https://www.google.com";
    $testSite4 = "https://mobile.twitter.com/miraieu";

    // fetch
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $testSite4);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_CAINFO, $certificatePath);

    $output = curl_exec($ch);
    curl_close($ch);




    // parse
    $onePageOfTweets = DataParser::parseTweetContainers($output);


    echo $output;
  }
}