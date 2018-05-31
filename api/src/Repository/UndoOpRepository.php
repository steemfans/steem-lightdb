<?php

namespace App\Repository;

use App\Entity\UndoOp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method UndoOp|null find($id, $lockMode = null, $lockVersion = null)
 * @method UndoOp|null findOneBy(array $criteria, array $orderBy = null)
 * @method UndoOp[]    findAll()
 * @method UndoOp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UndoOpRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UndoOp::class);
    }

//    /**
//     * @return UndoOp[] Returns an array of UndoOp objects
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
    public function findOneBySomeField($value): ?UndoOp
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
