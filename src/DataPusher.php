<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-26
 * Time: 5:36 PM
 */

class DataPusher
{
  /**
   * @param $accountTweets array
   * @param $entityManager \Doctrine\ORM\EntityManager
   */
  public static function push($accountTweets, $entityManager) {
    try {
      foreach ($accountTweets as $tweetBundle) {
        foreach($tweetBundle as $tweet) {
          // todo: somehow make these index references more robust.
          //  Make the list producer ensure everything is non-null and of the correct size.
          $author = $tweet[0];
          $tweetId = $tweet[1];
          $msg = $tweet[2];
          $date = Util::convertUnformattedTwitterDateToDateTime($tweet[3]); // todo: where should dataTime processing take place?

          $tweetEntity = new Tweet($author, $tweetId, $msg, $date);
          $entityManager->persist($tweetEntity);
        }
        $entityManager->flush();

        // todo: hold onto list of entities, check if each entity exists in the DB before insertion.
        //  Check against DB to see if there are any results that have the exact same tweetId.
      }
    } catch (Exception $e) {
      // todo: error handle better here.
      print ("Something went wrong!\n");
    }
  }
}