<?php

namespace App\Repository;

use App\Entity\CommentsVotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method CommentsVotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method CommentsVotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method CommentsVotes[]    findAll()
 * @method CommentsVotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentsVotesRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CommentsVotes::class);
    }

//    /**
//     * @return CommentsVotes[] Returns an array of CommentsVotes objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CommentsVotes
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
