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
//require_once "../vendor/autoload.php";
// todo: batch $pagesToFetch to push to DB somehow.
$pagesToFetch = 50;

$dataFetcher = new DataFetcher();

$accountTweets = $dataFetcher->fetch($pagesToFetch); // array of array

foreach ($accountTweets as $tweetBundle) {
  foreach($tweetBundle as $tweet) {
    $tweetEnt = new Tweet();

    $date = $tweet[1];


    $tweetEnt->setMessage($tweet[0]);



  }
}

