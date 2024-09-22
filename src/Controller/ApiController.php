<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\Data;
use App\Entity\Feedback;
use App\Entity\User;
use App\Entity\Node;
use App\Entity\Order;

#[Route('/api')]
class ApiController extends AbstractController
{
    private $data;

    public function __construct(Data $data)
    {
        $this->data = $data;
    }

    #[Route('/media_objects', methods: ['POST'])]
    public function upload(Request $request): Response
    {
        $uid = $request->request->get('uid');
        $file = $request->files->get('upload');
        $newName = uniqid() . '-' .  $file->getClientOriginalName();
        // copy($file->getPathname(), 'images/' . $newName);
        $file->move('images/', $newName);
        if ($uid !== null) {
            $em = $this->data->getEntityManager();
            $user = $em->getRepository(User::class)->find($uid);
            $user->setAvatar('images/' . $newName);
            $em->flush();
        }
        return $this->json(['url' => '/images/' . $newName]);
    }
    
    #[Route('/nodes/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getNode(int $id): Response
    {
        $node = $this->data->getNode($id);
        $data = $this->data->formatNode($node);

        return $this->json($data);
    }

    #[Route('/users/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function _getUser(int $id): Response
    {
        $em = $this->data->getEntityManager();
        $user = $em->getRepository(User::class)->find($id);
        $data = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'phone' => $user->getPhone(),
            'avatar' => $user->getAvatar(),
            'roles' => $user->getRoles(),
        ];
        return $this->json($data);
    }

    #[Route('/users/{id}', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function _updateUser(int $id, Request $request): Response
    {
        $arr = $request->toArray();
        $em = $this->data->getEntityManager();
        $user = $em->getRepository(User::class)->find($id);
        
        foreach($arr as $k => $v) {
            $setter = 'set' . ucwords($k);
            $user->$setter($v);
        }
        $em->flush();
        
        $data = [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'phone' => $user->getPhone(),
            'avatar' => $user->getAvatar(),
        ];
        return $this->json($data);
    }

    #[Route('/nodes/{regionLabel}', methods: ['GET'])]
    public function getNodesByRegion(string $regionLabel): Response
    {
        $nodes = $this->data->findNodesByRegionLabel($regionLabel, null);
        $region = $this->data->getRegionByLabel($regionLabel);
        $i = 0;
        $data['region'] = $region->getName();
        $data['nodes'] = [];
        foreach ($nodes as $n) {
            $data['nodes'][$i]['title'] = $n->getTitle();
            $data['nodes'][$i]['summary'] = $n->getSummary();
            $data['nodes'][$i]['image'] = $n->getImage();
            $data['nodes'][$i]['id'] = $n->getId();
            $i++;
        }

        return $this->json($data);
    }

    #[Route('/fav', methods: ['GET'])]
    public function getUserFav(Request $request): Response
    {
        $regionLabel = $request->query->get('region');
        $uid = $request->query->get('uid');

        $em = $this->data->getEntityManager();
        $user = $em->getRepository(User::class)->find($uid);
        $fav = $user->getFav();
        $region = $this->data->getRegionByLabel($regionLabel);
        
        $i = 0;
        $data['region'] = $region->getName();
        $data['nodes'] = [];
        foreach ($fav as $n) {
            if ($n->getRegions()->contains($region)) {
                $data['nodes'][$i]['title'] = $n->getTitle();
                $data['nodes'][$i]['summary'] = $n->getSummary();
                $data['nodes'][$i]['image'] = $n->getImage();
                $data['nodes'][$i]['id'] = $n->getId();
                $i++;
            }
        }

        return $this->json($data);
    }

    #[Route('/wx/home', methods: ['GET'])]
    public function wxHome(): Response
    {
        $list = ['slider', 'youzai', 'zhuzai', 'chizai', 'gouzai', 'tongzhi', 'location', 'jianjie', 'hongsetext', 'historytext'];

        foreach ($list as $l) {
            $nodes = $this->data->findNodesByRegionLabel($l, null, 5);
            $i = 0;
            $a = [];
            foreach ($nodes as $n) {
                $a[$i]['title'] = $n->getTitle();
                $a[$i]['summary'] = $n->getSummary();
                $a[$i]['image'] = $n->getImage();
                $a[$i]['id'] = $n->getId();
                $i++;
            }
            $data[$l] = $a;
        }

        return $this->json($data);
    }

    #[Route('/wx/leyou', methods: ['GET'])]
    public function wxLeyou(): Response
    {
        $conf = $this->data->findConfByLocale(null);
        $list = ['youzai', 'zhuzai', 'chizai', 'gouzai'];

        foreach ($list as $l) {
            $nodes = $this->data->findNodesByRegionLabel($l, null, 5);
            $i = 0;
            $a = [];
            foreach ($nodes as $n) {
                $tags = [];
                foreach ($n->getTags() as $t) {
                    array_push($tags, $t->getName());
                }
                
                // mv important_tags to first
                $important_tags = ['民宿', '农家乐'];
                foreach ($important_tags as $it) {
                    $index = array_search($it, $tags);
                    if ($index !== false) {
                        unset($tags[$index]);
                        array_unshift($tags, $it);
                    }
                }
                
                $a[$i]['title'] = $n->getTitle();
                $a[$i]['summary'] = $n->getSummary();
                $a[$i]['image'] = $n->getImage();
                $a[$i]['id'] = $n->getId();
                $a[$i]['phone'] = $n->getPhone() ? $n->getPhone() : $conf->getPhone();
                $a[$i]['tags'] = $tags;
                $i++;
            }
            $data[$l] = $a;
        }

        return $this->json($data);
    }

    #[Route('/feedback', methods: ['POST'])]
    public function feedback(Request $request): Response
    {
        $params = $request->toArray();
        $firstname = $params['firstname'];
        // $lastname = $params['lastname'];
        $phone = $params['phone'];
        $email = $params['email'];
        $title = $params['title'];
        $body = $params['body'];
        // $country = $params['country'];
        
        $em = $this->data->getEntityManager();
        $f = new Feedback();
        $f->setFirstname($firstname);
        // $f->setLastname($lastname);
        $f->setPhone($phone);
        $f->setEmail($email);
        $f->setTitle($title);
        $f->setBody($body);
        // $f->setCountry($country);
        $em->persist($f);
        $em->flush();

        $data = [
            'code' => 0,
            'msg' => 'ok',
        ];
        return $this->json($data);
    }

    #[Route(path: '/wx/getphone', name: 'api_wx_getphone', methods: ['POST'])]
    public function wxLogin(Request $request)
    {
        $data = json_decode($request->getContent());
        $code = $data->code;
        $resp = $this->wx->getPhoneNumber($code);
        return $this->json($resp);
    }

    #[Route('/isfav', methods: ['GET'])]
    public function getIsFav(Request $request): Response
    {
        $nid = $request->query->get('nid');
        $uid = $request->query->get('uid');

        $em = $this->data->getEntityManager();
        $user = $em->getRepository(User::class)->find($uid);
        $node = $this->data->getNode($nid);

        $isFav = false;
        if ($user->getFav()->contains($node)) {
            $isFav = true;
        }
        
        return $this->json(['isFav' => $isFav]);
    }

    #[Route('/fav/add', methods: ['POST'])]
    public function addFav(Request $request): Response
    {
        $data = $request->toArray();
        $nid = $data['nid'];
        $uid = $data['uid'];

        $em = $this->data->getEntityManager();
        $user = $em->getRepository(User::class)->find($uid);
        $node = $this->data->getNode($nid);
        
        $user->addFav($node);
        
        $em->flush();

        return $this->json(['isFav' => true]);
    }

    #[Route('/fav/remove', methods: ['POST'])]
    public function removeFav(Request $request): Response
    {
        $data = $request->toArray();
        $nid = $data['nid'];
        $uid = $data['uid'];

        $em = $this->data->getEntityManager();
        $user = $em->getRepository(User::class)->find($uid);
        $node = $this->data->getNode($nid);
        
        $user->removeFav($node);

        $em->flush();

        return $this->json(['isFav' => true]);
    }

    #[Route('/map/markers', methods: ['GET'])]
    public function getMapMarkers(): Response
    {
        $em = $this->data->getEntityManager();
        $nodes = $em->getRepository(Node::class)->findHaveLatLong();
        $data = [];
        foreach($nodes as $n) {
            array_push($data, $this->data->formatNode($n));
        }
        
        return $this->json($data);
    }

    #[Route('/wx/feedback', methods: ['GET'])]
    public function getWxFeedback(): Response
    {
        $nodes = $this->data->findNodesByRegionLabel('feedback', null);
        $node = $this->data->formatNode($nodes[0]);
        
        return $this->json($node);
    }

    #[Route('/orders/{oid}', methods: ['GET'], requirements: ['oid' => '\d+'])]
    public function getOrder(int $oid): Response
    {
        $em = $this->data->getEntityManager();

        $order = $em->getRepository(Order::class)->find($oid);

        $data = [
            'id' => $order->getId(),
            'node' => $order->getNode()->getId(),
            'consumer' => $order->getConsumer()->getId(),
            'quantity' => $order->getQuantity(),
            'price' => $order->getPrice(),
            'amount' => $order->getAmount(),
            'createdAt' => $order->getCreatedAt(),
            'paidAt' => $order->getPaidAt(),
            'usedAt' => $order->getUsedAt(),
            'status' => $order->getStatus(),
            'node' => $this->data->formatNode($order->getNode()),
        ];

        return $this->json($data);
    }

    #[Route('/orders', methods: ['GET'])]
    public function listOrders(Request $request): Response
    {
        $uid = $request->query->get('uid');

        $em = $this->data->getEntityManager();
        $user = $em->getRepository(User::class)->find($uid);
        $orders = $em->getRepository(Order::class)->findBy(['consumer' => $uid]);
        
        $data = [];
        
        foreach ($orders as $order) {
            array_push($data, [
                'id' => $order->getId(),
                'node' => $order->getNode()->getId(),
                'consumer' => $order->getConsumer()->getId(),
                'quantity' => $order->getQuantity(),
                'price' => $order->getPrice(),
                'amount' => $order->getAmount(),
                'createdAt' => $order->getCreatedAt(),
                'paidAt' => $order->getPaidAt(),
                'usedAt' => $order->getUsedAt(),
                'status' => $order->getStatus(),
                'node' => $this->data->formatNode($order->getNode()),
            ]);
        }

        return $this->json($data);
    }

    #[Route('/orders', methods: ['POST'])]
    public function createOrder(Request $request): Response
    {
        $data = $request->toArray();
        $nid = $data['nid'];
        $uid = $data['uid'];
        $quantity = $data['quantity'];

        $em = $this->data->getEntityManager();

        $user = $em->getRepository(User::class)->find($uid);
        $node = $this->data->getNode($nid);
        $amount = $node->getPrice() * $quantity;

        $order = new Order();
        $order->setNode($node);
        $order->setConsumer($user);
        $order->setQuantity($quantity);
        $order->setPrice($node->getPrice());
        $order->setAmount($amount);
        $em->persist($order);
        
        $em->flush();

        return $this->json(['code' => 0]);
    }

    #[Route('/orders/{oid}', requirements: ['oid' => '\d+'], methods: ['PATCH'])]
    public function updateOrder(int $oid, Request $request): Response
    {
        $data = $request->toArray();
        $status = $data['status'];

        $em = $this->data->getEntityManager();

        $order = $em->getRepository(Order::class)->find($oid);

        $order->setStatus($status);
        
        $em->flush();

        return $this->json(['code' => 0]);
    }
}
