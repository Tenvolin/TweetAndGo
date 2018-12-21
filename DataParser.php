<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-18
 * Time: 11:42 PM
 */
use DiDom\Document;
class DataParser
{
  const containerMarker = "class=\u{22}tweet";

  // purpose: Parse contents of mobile twitter account page.
  public static function parseTweetContainers($htmlStr) {
    // Parse out all tables
    $document = new Document();
    $document->loadHtml($htmlStr);
    $tables = $document->find("table.tweet");

    // generate an array that contains fields to push into DB.
    $output = [];
    foreach ($tables as $e) {
      $etimeStamp = $e->find("td.timestamp")[0];
      $etext = $e->find("div.tweet-text")[0];

      array_push($output,
        [$etimeStamp->text() , $etext->text()]);
    }

    return $output;
  }

  public static function parseContainerText() {

  }
  public static function parseContainerTimestamp() {

  }


}