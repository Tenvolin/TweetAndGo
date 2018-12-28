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
   * @param $tweetArray
   * @param $entityManager
   */
  public static function push($tweetArray, $entityManager) {
    // todo: Should only insert; no need to generate entire tweet array.
    try {
      foreach ($tweetArray as $tweet) {
        $entityManager->persist($tweet);
      }

      $entityManager->flush();
      // todo: hold onto list of entities, check if each entity exists in the DB before insertion.
      //  Check against DB to see if there are any results that have the exact same tweetId.
    } catch (Exception $e) {
      // todo: error handle better here.
      throw $e;
    }
  }
}