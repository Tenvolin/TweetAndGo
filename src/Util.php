<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-20
 * Time: 7:18 PM
 */



class Util {
  const TIME_FORMAT_WITHIN_SECONDS = 1;
  const TIME_FORMAT_WITHIN_MINUTES = 2;
  const TIME_FORMAT_WITHIN_HOURS = 3;
  const TIME_FORMAT_WITHIN_DAYS = 3;
  const TIME_FORMAT_WITHIN_MONTH = 4;
  const TIME_FORMAT_BEYOND_YEAR = 5;

  // https://stackoverflow.com/questions/3666306/how-to-iterate-utf-8-string-in-php/14366023#14366023
  // return false or char.
  public static function nextChar($string, &$pointer){
    if(!isset($string[$pointer])) return false;
    $char = ord($string[$pointer]);
    if($char < 128){
      return $string[$pointer++];
    }else{
      if($char < 224){
        $bytes = 2;
      }elseif($char < 240){
        $bytes = 3;
      }elseif($char < 248){
        $bytes = 4;
      }elseif($char == 252){
        $bytes = 5;
      }else{
        $bytes = 6;
      }
      $str =  substr($string, $pointer, $bytes);
      $pointer += $bytes;
      return $str;
    }
  }

  /**
   * Timestamps of tweets are not consistently formatted. They take five possible variations and thus need to be converted
   * to a DateTime object before persistence.
   * @param $abbreviatedDate
   * @return DateTime
   */
  public static function convertUnformattedTwitterDateToDateTime($abbreviatedDate) {
    // abbreviated date will be of the following form:
    // Not going to store exact times, as that's going to require many more http requests, which may result in banning.
    // | "dec 21" - This tweet took place on this day.
    // | "9 Jun 2016" - a date in a previous year.
    // | "2d" - 2 days old.
    // | "2h" - 2 hours old.
    // | "2m" - 2 mins old.
    // | "2s" - 2 secs old.
    try {
      $currentDateTime = null;
      $courseOfAction = self::findTimeFormat($abbreviatedDate);
      $durationToSubtract = "P";
      $tweetDateTime = null;

      // differentiate between different time formats Twitter provides.
      if ($courseOfAction === self::TIME_FORMAT_WITHIN_HOURS ||
          $courseOfAction === self::TIME_FORMAT_WITHIN_MINUTES ||
          $courseOfAction === self::TIME_FORMAT_WITHIN_SECONDS) {

        $currentDateTime = new DateTime();
        $durationToSubtract.= "T";
        $durationToSubtract.= mb_convert_case($abbreviatedDate, MB_CASE_UPPER, "UTF-8");
        $dateInterval = new DateInterval($durationToSubtract);
        $tweetDateTime = $currentDateTime->sub($dateInterval);

      } else if ( $courseOfAction === self::TIME_FORMAT_WITHIN_DAYS) {
        $dateStr = intval($abbreviatedDate) . "days";
        $tweetDateTime = new DateTime($dateStr);
      } else if (self::isDifferentMonthSameYear($abbreviatedDate)) {
        $tweetDateTime = new DateTime($abbreviatedDate);
      } else if (self::isDifferentYear($abbreviatedDate)) {
        $tweetDateTime = new DateTime($abbreviatedDate);
      }


      // remove one year; mobile twitter removes the year from posts within a year.
      if (self::needToRemoveOneYear($tweetDateTime)) {
        $dateInterval = new DateInterval("P1Y");
        $tweetDateTime->sub($dateInterval);
      }

       // todo: handle this scenario better?
      if (is_null($tweetDateTime)) {
        throw new Exception("Date format not understood ($abbreviatedDate)");
      }

      return $tweetDateTime;

    } catch(Exception $e) {
      Throw new RuntimeException("Problem when determining date and time: $e");
    }

  }

  /**
   * Invariant: input string is trimmed
   * Returns a value
   * @param $abbreviatedDate string
   * @return int
   */
  private static function findTimeFormat($abbreviatedDate) {
    $case = 0;
    $charsArray = preg_split('//u', $abbreviatedDate, null, PREG_SPLIT_NO_EMPTY);
    $lastIndex = count($charsArray) - 1;

    if (mb_strlen($abbreviatedDate) <= 3 && $charsArray[$lastIndex] == 'h') {
      $case = self::TIME_FORMAT_WITHIN_HOURS;
    } else if (mb_strlen($abbreviatedDate) === 3 && $charsArray[$lastIndex] == 'm') {
      $case = self::TIME_FORMAT_WITHIN_MINUTES;
    } else if (mb_strlen($abbreviatedDate) === 3 && $charsArray[$lastIndex] == 's') {
      $case = self::TIME_FORMAT_WITHIN_SECONDS;
    } else if (mb_strlen($abbreviatedDate) <= 6) {
      $case = self::TIME_FORMAT_WITHIN_MONTH;
    } else if (mb_strlen($abbreviatedDate) <= 10) {
      $case = self::TIME_FORMAT_BEYOND_YEAR;
    }

    return $case;
  }

  /**
   * Continually prompt user for input until some possible username found.
   * @return string
   */
  public static function promptForValidUsername() {
    $isValidInput = false;
    $account = "";

    while (!$isValidInput) {
      $account = readline("Account name: ");

      if (mb_strlen($account, "UTF-8") <= 0) {
        continue;
      } else {
        $isValidInput = true;
      }
    }

    return $account;
  }

  /**
   * Continually prompt user for input until integer found.
   * @return int
   */
  public static function promptForValidTweetCount() {
    $isValidInput = false;
    $tweetCount = 0;

    while (!$isValidInput) {
      $tweetCountStr = readline("Number of tweets to get: ");
      $tweetCount = intVal($tweetCountStr);

      if ($tweetCount == 0) {
        continue;
      }

      $isValidInput = true;
    }

    return $tweetCount;
  }

  /**
   * todo: determine if method is robust; not sure if l1 or l2 needs to be longer.
   * todo: was a hashmap the better choice?
   * @param $list1 Tweet[]
   * @param $list2 Tweet[]
   * @return array
   */
  public static function filterExclusiveTweets($list1, $list2)
  {
    $result = [];
    foreach ($list1 as $e1) {
      foreach ($list2 as $e2) {
        if ($e1->getTweetId() === $e2->getTweetId())
          return $result;
      }

      array_push($result, $e1);
    }

    return $result;
  }

  // todo: fix. Not proper check.
  public static function isDifferentMonthSameYear(String $date)
  {
    return mb_strlen($date, 'UTF-8') == 5 || mb_strlen($date, 'UTF-8') == 6;
  }

  public static function isDifferentYear(String $date)
  {
    $result = preg_split("/ /", $date);
    return count($result) == 3;
  }

  /**
   *
   * @param DateTime $someDate
   * @return bool
   * @throws Exception
   */
  public static function needToRemoveOneYear(DateTime $someDate) {
    $currentDate = new DateTime();

    $diff = $someDate->diff($currentDate);
    if ($diff->invert) {
      return true;
    }

    return false;
  }
}

