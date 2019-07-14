<?php
namespace App\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Core\Resources;

/**
 * Class TweetController
 *
 * @package App\Controller
 */
class TweetController extends AbstractController
{
  public function number()
  {
    /** @var EntityManager $em */
    $em = Resources\DoctrineUtil::getEntityManager();
    $config = Resources\DoctrineUtil::getConfig();
    $conn = Resources\DoctrineUtil::getConnection();
    $qb = $em->createQueryBuilder();
    $qb->select('t')
      ->from('App\Entity\Tweet', 't')
      ->orderBy('t.tweetId', 'ASC')
      ->setMaxResults(1);
    $query = $qb->getQuery();
    $result = $query->getResult();

    return new Response(
      '<html><body>Lucky number: '. 4 .'</body></html>'
    );
  }
}