<?php

namespace App\Repository;

use App\Entity\Validator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Validator>
 *
 * @method Validator|null find($id, $lockMode = null, $lockVersion = null)
 * @method Validator|null findOneBy(array $criteria, array $orderBy = null)
 * @method Validator[]    findAll()
 * @method Validator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ValidatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Validator::class);
    }
    
   /**
    * @return Validator[] Returns an array of Validator objects
    */
   public function setActiveMultiMailing($value): array
   {
       return $this->createQueryBuilder('v')
           ->set('smtp_status', 'Active')
           ->set('multi_mailing', '1')
           ->andWhere('v.email like :val')
           ->setParameter('val', "%$value%")
           ->getQuery()
           ->getResult()
       ;
   }

    /**
     * @return Validator[] Returns an array of Validator objects
     */
    public function getValidatorList(): array
    {
        return $this->createQueryBuilder('v')
            ->distinct()
            ->select('v.list')
            ->getQuery()
            ->getResult()
        ;
    }
    
//    /**
//     * @return Validator[] Returns an array of Validator objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Validator
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
