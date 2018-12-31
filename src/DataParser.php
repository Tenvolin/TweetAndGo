<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:42 PM
 */
use DiDom\Document;
// todo: implement exceptions for calling parsing without loading htmlStr.
// returns array or strings.
class DataParser
{
  private $document;
  private $canonicalLink;

  public function __construct() {
    $this->document = new Document();
  }

  // todo: Extract out a parseCanonicalLink() method; and
  //  consider creating a factory class for loading up our loadHTMLdoc.
  /**
   * purpose: Instantiate DOM object and find canonical link.
   * Always call this method before parsing.
   * @param $htmlStr
   */
  public function loadHtmlStr($htmlStr) {
    $this->document->loadHtml($htmlStr);

    $result = $this->document->find('*[rel=canonical]');
    if (count($result) <= 0 ) {
      throw new ParseException("Unable to parse author name.");
    }

    $canonicalLink = $result[0]->getAttribute('href');
    if (is_null($canonicalLink)) {
      throw new ParseException("Unable to parse canonical link.");
    }
    $this->canonicalLink = $canonicalLink;
  }

  /**
   * Parse contents of mobile twitter account page.
   * Returns an array of tweets, where every tweet can contain n features.
   * Features: tweetText, timestamp.
   * @return Tweet[];
   */
  public function parseTweetsAndFeatures() {
  // Parse out all tables
  $document = $this->document;
  $tables = $document->find("table.tweet"); // todo: refactor this and other find-calls to throw exception
  $author = $this->parseAuthor($document);

  // generate an array that contains fields to push into DB.
  $tweetArray = [];

  foreach ($tables as $e) {
    $tweetId = $this->parseTweetId($e);
    $message = $this->parseTweetMessage($e);
    // todo; datetime not as accurate as we'd like. Getting 1-day off errors._
    $timestamp =  Util::convertUnformattedTwitterDateToDateTime($this->parseContainerTimestamp($e));
    $type = $this->parseTweetType($e);

    $tweet = new Tweet($author, $tweetId, $message, $timestamp, $type);
    array_push($tweetArray, $tweet);
  }
  return $tweetArray;
  }

  public function parseNextPageLink() {
    $document = $this->document;
    $results = $document->find("div.w-button-more>a");
    if (count($results) <= 0) {
      return '';
    }
    $eNextButton = $results[0];

    // Parse restEndpoint in order to CURL next page of tweets.
    $relativeNextPageLink = trim($eNextButton->getAttribute('href'));
    $restEndpointStart = mb_strpos($relativeNextPageLink , '?');
    $restEndpointStr = mb_substr($relativeNextPageLink , $restEndpointStart);

    // set up next mobile twitter link
    $absoluteNextPageLink = $this->canonicalLink . $restEndpointStr;
    $mobileAbsoluteNextPageLink = mb_ereg_replace('//', '//mobile.', $absoluteNextPageLink);

    if (!is_null($GLOBALS['debug_logger'])) {
      $GLOBALS['debug_logger']->log($mobileAbsoluteNextPageLink);
    }
    return $mobileAbsoluteNextPageLink;
  }


  /**
   * Given a table node, extract tweet message.
   * @param $tweetTableNode
   * @return string
   */
  private function parseTweetMessage($tweetTableNode) {
    $result = $tweetTableNode->find("div.tweet-text");
    if (count($result) <= 0) {
      return '';
    }
    $eMessage = $result[0];
    $message = trim($eMessage->text());
    return $message;
  }

  /**
   * Given a table node, extract timestamp.
   * todo: feature - pull and make use of link that actually extracts timestamp; this timestamp is not exact.
   * @param $tweetTableNode
   * @return string
   */
  private static function parseContainerTimestamp($tweetTableNode) {
    $result = $tweetTableNode->find("td.timestamp");
    if (count($result) <= 0) {
      return '';
    }
    $eTimestamp = $result[0];
    $timestamp = trim($eTimestamp->text());
    return $timestamp;
  }

  /**
   * @param $tweetTableNode
   * @return string
   */
  private function parseTweetId($tweetTableNode) {
    $resultStr = $tweetTableNode->getAttribute('href');
    if (mb_strlen($resultStr) <= 0) {
      return '';
    }

    $tweetId = trim($resultStr);
    $tweetId = preg_replace("/[^0-9]/", '', $tweetId);

    if (is_null($tweetId) || is_array($tweetId))
    {
      return '';
    }

    return $tweetId;
  }

  /**
   * @param $document
   * @return string
   */
  private function parseAuthor($document) {
    $result = $document->find("div.profile .screen-name");
    if (count($result) <= 0) {
      return '';
    }
    $eAuthor = $result[0];

    $author = trim($eAuthor->text());

    if (is_null($author))
    {
      return '';
    }

    return $author;
  }


  private function parseTweetType(DiDom\Element $tweetTableNode) {
    $result = $tweetTableNode->find("span.context");
    if (count($result) <= 0) // todo: replicate this for all other parsing; make more robust.
      return 0;

    $eSpan = $result[0];

    // retweet
    $searchIndex = mb_stripos($eSpan->text(), "retweet", 0, 'UTF-8') ;
    if ($searchIndex >= 0)
      return 1;

    // No type found
    throw new ParseException("No tweet type found.");
  }
}