<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:42 PM
 */
use DiDom\Document;
// todo: implement exceptions for calling parsing without loading htmlStr.
class DataParser
{
  private $document;
  private $canonicalLink;

  public function __construct() {
    $this->document = new Document();
  }

  // purpose: Instantiate DOM object and find canonical link.
  // Always call this method before parsing.
  // todo: Extract out a parseCanonicalLink() method; and
  //  consider creating a factory class for loading up our loadHTMLdoc.
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
   * Returns a 2-d array; An array of tweets, where every tweet can contain n features.
   * Features: tweetText, timestamp.
   * @return array;
   */
  public function parseTweetsAndFeatures() {
  // Parse out all tables
  $document = $this->document;
  $tables = $document->find("table.tweet"); // todo: refactor this and other find-calls to throw exception
  $author = $this->parseAuthor($document);

  // generate an array that contains fields to push into DB.
  $tweets = [];

  foreach ($tables as $e) {
    $tweetId = $this->parseTweetId($e);
    $text = $this->parseTweetMessage($e);
    $timestamp =  $this->parseContainerTimestamp($e);

    array_push($tweets,
      [$author, $tweetId, $text, $timestamp]);
  }
  return $tweets;
  }

  public function parseNextPageLink() {
    $document = $this->document;
    $results = $document->find("div.w-button-more>a");
    if (count($results) <= 0) {
      return null;
    }
    $eNextButton = $results[0];

    // Parse restEndpoint in order to CURL next page of tweets.
    $relativeNextPageLink = trim($eNextButton->getAttribute('href'));
    $restEndpointStart = mb_strpos($relativeNextPageLink , '?');
    $restEndpointStr = mb_substr($relativeNextPageLink , $restEndpointStart);

    // set up next mobile twitter link
    $absoluteNextPageLink = $this->canonicalLink . $restEndpointStr;
    $mobileAbsoluteNextPageLink = mb_ereg_replace('//', '//mobile.', $absoluteNextPageLink);
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
      return null;
    }
    $eMessage = $result[0];
    $message = trim($eMessage->text());
    return $message;
  }

  /**
   * Given a table node, extract timestamp.
   * todo: feature - pull and make use of link that actually extracts timestamp; this timestamp is not exact.
   * @param $tweetTableNode
   * @return string|null
   */
  private static function parseContainerTimestamp($tweetTableNode) {
    $result = $tweetTableNode->find("td.timestamp");
    if (count($result) <= 0) {
      return null;
    }
    $eTimestamp = $result[0];
    $timestamp = trim($eTimestamp->text());
    return $timestamp;
  }

  /**
   * @param $tweetTableNode
   * @return string|null
   */
  private function parseTweetId($tweetTableNode) {
    $resultStr = $tweetTableNode->getAttribute('href');
    if (mb_strlen($resultStr) <= 0) {
      return null;
    }

    $tweetId = trim($resultStr);
    $tweetId = preg_replace("/[^0-9]/", '', $tweetId);

    if (is_null($tweetId) || is_array($tweetId))
    {
      return null;
    }

    return $tweetId;
  }

  /**
   * @param $e
   * @return string|null
   */
  private function parseAuthor($document) {
    $result = $document->find("div.profile .screen-name");
    if (count($result) <= 0) {
      return null;
    }
    $eAuthor = $result[0];

    $author = trim($eAuthor->text());

    if (is_null($author))
    {
      return null;
    }

    return $author;
  }

}