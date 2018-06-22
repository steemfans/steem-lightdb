<?php

namespace App\Repository;

use App\Entity\MultiTasks;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method MultiTasks|null find($id, $lockMode = null, $lockVersion = null)
 * @method MultiTasks|null findOneBy(array $criteria, array $orderBy = null)
 * @method MultiTasks[]    findAll()
 * @method MultiTasks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MultiTasksRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, MultiTasks::class);
    }

//    /**
//     * @return MultiTasks[] Returns an array of MultiTasks objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MultiTasks
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
