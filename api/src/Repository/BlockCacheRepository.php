<?php

namespace App\Repository;

use App\Entity\BlockCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method BlockCache|null find($id, $lockMode = null, $lockVersion = null)
 * @method BlockCache|null findOneBy(array $criteria, array $orderBy = null)
 * @method BlockCache[]    findAll()
 * @method BlockCache[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlockCacheRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BlockCache::class);
    }

//    /**
//     * @return BlockCache[] Returns an array of BlockCache objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BlockCache
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
