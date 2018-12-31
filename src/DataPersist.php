<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2018-12-26
 * Time: 5:36 PM
 */
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
   * @throws Exception
   */
  public function pushTweetArray($tweetArray) {
    $entityManager = &$this->entityManager;
    foreach ($tweetArray as $tweet) {
      $entityManager->persist($tweet);
    }
    $entityManager->flush();
  }


  public function fetchAndPersistTweets($accountName, $tweetsWanted) {
    $dbTweetsFound = $this->tweetsExistInDbForAccount($accountName);
    // todo: Fix name schemes here. They're bad.

    if ($dbTweetsFound) {
      $tweetsPersisted = $this->persistFromFront($accountName);
    } else {
      $tweetsPersisted = $this->pushPossibleTweets($accountName, $tweetsWanted);
    }

    $dbTweetsAvailable = $this->dbHasSufficientTweets($accountName, $tweetsWanted);
    if ($dbTweetsFound && $dbTweetsAvailable < 0 ) {
      $tweetsPersisted += $this->persistFromBack($accountName);
    }

    return $tweetsPersisted;
// refactor start
//    // when tweets pre-exist in db, one page of tweets will contain >= 0 tweets that need to be inserted.
//    $tweetsFetched = $results1['tweetsFetched'];
//    $insertExceptionFound = $results1['insertExceptionFound'];
//    $lastPageOfTweets = $results1['lastPageOfTweets'];
//    $results2 = [];
//    if ($insertExceptionFound) {
//      // attempt to fetch entries from DB;
//      $results2 = $this->insertRemainingTweetsFromLastPage($accountName, $tweetsFetched, $lastPageOfTweets);
//
//    }
//
//
//    // If DB was unable to satisfy tweet count, attempt a final fetch, beginning from the last tweet made.
//    if (count($results2) > 0 ) {
//      $tweetsFetched = $results2['tweetsFetched'];
//      // check if the database can satisfy $tweetsWanted
//      // todo: rename difference and these vars, maybe refactor out.
//      $difference = $this->dbHasSufficientTweets($accountName, $tweetsWanted);
//      if ($difference < 0) {
////        $nextPageLink = $this->getNextPageLinkFromLastTweet($accountName);
//        $results3 = $this->pushPossibleTweetsFromEnd($accountName, abs($difference));
//        $i = 5;
//      }
//    $i = 5;
//    }
// refactor end
  }

  /**
   * Persists some tweets to DB for some account with no pre-existing records in DB.
   * Returns a count of total possible inserts into DB.
   * @param $accountName
   * @param $tweetsToFetch
   * @return int
   */
  private function pushPossibleTweets($accountName, $tweetsToFetch) {
    $dataFetcher = &$this->dataFetcher;
    $dataParser = &$this->dataParser;

    $fetchCount = 0;
    while ($fetchCount <= $tweetsToFetch) {
      $link = $dataParser->parseNextPageLink();
      $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
      $dataParser->loadHtmlStr($tweetsHtmlData);

      $tweetArray = $dataParser->parseTweetsAndFeatures();
      if (count($tweetArray) == 0) {
        break;
      }

      $pushedArray = $this->pushAndIgnoreConflicts($tweetArray);
      if (count($pushedArray) == 0) {
        throw new \http\Exception\RuntimeException("One page of tweets did not have a single tweet that could
        be inserted. This method should not have been called if DB entries pre-exist.");
      }
      $fetchCount += count($pushedArray);
    }

    return $fetchCount;
  }


  /**
   * Should an exception be encountered, the last page of tweets parsed will not
   * be persisted to DB. Thus, we must manually confirm if any of this last page
   * has not been persisted to DB yet.
   * INVARIANT: The #tweets to persist MUST BE < 20.
   * @param $accountName
   * @param $offset
   * @param $lastPageOfTweets
   * @return int
   */
  private function insertRemainingTweetsFromLastPage($accountName, $offset, $lastPageOfTweets) {
    $entityManager = $this->entityManager;

    $qb = $entityManager->createQueryBuilder();
    $qb->select('t')
      ->from('Tweet', 't')
      ->where("t.author = '$accountName'")
      ->orderBy('t.date', 'DESC')
      ->setFirstResult($offset)
      ->setMaxResults(self::$TWEET_BATCH);
    $query = $qb->getQuery();

    $result = $query->getResult();
    $filteredResults = Util::filterExclusiveTweets($lastPageOfTweets, $result);

    try {
      $this->pushTweetArray($filteredResults);
    } catch (Exception $e) {
      // terminate program,
      throw new RuntimeException("Unable to insert filtered exclusive tweets;
    something in persistence logic probably went wrong.");
    }

    // return tweetsToFetchRemaining;
    return count($filteredResults);
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
        $this->pushTweetArray($tweetArray);
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

  /**
   * Persists some subset of $tweetArray to DB.
   * @param array $tweetArray
   * @return array
   */
  private function pushAndIgnoreConflicts(array $tweetArray) {
    $entityManager = &$this->entityManager;
    $conn = &$this->conn;
    $config = &$this->config;

    while (count($tweetArray) != 0) {
      try {
        $this->pushTweetArray($tweetArray);
        break;
      } catch (Exception $e) {
        $offendingTweetId = ErrorParser::parseDuplicateMessageForTweetId($e->getMessage());

        $tweetArray = ErrorParser::filterOutOffendingTweet($tweetArray, $offendingTweetId);
        try {
          $entityManager = EntityManager::create($conn, $config);
        } catch (Exception $e) {
          throw new RuntimeException("EntityManager instantiation error: $e");
        }
      }
    }

    return $tweetArray;
  }

  private function persistFromFront($accountName) {
    $tweetsToFetch = self::$MAX_TWEET_LIMIT;
    $dataFetcher = &$this->dataFetcher;
    $dataParser = &$this->dataParser;

    $fetchCount = 0;
    while ($fetchCount <= $tweetsToFetch) {
      $link = $dataParser->parseNextPageLink();
      $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
      $dataParser->loadHtmlStr($tweetsHtmlData);

      $tweetArray = $dataParser->parseTweetsAndFeatures();
      if (count($tweetArray) == 0) {
        throw new \http\Exception\RuntimeException("Unable to fetch tweets when there should be");
      }

      try {
        $this->pushTweetArray($tweetArray);
      } catch(Exception $e) {
        $this->reopenEntityManager();
        // push remainder
        $fetchCount += $this->insertRemainingTweetsFromLastPage($accountName, $fetchCount, $tweetArray);
        break;
      }

      $fetchCount += count($tweetArray);
    }

    return $fetchCount;
  }

  private function reopenEntityManager() {
    $entityManager = &$this->entityManager;
    $conn = &$this->conn;
    $config = &$this->config;
    try {
      $entityManager = EntityManager::create($conn, $config);
    } catch (Exception $e) {
      throw new RuntimeException("EntityManager instantiation error: $e");
    }
  }
}