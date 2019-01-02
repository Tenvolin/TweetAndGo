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
  // The max tweet limit is limited by Twitter's API themselves; pagination terminates.
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
  private function pushTweetArray($tweetArray)
  {
    $entityManager = &$this->entityManager;
    foreach ($tweetArray as $tweet) {
      $entityManager->persist($tweet);
    }
    $entityManager->flush();
  }


  public function fetchAndPersistTweets($accountName, $tweetsWanted)
  {
    // todo: Fix name schemes here. They're bad.
    $dbTweetsFound = $this->tweetsExistInDbForAccount($accountName);
    $tweetsPersisted = $this->pushPossibleTweets($accountName, $tweetsWanted, $dbTweetsFound);

    $dbTweetsAvailable = $this->dbHasSufficientTweets($accountName, $tweetsWanted);
    if ($dbTweetsFound && $dbTweetsAvailable < 0) {
      $tweetsPersisted += $this->pushPossibleTweets($accountName, abs($dbTweetsAvailable), $dbTweetsFound, true);
    }

    return $tweetsPersisted;
  }

  /**
   * Persists some tweets to DB for some account with no pre-existing records in DB.
   * Returns a count of total possible inserts into DB.
   * @param $accountName
   * @param $tweetsToFetch
   * @param bool $fillFromFront
   * @param bool $fillFromBack
   * @return int
   */
  private function pushPossibleTweets($accountName, $tweetsToFetch, $fillFromFront= false, $fillFromBack = false)
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

    Logger::logIfDebugging("end pushPossibleTweets: $fetchCount");
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
  private function insertRemainingTweetsFromLastPage($accountName, $offset, $lastPageOfTweets)
  {
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

  private function tweetsExistInDbForAccount(String $accountName)
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

  // LOGIC: provides a link that can be used to fetch tweets. These tweets will contain the last tweet itself.
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
   * Returns difference between tweetsWanted and tweetsInDb.
   * Insufficient tweets: [-inf, 0)
   * sufficient tweets: [0, inf]
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
   * Persists some subset of $tweetArray to DB.
   * @param array $tweetArray
   * @return array
   */
  private function pushAndIgnoreConflicts(array $tweetArray)
  {
    $entityManager = &$this->entityManager;

    // Terminate on successful push; otherwise, continue filtering erroneous tweets until empty.
    while (count($tweetArray) != 0) {
      try {
        $this->pushTweetArray($tweetArray);
        break;
      } catch (Exception $e) {
        $offendingTweetId = ErrorParser::parseDuplicateMessageForTweetId($e->getMessage());

        $tweetArray = ErrorParser::filterOutOffendingTweet($tweetArray, $offendingTweetId);

        $entityManager = $this->reopenEntityManager();
      }
    }

    return $tweetArray;
  }

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