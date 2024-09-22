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
use App\Entity\Check;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

// #[AsEntityListener(event: Events::prePersist, entity: Node::class)]
// #[AsEntityListener(event: Events::postPersist, entity: Node::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Order::class)]
class OrderListener extends AbstractController
{
    // public function prePersist(Node $node, LifecycleEventArgs $event): void
    // {
    //     $em = $event->getEntityManager();
    //     foreach ($node->getRegions() as $region) {
    //     }
    // }
    
    public function preUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        if ($event->hasChangedField('status')) {
            $datetime = new \DateTimeImmutable;

            if($order->getStatus() === 2) {
                $order->setPaidAt($datetime);
            }
            if($order->getStatus() === 3) {
                $order->setUsedAt($datetime);
            }
            if($order->getStatus() === 4) {
                $order->setCancelledAt($datetime);
            }
            if($order->getStatus() === 5) {
                $order->setRefundedAt($datetime);
            }
            if($order->getStatus() === 6) {
                $order->setDeletedAt($datetime);
            }
        }
    }
}
