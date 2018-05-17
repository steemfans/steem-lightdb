<?php

namespace App\Repository;

use App\Entity\UserRelations;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method UserRelations|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRelations|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRelations[]    findAll()
 * @method UserRelations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRelationsRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserRelations::class);
    }

//    /**
//     * @return UserRelations[] Returns an array of UserRelations objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserRelations
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
