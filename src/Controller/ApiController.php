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
use App\Entity\Check;
use App\Entity\Refund;
use App\Service\WxPay;
use App\Service\Wx;

#[Route('/api')]
class ApiController extends AbstractController
{
    private $data;
    private $wxpay;
    private $wx;

    public function __construct(Data $data, WxPay $wxpay, Wx $wx)
    {
        $this->data = $data;
        $this->wxpay = $wxpay;
        $this->wx = $wx;
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
            'sn' => $order->getSn(),
            'node' => $order->getNode()->getId(),
            'consumer' => $order->getConsumer()->getId(),
            'quantity' => $order->getQuantity(),
            'price' => $order->getPrice(),
            'amount' => $order->getAmount(),
            'createdAt' => $order->getCreatedAt(),
            'paidAt' => $order->getPaidAt(),
            'usedAt' => $order->getUsedAt(),
            'cancelledAt' => $order->getCancelledAt(),
            'refundedAt' => $order->getRefundedAt(),
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
                'sn' => $order->getSn(),
                'node' => $order->getNode()->getId(),
                'consumer' => $order->getConsumer()->getId(),
                'quantity' => $order->getQuantity(),
                'price' => $order->getPrice(),
                'amount' => $order->getAmount(),
                'createdAt' => $order->getCreatedAt(),
                'paidAt' => $order->getPaidAt(),
                'usedAt' => $order->getUsedAt(),
                'cancelledAt' => $order->getCancelledAt(),
                'refundedAt' => $order->getRefundedAt(),
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

    // #[Route('/orders/{oid}', requirements: ['oid' => '\d+'], methods: ['PATCH'])]
    // public function updateOrder(int $oid, Request $request): Response
    // {
    //     $data = $request->toArray();
    //     $status = $data['status'];

    //     $em = $this->data->getEntityManager();

    //     $order = $em->getRepository(Order::class)->find($oid);

    //     $order->setStatus($status);
    //     
    //     $em->flush();

    //     return $this->json(['code' => 0]);
    // }

    #[Route('/orders/cancel', methods: ['POST'])]
    public function cancelOrder(Request $request): Response
    {
        $data = $request->toArray();
        $oid = $data['oid'];

        $em = $this->data->getEntityManager();

        $order = $em->getRepository(Order::class)->find($oid);

        $order->setStatus(4);
        
        $em->flush();

        return $this->json(['code' => 0]);
    }

    #[Route('/orders/refund', methods: ['POST'])]
    public function refund(Request $request): Response
    {
        $data = $request->toArray();
        $oid = $data['oid'];
        $reason = $data['reason'];

        $em = $this->data->getEntityManager();

        $order = $em->getRepository(Order::class)->find($oid);

        $order->setStatus(5);

        $refund = new Refund();
        $refund->setOrd($order);
        $refund->setReason($reason);
        $em->persist($refund);
        
        $em->flush();

        return $this->json(['code' => 0]);
    }

    #[Route('/orders/check', methods: ['POST'])]
    public function checkOrder(Request $request): Response
    {
        $data = $request->toArray();
        $oid = $data['oid'];
        $uid = $data['uid'];

        $em = $this->data->getEntityManager();

        $order = $em->getRepository(Order::class)->find($oid);
        $checker = $em->getRepository(User::class)->find($uid);

        $order->setStatus(3);

        $em = $this->data->getEntityManager();

        $check = new Check();
        $check->setOrd($order);
        $check->setChecker($checker);
        $em->persist($check);
        
        $em->flush();
        
        return $this->json(['code' => 0]);
    }

    #[Route('/wx/pay/notify')]
    public function wxPayNotify(Request $request): Response
    {
        $data = $request->toArray();
        
        $sig = $request->headers->get('wechatpay-signature');
        $sigType = $request->headers->get('wechatpay-signature-type');
        // $this->wxpay->verifySignature();

        $resource = $data['resource'];
        
        $json = $this->wxpay->decode($resource['ciphertext'], $resource['nonce'], $resource['associated_data']);

        $res = json_decode($json, true);

        if ($res['trade_state'] === 'SUCCESS') {
            $order = $this->data->getOrderBySn($res['out_trade_no']);
            $order->setWxTransId($res['transaction_id']);
            $order->setBankType($res['bank_type']);
            $order->setStatus(2);
            $em = $this->data->getEntityManager();
            $em->flush();
        }
        
        return $this->json(['code' => 'SUCCESS', 'message' => 'OK']);
    }

    #[Route('/wx/pay/{oid}',  requirements: ['oid' => '\d+'], methods: ['POST'])]
    public function payWithWx(int $oid): Response
    {
        $order = $this->data->getOrder($oid);

        $package = 'prepay_id=' . $order->getWxPrepayId();
        $timestamp = time();
        $nonce = md5(uniqid());
        $msg = $this->wx->getAppid() . "\n".
            $timestamp . "\n" .
            $nonce . "\n" .
            $package . "\n";

        $paySign = $this->wxpay->genSign($msg);
        
        $data = [
            "timeStamp" => "$timestamp",
            "nonceStr" => $nonce,
            "package" => $package,
            "signType" => 'RSA',
            "paySign" => $paySign,
            'oid' => $order->getId(),
        ];

        return $this->json(["code" => 0, "data" => $data]);
    }

    /**
     * @test curl -k -d '{"nid": 100,"uid":7, "quantity": 1}' https://127.0.0.1:8000/api/wx/pay/prepay
     */
    #[Route('/wx/pay/prepay', methods: ['POST'])]
    public function getWxPrepay(Request $request): Response
    {
        $data = $request->toArray();
        $nid = $data['nid'];
        $uid = $data['uid'];
        $quantity = $data['quantity'];

        $em = $this->data->getEntityManager();

        $user = $em->getRepository(User::class)->find($uid);
        $node = $this->data->getNode($nid);
        $amount = $node->getPrice() * $quantity;
        $amount = 1;

        $order = new Order();
        $order->setNode($node);
        $order->setConsumer($user);
        $order->setQuantity($quantity);
        $order->setPrice($node->getPrice());
        $order->setAmount($amount);
        
        $notify_url = 'https://' . $request->server->get('HTTP_HOST') . '/api/wx/pay/notify';
        $resp = $this->wxpay->prepay($order->getSn(), $order->getNode()->getTitle(), $order->getAmount(), $order->getConsumer()->getOpenid(), $notify_url);

        $order->setWxPrepayId($resp['prepay_id']);
        $em->persist($order);
        $em->flush();
        dump($order->getId());

        return $this->payWithWx($order->getId());
    }

    #[Route('/wx/pay/cerlist', methods: ['GET'])]
    public function getWXCertList(Request $request): Response
    {
        $this->wxpay->getWXCertList();

        return $this->json(["code" => 0]);
    }

    #[Route('/test/decode', methods: ['GET'])]
    public function testDecode(Request $request): Response
    {
        // dump(openssl_get_cipher_methods());
        $k = $_ENV['WXPAY_APIV3_SECRET'];
        dump($k);
        $a = 'AEAD_AES_256_GCM';
        $a = 'aes-256-gcm';
        $c = '8335/RHgOHJSAuz4INX1t2hdBC3dkaGDqxm8OyXM9aNThu6AVDTq0galc1ODyEL6UXhdaqyqE3sbdmSgEUAzoFwirPYV9iIchxOBngMZFFQnHlz7T0H3w3o3Ju574wkbyC2+e5gCbA8iPSHC6YF76sT+VH8ur35YgNwZT9VgEbzsba2pqZaavJLgO5iYzDWEIjMQ0us0+/rUCjhrKt4FManN7LxXLMXq+qlbqru8v+QOuAn6oXKw/Nt0E/kpOE9ah6c2724CFX8fntVu5R38/QABCmPa3l8KgmViVPMyEGy2XXi22KUP2k3svLOr0vl/NB7xoXeVAVJiWYLJEZ24POuEblSNhiJV2s6nhRpR7EQ+EnxuIwqyWhGzUYAukWfxbUSW/DiEoNGdsI+wz+DyR6IsE2AD0nOVzlikdN/Pmr2tljsCtm/ewW6ursc6QA5MLQjZRrrOg+Z18oldU+qhW/+wzaemKF0guOvQsNiWCGYD8VUp/w35z30rCFKkGZacWD/Jr6bZL0I3NjWrzw9uAGQT7SbVqspYMxKz4+Qf9UWb8lkygHtBTG/JHabn1l8ekmyOQKfYrwk=';
        $c1 = base64_decode($c);
        $c2 = substr($c1, 0, -16);
        $authTag = substr($c1, -16);
        dump($c1);
        dump($c2);
        dump($authTag);
        $n = 'JGEF5qEBRuwM';
        $associated_data = 'transaction';
        $t = openssl_decrypt($c2, $a, $k, OPENSSL_RAW_DATA, $n, $authTag, $associated_data);
        dump($t);
    }
}
