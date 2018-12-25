<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:40 PM
 */
include_once "../bootstrap.php";
include_once "DataParser.php";
include_once "DataFetcher.php";
include_once "util.php";
include_once "model/Tweet.php";
//require_once "../vendor/autoload.php";
// todo: batch $pagesToFetch to push to DB somehow.

// string manipulation config.
mb_internal_encoding("UTF-8");

$pagesToFetch = 3;

$dataFetcher = new DataFetcher();

$accountTweets = $dataFetcher->fetch($pagesToFetch); // array of array

foreach ($accountTweets as $tweetBundle) {
  foreach($tweetBundle as $tweet) {
    $msg = $tweet[0];
    $date = Util::convertUnformattedTwitterDateToDateTime($tweet[1]);
    $tweetId = $tweet[2];

    $tweetEntity = new Tweet($tweetId);
    $tweetEntity->setMessage($msg);
    $tweetEntity->setDate($date);
  }
}

