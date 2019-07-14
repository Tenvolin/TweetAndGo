<?php
namespace App\Core\Parse;

class CSVAccountParser
{
  /**
   * Parses local csv file and returns all account names. Used to figure out what accounts to scrape tweets for.
   * @param $filepath
   * @return array
   */
  public static function parseAccounts($filepath)
  {
    $h = fopen($filepath, "r");
    $accountNames = [];
    while (($data = fgetcsv($h, 1000, ",")) !== FALSE) {
      // read the data.
      $accountNames = array_merge($accountNames, $data);
    }
    return $accountNames;
  }

  public static function checkCLIArguments($args)
  {
    /**
     *  $targetOneAccount = false;
     *  $targetCSVFileAccounts = false;
     */
    // check if targeting one account
    // Q: What are we returning?


  }

  public static function checkIfTargetOneAccount($args)
  {
    if (count($args) !== 4)
      return false;

    $expectedKeyword = "-persistsingleaccount";
    $keywordIdx = 1;
    $keyword = $args[$keywordIdx];
    if (strcasecmp($keyword, $expectedKeyword) != 0) {
      return false;
    }


    return true;
  }

  public static function parseAccountName($args)
  {
    $accountIdx = 2;
    return $args[$accountIdx];
  }

  public static function parseTweetCount($args)
  {
    $tweetCountIdx = 3;
    return $args[$tweetCountIdx];
  }

  public static function checkIfTargetCSV($args)
  {
    if (count($args) !== 4)
      return false;

    $expectedKeyword = "-persistfromcsv";
    $keywordIdx = 1;
    $keyword = $args[$keywordIdx];
    if (strcasecmp($keyword, $expectedKeyword) != 0) {
      return false;
    }

    return true;
  }

  public static function parseCSVFilepath($args)
  {
    $CSVFilepath = 2;
    return $args[$CSVFilepath];
  }

}

