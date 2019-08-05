<?php
namespace App\Controller;

use App\Core\DataFetcher;
use App\Entity\Tweet;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Core\Resources\DoctrineUtil;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Class TweetController
 *
 * @package App\Controller
 */
class TweetController extends AbstractController
{
  /**
   * @param $tweet Tweet entity
   * @return string JSON format string
   */
  private static function ConvertTweetToJson($tweet): string
  {
    $encoders = [new JsonEncoder()];
    $normalizers = [new DateTimeNormalizer(), new ObjectNormalizer()];
    $serializer = new Serializer($normalizers, $encoders);
    return $serializer->serialize($tweet, 'json');
  }

  /**
   * Query and return a JSON representation of tweets.
   * @return Response
   */
  public function query(string $accountName, int $tweetCount)
  {
    $em = DoctrineUtil::getEntityManager();
    $qb = $em->createQueryBuilder();
    $qb->select('t')
      ->from('App\Entity\Tweet', 't')
      ->orderBy('t.tweetId', 'ASC')
      ->setMaxResults(5);

    $query = $qb->getQuery();
    $result = $query->getResult();

    if (count($result) === 0) {
      // queryDBAccountTable

//      $df = new DataFetcher();
//      $insertIntoDb = $df->delayedFetch($accountName, '', true);
    }
    return new Response(self::ConvertTweetToJson($result));
  }
}