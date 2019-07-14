<?php
namespace App\Core;
use Doctrine\ORM\EntityManager;
use App\Core\Parse\DataParser;
use Exception;
use RuntimeException;
use App\Core\Parse\ErrorParser;

class DataPersist
{
  private $entityManager;
  private $conn;
  private $config;
  private $dataParser;
  private $dataFetcher;

  static $TWEET_BATCH = 20;
  // The max tweet limit is limited by Twitter's API themselves; pagination terminates.
  static $MAX_TWEET_LIMIT = 3300;

  /**
   * DataPersist constructor.
   * @param EntityManager $entityManager
   * @param array $conn
   * @param \Doctrine\ORM\Configuration $config
   */
  public function __construct(EntityManager $entityManager, array $conn, \Doctrine\ORM\Configuration $config)
  {
    $this->entityManager = $entityManager;
    $this->conn = $conn;
    $this->config = $config;

    $this->dataParser = new DataParser();
    $this->dataFetcher = new DataFetcher();
  }

  /**
   * PURPOSE: Fetch, parse, and insert tweets into DB. Its purpose is to ensure the DB contains the most recent tweets,
   * and that the DB has a sufficient number of tweets.
   *
   * This is the entry point for all persistence logic. Persistence implies that a DB INSERT operation occurs.
   * Tweets are fetched from $accountName, and then inserted in the DB. Tweets are fetched and inserted in batches
   * (Loading a single page of tweets yields a small number of tweets: ~20.)
   *
   * Collisions will occur as we do not allow insertion of duplicate tweets. e.g. Consider persisting 20 tweets, and then
   * running this function again after the account tweets one more time (totalling 21 tweets); We will fetch the LATEST
   * 20 tweets, and of these 20 tweets, only 1 will be new. A collision will occur and this subset (one tweet in this example)
   * of tweets will not be inserted due to the exception thrown by the ORM. To handle this, we will simply filter out offending
   * tweets as reported by the DBMS and attempt to insert again. Repeat this until all remaining tweets are persisted.
   *
   * NOTE: Certain DBMS's allow for partial insertions where there are duplicates for a set of data (Not always implemented).
   * However, ORMs are designed to be as general as possible, and does not allow us to "insert" only a subset of tweet entities
   * that do not collide with existing tweets in the DB. As a result, we are forced to make use of exceptions to filter
   * offending tweets out of a batch before inserting.
   *
   * @param String $accountName
   * @param int $tweetsWanted
   * @return int The number of tweets persisted to DB.
   */
  public function fetchAndPersistTweets(String $accountName, int $tweetsWanted)
  {
    // Start fetching tweets, and stop when the first DB_insert collision occurs; stopping means we have updated all
    //    new tweets for this account.
    $dbTweetsFound = $this->dbHasTweets($accountName);
    $tweetsPersisted = $this->fetchParseAndForceInsertTweets($accountName, $tweetsWanted, $dbTweetsFound);

    $dbNumberTweetsAvailable = $this->dbHasSufficientTweets($accountName, $tweetsWanted);
    if ($dbTweetsFound && $dbNumberTweetsAvailable < 0) {
      // However, insufficient # of tweets exist in the DB. Start fetching tweets from the oldest dated tweet.
      $tweetsPersisted += $this->fetchParseAndForceInsertTweets($accountName, abs($dbNumberTweetsAvailable), $dbTweetsFound, true);
    }

    return $tweetsPersisted;
  }

  /**
   * PURPOSE: Persists some tweets to DB for some account with no pre-existing records in DB.
   *
   * @param $accountName
   * @param $tweetsToFetch
   * @param bool $fillFromFront
   * @param bool $fillFromBack
   * @return int A count of total possible inserts into DB.
   */
  private function fetchParseAndForceInsertTweets(String $accountName, int $tweetsToFetch, bool $fillFromFront= false, bool $fillFromBack = false)
  {
    $dataFetcher = &$this->dataFetcher;
    $dataParser = &$this->dataParser;

    if ($fillFromFront && !$fillFromBack) {
      $tweetsToFetch = self::$MAX_TWEET_LIMIT;
    }
    if(!$fillFromFront && $fillFromBack) {
      throw new \http\Exception\RuntimeException("Improper use of pushPossibleTweets() flags");
    }

    $fetchCount = 0;
    $firstPage = true;
    while ($fetchCount < $tweetsToFetch) {
      $link = ($fillFromBack && $firstPage) ? $this->getNextPageLinkFromLastTweet($accountName) : $dataParser->parseNextPageLink();

      $tweetsHtmlData = $dataFetcher->delayedFetch($accountName, $link);
      $dataParser->loadHtmlStr($tweetsHtmlData);

      $tweetArray = $dataParser->parseTweetsAndFeatures();
      if (count($tweetArray) == 0) {
        // end of parsing.
        break;
      }

      $pushedArray = $this->pushAndIgnoreConflicts($tweetArray);
      if (count($pushedArray) == 0 ||
        (mb_strlen($link) <= 0 && !$firstPage)) {
        // End of parsing.
        break;
      }

      $fetchCount += count($pushedArray);
      $firstPage = false;
    }

    $this->dataParser = new DataParser(); // TODO: rewrite this functionality.
    Logger::logIfDebugging("end pushPossibleTweets: $fetchCount");
    return $fetchCount;
  }

  /**
   * PURPOSE: Persists some subset of $tweetArray to DB.
   * Terminate on successful push; otherwise, continue filtering duplicate tweets until empty.
   * @param array $tweetArray
   * @return array
   */
  private function pushAndIgnoreConflicts(array $tweetArray)
  {
    $entityManager = &$this->entityManager;

    while (count($tweetArray) != 0) {
      try {
        $this->dbInsertTweetArray($tweetArray);
        break;
      } catch (Exception $e) {
        $offendingTweetId = ErrorParser::parseDuplicateMessageForTweetId($e->getMessage());

        $tweetArray = ErrorParser::filterOutOffendingTweet($tweetArray, $offendingTweetId);

        $entityManager = $this->reopenEntityManager();
      }
    }

    return $tweetArray;
  }

  /**
   * PURPOSE: Provides a link that can be used to fetch tweets. These tweets will contain the last tweet itself.
   * @param String $accountName
   * @return string
   */
  private function getNextPageLinkFromLastTweet(String $accountName)
  {
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
   * PURPOSE: Attempt to insert an array of Tweet entities.
   * @param array $tweetArray
   * @throws \Doctrine\ORM\ORMException
   * @throws \Doctrine\ORM\OptimisticLockException
   */
  private function dbInsertTweetArray(array $tweetArray)
  {
    $entityManager = &$this->entityManager;
    foreach ($tweetArray as $tweet) {
      $entityManager->persist($tweet);
    }
    $entityManager->flush();
  }

  /**
   * @param String $accountName
   * @return bool
   */
  private function dbHasTweets(String $accountName)
  {
    $entityManager = $this->entityManager;

    $qb = $entityManager->createQueryBuilder();
    $qb->select('count(t)')
      ->from('Tweet', 't')
      ->where("t.author = '$accountName'");

    $query = $qb->getQuery();

    $result = $query->getScalarResult()[0][1];
    return $result > 0;
  }

  /**
   * PURPOSE: Returns difference between tweetsWanted and tweetsInDb.
   *  Insufficient tweets: [-inf, 0)
   *  sufficient tweets: [0, inf]
   * @param $accountName
   * @param $tweetsWanted
   * @return int
   */
  private function dbHasSufficientTweets(String $accountName, int $tweetsWanted)
  {
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
   * PURPOSE: Doctrine ORM closes the EntityManager on failure; reopening is necessary.
   * @return EntityManager
   */
  private function reopenEntityManager()
  {
    $entityManager = &$this->entityManager;
    $conn = &$this->conn;
    $config = &$this->config;
    try {
      $entityManager = EntityManager::create($conn, $config);
    } catch (Exception $e) {
      throw new RuntimeException("EntityManager instantiation error: $e");
    }
    return $entityManager;
  }
}