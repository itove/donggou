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
use App\Service\WxPay;

// #[AsEntityListener(event: Events::prePersist, entity: Order::class)]
// #[AsEntityListener(event: Events::postPersist, entity: Order::class)]
#[AsEntityListener(event: Events::preUpdate, entity: Order::class)]
class OrderListener extends AbstractController
{
    private $wxpay;

    public function __construct(WxPay $wxpay)
    {
        $this->wxpay = $wxpay;
    }

    // public function prePersist(Order $order, LifecycleEventArgs $event): void
    // {
    // }

    public function postPersist(Order $order, LifecycleEventArgs $event): void
    {
    }
    
    public function preUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        $datetime = new \DateTimeImmutable;

        if ($event->hasChangedField('status')) {

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
        }
        if ($event->hasChangedField('deleted')) {
            $order->setDeletedAt($datetime);
        }
    }
}
