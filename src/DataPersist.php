<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-26
 * Time: 5:36 PM
 */
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class DataPersist
{
  private $entityManager;
  private $conn;
  private $config;
  private $dataParser;
  private $dataFetcher;

  static $TWEET_BATCH = 20;
  static $MAX_TWEET_LIMIT = 3300;

  public function __construct(EntityManager $entityManager, array $conn, \Doctrine\ORM\Configuration $config)
  {
    $this->entityManager = $entityManager;
    $this->conn = $conn;
    $this->config = $config;

    $this->dataParser = new DataParser();
    $this->dataFetcher = new DataFetcher();

  }

  /**
   * @param $tweetArray
   * @param $entityManager EntityManager
   * @throws Exception
   */
  public static function pushTweetArray($tweetArray, $entityManager) {
    // todo: Should only insert; no need to generate entire tweet array.
      foreach ($tweetArray as $tweet) {
        $entityManager->persist($tweet);
      }

      $entityManager->flush();
      // todo: hold onto list of entities, check if each entity exists in the DB before insertion.
      //  Check against DB to see if there are any results that have the exact same tweetId.
  }


  public function fetchAndPersistTweets($accountName, $tweetsWanted) {
    // ==== Persistence logic below.
    // 1) parse one page; then, attempt to insert. On exception thrown, insert all that we can.
    // 2) Pull the remainder from database   entries;
    // 3) Should we not have enough database entries, pull the remaining entries.

    // 1) Extract all tweets made between now and the tweet made;
    // 2) or insert N tweets for the fresh account.
    $dbTweetsExist = $this->tweetsExistInDbForAccount($accountName);
    if ($dbTweetsExist) {
      $results1= $this->pushPossibleTweets($accountName, self::$MAX_TWEET_LIMIT);
    } else {
      $results1= $this->pushPossibleTweets($accountName, $tweetsWanted);
    }


    // when tweets pre-exist in db, one page of tweets will contain >= 0 tweets that need to be inserted.
    $tweetsFetched = $results1['tweetsFetched'];
    $insertExceptionFound = $results1['insertExceptionFound'];
    $lastPageOfTweets = $results1['lastPageOfTweets'];
    $results2 = [];
    if ($insertExceptionFound) {
      // attempt to fetch entries from DB;
      $results2 = $this->insertRemainingTweetsFromLastPage($accountName, $tweetsFetched, $lastPageOfTweets);

    }


    // If DB was unable to satisfy tweet count, attempt a final fetch, beginning from the last tweet made.
    if (count($results2) > 0 ) {
      $tweetsFetched = $results2['tweetsFetched'];
      // check if the database can satisfy $tweetsWanted
      // todo: rename difference and these vars, maybe refactor out.
      $difference = $this->dbHasSufficientTweets($accountName, $tweetsWanted);
      if ($difference < 0) {
//        $nextPageLink = $this->getNextPageLinkFromLastTweet($accountName);
        $results3 = $this->pushPossibleTweetsFromEnd($accountName, abs($difference));
        $i = 5;
      }
    $i = 5;
    }

//    // attempt to fetch from $fetchCount * 20, 20 tweets from the current account.
//    if ($insertExceptionFound) {
//      $offset = $pageFetchCount * self::$tweetBatch;
//      $pageFetchCount++;
//
//      $qb = $entityManager->createQueryBuilder();
//      $qb->select('t') //t.id, t.tweetId, t.message, t.date, t.author
//      ->from('Tweet', 't')
//        ->where("t.author = $accountName")
//        ->orderBy('t.tweetId', 'DESC')
//        ->setFirstResult($offset)
//        ->setMaxResults(self::$tweetBatch);
//      $query = $qb->getQuery();
//
//      $result = $query->getResult();
//      $filteredResults = Util::filterExclusiveTweets($tweetArray, $result);
//
//      try {
//        DataPersist::pushTweetArray($filteredResults, $entityManager);
//      } catch (Exception $e) {
//        // terminate program,
//        throw new RuntimeException("Unable to insert filtered exclusive tweets;
//      something in persistence logic probably went wrong.");
//      }
//    }

//    // pull possible remaining entries from db.
//    // Ensure enough entries remain before pulling, so as to avoid db exceptions.
//    $tweetsInDb = 0;
//    $tweetsToFetchRemaining = 0;
//    if ($pagesToFetch < $pageFetchCount) {
//      $qb = null;
//      $query = null;
//
//      $qb = $entityManager->createQueryBuilder();
//      $qb->select('count(t)')
//        ->from('Tweet', 't')
//        ->where("t.author = $accountName")
//        ->orderBy('t.tweetId', 'DESC');
//      $query = $qb->getQuery();
//
//      $tweetsInDb = $query->getScalarResult();
//      $tweetsToFetchRemaining = ($pageFetchCount - $pagesToFetch) * self::$TWEET_BATCH;
//    }
//
//
//
//    if ($tweetsToFetchRemaining > 0) {
//      $qb = null;
//      $query = null;
//      $result = null;
//      $qb = $entityManager ->createQueryBuilder();
//
//    // Fetch for the following scenarios:
//    //  1) DB does not have any tweets required.
//    //  2) DB has enough tweets required.
//    //  3) Does not have enough tweets required, but some.
//      if ($tweetsInDb == 0) {
//        // Do nothing; nothing to pull from DB.
//      } else if ($tweetsToFetchRemaining <= $tweetsInDb) {
//        $qb->select('t')
//          ->from('Tweet', 't')
//          ->where("t.author = $accountName")
//          ->orderBy('t.tweetId', 'DESC')
//          ->setMaxResults($tweetsToFetchRemaining);
//
//        $query = $qb->getQuery();
//        $result = $query->getResult();
//        $tweetsToFetchRemaining = 0; // DB fully satisfies tweet request.
//      } else if ($tweetsToFetchRemaining > $tweetsInDb) {
//        $qb->select('t')
//          ->from('Tweet', 't')
//          ->where("t.author = $accountName")
//          ->orderBy('t.tweetId', 'DESC')
//          ->setMaxResults($tweetsInDb);
//
//        $query = $qb->getQuery();
//        $result = $query->getResult();
//        $tweetsToFetchRemaining -= $tweetsInDb;
//      }
//    }


//// fetch the remaining possible entries from twitter, when DB is exhausted.
//// pull the last tweet.
//// This will not trigger if DB was never prompted.
//if ($tweetsToFetchRemaining >= 0) {
//  $qb = null;
//  $query = null;
//  $result = null;
//  $qb = $entityManager ->createQueryBuilder();
//
//  // Generate nextPageLink via last tweet stored in DB.
//  $qb->select('t')
//    ->from('Tweet', 't')
//    ->where("t.author = $accountName")
//    ->orderBy('t.tweetId', 'ASC')
//    ->setMaxResults(1);
//
//
//  // fetch remaining tweets
//  for ( ; $pageFetchCount < $pagesToFetch; $pageFetchCount++) {
//    $link = $dataParser->parseNextPageLink();
//    $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
//    $dataParser->loadHtmlStr($tweetsHtmlData);
//    $tweetArray = $dataParser->parseTweetsAndFeatures(); // tuples; maybe generate array of Tweet entities?
//
//    try {
//      DataPusher::pushTweetArray($tweetArray, $entityManager);
//
//    } catch (Exception $e) {
//      // EntityManager object closes; must recreate and begin fetching from database instead.
//      $insertExceptionFound = true;
//      try {
//        $entityManager = EntityManager::create($conn, $config);
//      } catch (Exception $e) {
//        throw new RuntimeException("EntityManager instantiation error: $e");
//      }
//      break;
//    }
//  }
//}
  }

  /**
   * Attempt to fetch $tweetCount tweets and persist to DB.
   * On failure to persist, caller will attempt to pull remaining from DB;
   * should DB not supply enough, one last attempt to pull more tweets using
   * the last DB entry.
   * @param $accountName
   * @param $tweetsToFetch
   * @param bool $persistFromEnd
   * @return array
   */
  private function pushPossibleTweets($accountName, $tweetsToFetch) {
    // todo: turn each large if/forloop block into helper functions like this.
    // todo: output only tweetsRemaining, and if an exception was found.
    //  That's the only logic we care about.
    $dataFetcher = $this->dataFetcher;
    $dataParser = $this->dataParser;
    $entityManager = $this->entityManager;
    $conn = $this->conn;
    $config = $this->config;

    $tweetsFetched = 0;
    $insertExceptionFound = false;
    $tweetArray = [];

    $pagesToFetch = ceil($tweetsToFetch / 20);
    $pageFetchCount = 0;
    for ( ; $pageFetchCount < $pagesToFetch; $pageFetchCount++) {
      $link = $dataParser->parseNextPageLink();
      $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
      $dataParser->loadHtmlStr($tweetsHtmlData);
      $tweetArray = $dataParser->parseTweetsAndFeatures(); // tuples; maybe generate array of Tweet entities?

      try {
        DataPersist::pushTweetArray($tweetArray, $entityManager);
        $tweetsFetched += self::$TWEET_BATCH;
      } catch (Exception $e) {
        // EntityManager object closes; must recreate and begin fetching from database instead.
        $insertExceptionFound = true;
        try {
          $this->entityManager = EntityManager::create($conn, $config);
        } catch (Exception $e) {
          throw new RuntimeException("EntityManager instantiation error: $e");
        }
        break;
      }
      // !!! return $insertExceptionFound, $remainingTweetsRequired.
    }

    $results = [];
    $results['tweetsFetched'] = $tweetsFetched;
    $results['insertExceptionFound'] = $insertExceptionFound;
    $results['lastPageOfTweets'] = $tweetArray;
    return $results;
  }


  /**
   * Should an exception be encountered, the last page of tweets parsed will not
   * be persisted to DB. Thus, we must manually confirm if any of this last page
   * has not been persisted to DB yet.
   * INVARIANT: The #tweets to persist MUST BE < 20.
   * @param $accountName
   * @param $offset
   * @param $lastPageOfTweets
   * @return array
   */
  private function insertRemainingTweetsFromLastPage($accountName, $offset, $lastPageOfTweets) {
    $entityManager = $this->entityManager;

    $qb = $entityManager->createQueryBuilder();
    $qb->select('t')
      ->from('Tweet', 't')
      ->where("t.author = '$accountName'")
      ->orderBy('t.tweetId', 'DESC')
      ->setFirstResult($offset)
      ->setMaxResults(self::$TWEET_BATCH);
    $query = $qb->getQuery();

    $result = $query->getResult();
    $filteredResults = Util::filterExclusiveTweets($lastPageOfTweets, $result);

    try {
      DataPersist::pushTweetArray($filteredResults, $entityManager);
    } catch (Exception $e) {
      // terminate program,
      throw new RuntimeException("Unable to insert filtered exclusive tweets;
    something in persistence logic probably went wrong.");
    }

    // return tweetsToFetchRemaining;
    $tweetsFetched = count($filteredResults);
    $results = [];
    $results['tweetsFetched'] = $tweetsFetched;
    return $results;
  }

  private function tweetsExistInDbForAccount(String $accountName) {
    $entityManager = $this->entityManager;

    $qb = $entityManager->createQueryBuilder();
    $qb->select('count(t)')
      ->from('Tweet', 't')
      ->where("t.author = '$accountName'");

    $query = $qb->getQuery();

    $result = $query->getScalarResult()[0][1];
    return $result > 0;
  }

  private function pushPossibleTweetsFromEnd(String $accountName, int $tweetsToFetch) {
    $dataFetcher = $this->dataFetcher;
    $dataParser = $this->dataParser;
    $entityManager = $this->entityManager;
    $conn = $this->conn;
    $config = $this->config;

    $tweetsFetched = 0;
    $insertExceptionFound = false;
    $tweetArray = [];

    $pagesToFetch = ceil($tweetsToFetch / 20);
    $pageFetchCount = 0;
    $link = null;
    for ( ; $pageFetchCount < $pagesToFetch; $pageFetchCount++) {
      if (is_null($link)) {
        $link = $this->getNextPageLinkFromLastTweet($accountName);
      } else {
        $link = $dataParser->parseNextPageLink();
      }

      $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
      $dataParser->loadHtmlStr($tweetsHtmlData);
      $tweetArray = $dataParser->parseTweetsAndFeatures(); // tuples; maybe generate array of Tweet entities?

      try {
        DataPersist::pushTweetArray($tweetArray, $entityManager);
        $tweetsFetched += count($tweetArray);
      } catch (Exception $e) {
        // EntityManager object closes; must recreate and begin fetching from database instead.
        $insertExceptionFound = true;
        try {
          $this->entityManager = EntityManager::create($conn, $config);
        } catch (Exception $e) {
          throw new RuntimeException("EntityManager instantiation error: $e");
        }
        break;
      }
      // !!! return $insertExceptionFound, $remainingTweetsRequired.
    }

    $results = [];
    $results['tweetsFetched'] = $tweetsFetched;
    $results['insertExceptionFound'] = $insertExceptionFound;
    $results['lastPageOfTweets'] = $tweetArray;
    return $results;
  }

  // LOGIC: provides a link that can be used to fetch tweets. These tweets will contain the last tweet itself.
  private function getNextPageLinkFromLastTweet(String $accountName) {
    $entityManager = $this->entityManager;
    $dataFetcher = $this->dataFetcher;

    // Search DB for nextPageLink.
    $qb = null;
    $query = null;
    $result = null;
    $qb = $entityManager->createQueryBuilder();

    // Generate nextPageLink via last tweet stored in DB.
    $qb->select('t')
      ->from('Tweet', 't')
      ->where("t.author = '$accountName'")
      ->orderBy('t.tweetId', 'ASC')
      ->setMaxResults(1);

    $query = $qb->getQuery();
    $result = $query->getResult();

    if (count($result) != 1) {
      return '';
    }

    $lastTweet = $result[0];
    $lastTweetId = $lastTweet->getTweetId();

    $newLink = $dataFetcher->getBaseLink();
    $newLink .= $accountName . '/';

    // The following decrement is to force a completely unique set of tweets fetched.
    // Otherwise, one duplicate tweet appears; leading to an exception.
    $newLink .= "?max_id=" . (intval($lastTweetId) - 1);

    return $newLink;
  }

  /**
   * Returns difference between tweetsWanted and tweetsInDb.
   * Insufficient tweets: [-inf, 0)
   * sufficient tweets: [0, inf]
   * @param $accountName
   * @param $tweetsWanted
   * @return int
   */
  private function dbHasSufficientTweets(String $accountName, int $tweetsWanted) {
    $qb = null;
    $query = null;
    $result = null;
    $entityManager = $this->entityManager;

    $qb = $entityManager->createQueryBuilder();
    $qb->select('count(t)')
      ->from('Tweet', 't')
      ->where("t.author = '$accountName'");

    $query = $qb->getQuery();

    $tweetsInDb = $query->getScalarResult()[0][1];

    return $tweetsInDb - $tweetsWanted;
  }
}