<?php
namespace App\Core\Parse;
use DiDom\Document;

class DataParser
{
  private $document;
  private $canonicalLink;

  public function __construct()
  {
    $this->document = new Document();
  }

  /**
   * PURPOSE: Instantiate DOM object and find canonical link.
   * Always call this method before parsing.
   * @param String $htmlStr
   */
  public function loadHtmlStr(String $htmlStr)
  {
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
   * PURPOSE: parse contents of mobile twitter account page.
   * Returns an array of tweets, where every tweet can contain n features.
   * @return array
   */
  public function parseTweetsAndFeatures()
  {
    // parse out all tables
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

    // ensure the next document we tweet is ready for parsing.
    return $tweetArray;
  }

  /**
   * Given a table node, extract tweet message.
   * @param $tweetTableNode
   * @return string
   */
  private function parseTweetMessage(DiDom\Element $tweetTableNode)
  {
    $result = $tweetTableNode->find("div.tweet-text");
    if (count($result) <= 0) {
      throw new ParseException("No tweet message found");
    }

    $eMessage = $result[0];
    $message = trim($eMessage->text());
    return $message;
  }

  /**
   * PURPOSE: parse and return absolute link from current html doc.
   * @return string
   */
  public function parseNextPageLink()
  {
    $document = $this->document;
    try {
      $results = $document->find("div.w-button-more>a");
      if (count($results) <= 0) {
        return '';
      }
    } catch(Exception $e) {
      return '';
    }

    $eNextButton = $results[0];

    // CURL next page of tweets.
    $relativeNextPageLink = trim($eNextButton->getAttribute('href'));
    $restEndpointStart = mb_strpos($relativeNextPageLink , '?');
    $restEndpointStr = mb_substr($relativeNextPageLink , $restEndpointStart);

    // Format
    $absoluteNextPageLink = $this->canonicalLink . $restEndpointStr;
    $mobileAbsoluteNextPageLink = mb_ereg_replace('//', '//mobile.', $absoluteNextPageLink);
    if ($mobileAbsoluteNextPageLink === false) {
      return '';
    }

    Logger::logIfDebugging($mobileAbsoluteNextPageLink);
    return $mobileAbsoluteNextPageLink;
  }

  /**
   * PURPOSE: Given a table node, extract timestamp.
   * todo: feature - pull and make use of link that actually extracts timestamp; this timestamp is not exact.
   * @param $tweetTableNode
   * @return string
   */
  private static function parseContainerTimestamp(DiDom\Element $tweetTableNode)
  {
    $result = $tweetTableNode->find("td.timestamp");
    if (count($result) <= 0) {
      throw new ParseException("No timestamp found");
    }

    $eTimestamp = $result[0];
    $timestamp = trim($eTimestamp->text());
    return $timestamp;
  }

  /**
   * @param \DiDom\Element $tweetTableNode
   * @return string
   */
  private function parseTweetId(\DiDom\Element $tweetTableNode)
  {
    $resultStr = $tweetTableNode->getAttribute('href');
    if (mb_strlen($resultStr) <= 0) {
      throw new ParseException("No tweetId found.");
    }

    $tweetId = trim($resultStr);
    $tweetId = preg_replace("/[^0-9]/", '', $tweetId);
    if (is_null($tweetId) || is_array($tweetId)) {
      return '';
    }

    return $tweetId;
  }

  /**
   * @param Document $document
   * @return string
   */
  private function parseAuthor(Document $document)
  {
    $result = $document->find("div.profile .screen-name");
    if (count($result) <= 0) {
      throw new ParseException("No author found.");
    }

    $eAuthor = $result[0];

    $author = trim($eAuthor->text());

    if (is_null($author)) {
      return '';
    }

    return $author;
  }

  /**
   * @param \DiDom\Element $tweetTableNode
   * @return int
   */
  private function parseTweetType(DiDom\Element $tweetTableNode)
  {
    $result = $tweetTableNode->find("span.context");
    if (count($result) <= 0) {
      return 0;
    }

    $eSpan = $result[0];
    $searchIndex = mb_stripos($eSpan->text(), "retweet", 0, 'UTF-8') ;
    if ($searchIndex >= 0) {
      return 1;
    }

    throw new ParseException("No tweet type found.");
  }
}