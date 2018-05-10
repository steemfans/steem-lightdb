<?php

namespace App\Repository;

use App\Entity\PostsVotes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method PostsVotes|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostsVotes|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostsVotes[]    findAll()
 * @method PostsVotes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostsVotesRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PostsVotes::class);
    }

//    /**
//     * @return PostsVotes[] Returns an array of PostsVotes objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PostsVotes
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
