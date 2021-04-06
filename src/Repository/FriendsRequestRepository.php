<?php

namespace App\Repository;

use App\Entity\FriendsRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FriendsRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method FriendsRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method FriendsRequest[]    findAll()
 * @method FriendsRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendsRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FriendsRequest::class);
    }

    // /**
    //  * @return Friends[] Returns an array of Friends objects
    //  */
    public function findMyFriends(User $user) :array
    {
        $demands = $this->createQueryBuilder('f')
            ->orWhere('f.sender = :user OR f.receiver = :user')
            ->andWhere('f.accepted = TRUE')
            ->setParameter('user', $user)            
            ->getQuery()
            ->getResult()
        ;
        $friends = [];
        
        foreach ($demands as $demand) {
            $friends[] = $demand->getReceiver() === $user ? $demand->getSender() : $demand->getReceiver();                           
        }
        return $friends;
    }

    /*
    public function findOneBySomeField($value): ?FriendsRequest
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}