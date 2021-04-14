<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * Permet d'afficher les commandes dans membre de l'utilisateur
     * @author Durand Soline <Solined.independant@php.net>
     * @param User $user
     * @return object QueryBuilder
     * use ds AccountOrderController index()
     *  
     * @version Version20210301074205 : video 61
     * tri et affichage par state   : ->andWhere('o.state > 0')
     */
    public function findSuccessOrders($user)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.isPaid = 1')
            ->andWhere('o.state > 0')       // c'est state qui prend le dessus sur isPAid : ex : isPaid à 1 payée et state à 0 non payée, elle ne sera pas affichée
            ->andWhere('o.user = :user')    //:user  flag de user courant
            ->setParameter('user', $user)   //definit la var correspondant au flag
            ->orderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    /**
     * @return Order[] Returns an array of Order objects
     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Order
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
