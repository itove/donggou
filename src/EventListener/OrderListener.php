<?php
/**
 * vim:ft=php et ts=4 sts=4
 * @version
 * @todo
 */

namespace App\EventListener;

use Symfony\Component\PasswordHasher\Hasher\NodePasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Order;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

// #[AsEntityListener(event: Events::prePersist, entity: Node::class)]
// #[AsEntityListener(event: Events::postPersist, entity: Node::class)]
class OrderListener extends AbstractController
{
    // public function prePersist(Node $node, LifecycleEventArgs $event): void
    // {
    //     $em = $event->getEntityManager();
    //     foreach ($node->getRegions() as $region) {
    //     }
    // }
}
