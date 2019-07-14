<?php
namespace App\Core\Parse;

class ErrorParser
{
  // ==== Example of an error encountered START.
  // An exception occurred while executing 'INSERT INTO tweets (author, tweetId, message, date) VALUES (?, ?, ?, ?)'
  // with params ["realDonaldTrump", "1079214392758145024", "\u201cIt turns out to be true now, that the Department of
  // Justice and the FBI, under President Obama, rigged the investigation for Hillary and really turned the screws on
  // Trump, and now it looks like in a corrupt & illegal way. The facts are out now. Whole Hoax exposed. @JesseBWatters",
  // "2018-12-30 03:18:56"]:
  //
  // SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '1079214392758145024' for key 'UNIQ_AA384025F6DA78B2'
  // ==== Example of an error encountered END.
  public static function parseDuplicateMessageForTweetId(String $msg)
  {
    // params \[.*]
    mb_regex_encoding('UTF-8');
    $results = [];
    preg_match("/\[.*\]/", $msg, $results);
    $preggedStr = $results[0];

    $strToSplit = mb_substr($preggedStr,
      1,
      mb_strlen($preggedStr, 'UTF-8') - 2,
      'UTF-8');

    $splitArr = preg_split("/[,]+/", $strToSplit);
    $indexForTweetId = 1; // todo: Write Tests for parsing duplicate message errors!

    // TODO: Remove. Temporary check. Why does the splitarray have no data?
    try {
      $tweetId = $splitArr[$indexForTweetId];
    } catch (Exception $e) {
      print("Parsing duplicate message went wrong. Msg: " .
        $msg . "\n" .
        $splitArr);
    }

    $tweetId = trim(preg_replace('/"*/', '', $tweetId));
    return $tweetId;
  }

  public static function filterOutOffendingTweet($tweets, $tweetId)
  {
    $results = array_filter($tweets,
      function (Tweet $tweet) use ($tweetId) {
        return $tweet->getTweetId() != $tweetId;
      });

    return $results;
  }

}