<?php
/**
 * vim:ft=php et ts=4 sts=4
 * @author Al Zee <z@alz.ee>
 * @version
 * @todo
 */

namespace App\Service;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Conf;
use App\Entity\Node;
use App\Entity\Tag;
use App\Entity\Region;
use App\Entity\Page;
use App\Entity\Language;
use App\Entity\Menu;
use App\Entity\Order;

class Data
{
    private $doctrine;
    
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    public function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
    
    static function GetProperties($entity)
    {
        $reflect = new \ReflectionClass($entity);
        $props   = $reflect->getProperties();
        $arr = [];
        $no_need = ['title'];
        if (!$_ENV['IS_MULTILINGUAL']) {
            array_push($no_need, 'language');
        }

        foreach ($props as $prop) {
            foreach ($prop->getAttributes() as $attrib) {
                // only ORM fields
                if (str_contains($attrib->getName(), 'Doctrine\ORM\Mapping')) {
                    $prop_name = $prop->getName();

                    // Insert space before Capital letters;
                    $name = preg_replace('/(\w+)([A-Z])/U', '\\1 \\2', $prop_name);
                    $name = ucfirst($name);
                    if (!in_array($prop_name, $no_need)) {
                        $arr[$name] = $prop_name;
                    }
                    break;
                }
            }
        }

        return $arr;
    }
    
    public function getPage(string $label)
    {
        return $this->doctrine->getRepository(Page::class)->findOneBy(['label' => $label]);
    }
    
    public function getRegionByLabel(string $label)
    {
        return $this->doctrine->getRepository(Region::class)->findOneBy(['label' => $label]);
    }

    public function getRegion(int $id)
    {
        return $this->doctrine->getRepository(Region::class)->find($id);
    }
    
    public function getNode(int $id)
    {
        return $this->doctrine->getRepository(Node::class)->find($id);
    }

    public function formatOrder(Order $order)
    {
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
            'node' => self::formatNode($order->getNode()),
        ];

        return $data;
    }

    public function formatNode(Node $n)
    {
        $conf = $this->findConfByLocale(null);
        $tags = [];
        foreach ($n->getTags() as $t) {
            array_push($tags, $t->getName());
        }
        $data = [
            'id' => $n->getId(),
            'title' => $n->getTitle(),
            'summary' => $n->getSummary(),
            'tags' => $tags,
            'body' => $n->getBody(),
            'image' => $n->getImage(),
            'audio' => $n->getAudio(),
            'qr' => $n->getQr(),
            'latitude' => $n->getLatitude(),
            'longitude' => $n->getLongitude(),
            'address' => $n->getAddress() ? $n->getAddress() : $conf->getAddress(),
            'phone' => $n->getPhone() ? $n->getPhone() : $conf->getPhone(),
            'price' => $n->getPrice(),
        ];

        if (!empty($n->getRegions())) {
            $data['region'] = $n->getRegions()[0]->getId();
        }
        
        $children = [];
        foreach ($n->getChildren() as $k => $v) {
            $children[$k]['title'] = $v->getTitle();
            $children[$k]['summary'] = $v->getSummary();
            $children[$k]['images'] = $v->getImages();
            $tags = [];
            foreach ($v->getTags() as $t) {
                array_push($tags, $t->getName());
            }
            $children[$k]['tags'] = $tags;

            $images = [];
            foreach ($v->getImages() as $i) {
                array_push($images, $i->getImage());
            }
            $children[$k]['images'] = $images;
        }
        $data['children'] = $children;
        
        return $data;
    }
    
    public function getPrev(Node $node)
    {
        return $this->doctrine->getRepository(Node::class)->findPrev($node);
    }

    public function getNext(Node $node)
    {
        return $this->doctrine->getRepository(Node::class)->findNext($node);
    }
    
    public function getPageContent(string $label, $locale)
    {
        $page = $this->doctrine->getRepository(Page::class)->findOneBy(['label' => $label]);
        $regions = $page->getRegions();
        $data = [];
        foreach ($regions as $r) {
            $dataOfRegion = self::findNodesByRegion($r, $locale, $r->getCount());
            $data[$r->getLabel()] = $dataOfRegion;
        }
        
        $data['path'] = $page->getName();
        
        return array_merge($data, self::getMisc($locale));
        
    }
    
    public function getMisc(string $locale)
    {
        $footer = self::getRegionByLabel('footer');
        $data['footer'] = self::findNodesByRegion($footer, $locale, $footer->getCount());
        $data['video'] = self::findNodesByRegionLabel('video', $locale, 1);
        $data['conf'] = self::findConfByLocale($locale);
        $data['friendLinks'] = self::getMenu('friend');
        $data['footerMenu'] = self::getMenu('footer');

		return $data;
    }
    
    public function getMenu(string $label)
    {
        return $this->doctrine->getRepository(Menu::class)->findOneBy(['label' => $label]);
    }
    
    public function getSome($nid = null)
    {
        $conf = $this->doctrine->getRepository(Conf::class)->find(1);
        $nodeRepo = $this->doctrine->getRepository(Node::class);
        $regions = $this->doctrine->getRepository(Region::class)->findAll();
        $arr = [
            'description' => $conf->getDescription(),
            'keywords' => $conf->getKeywords(),
            'site_name' => $conf->getSitename(),
            'address' => $conf->getAddress(),
            'phone' => $conf->getPhone(),
            'email' => $conf->getEmail(),
        ];
        
        if (!is_null($nid)) {
            $arr['node'] = $this->doctrine->getRepository(Node::class)->find($nid);
        }
        
        foreach($regions as $r ) {
            $limit = $r->getCount();
            if ($limit > 0) {
                $order = 'DESC';
            } else {
                $order = 'ASC';
            }
            if ($limit !== 0) {
                $arr[$r->getLabel()] = $nodeRepo->findBy(['region' => $r], ['id' => $order], abs($limit));
            } else {
                // $arr[$r->getLabel()] = $nodeRepo->findOneBy(['region' => $r]);
            }
        }
        
        return $arr;
    }
    
    public function getNodeByRegion(string $region, $limit = null, $offset = null)
    {
        $nodes = $this->doctrine->getRepository(Node::class)->findByRegion(['region' => $region], $limit, $offset);
        return $nodes;
    }
    
    public function findNodeByRegion(string $region, $limit = null, $offset = null)
    {
        $nodes = $this->doctrine->getRepository(Node::class)->findByRegion(['region' => $region], $limit, $offset);
        return $nodes;
    }
    
    public function getNodeByTag(string $tag, $limit = null, $offset = null)
    {
        $nodes = $this->doctrine->getRepository(Node::class)->findByTag(['tag' => $tag], $limit, $offset);
        return $nodes;
    }
    
    public function getTagByLabel(string $label)
    {
        $tag = $this->doctrine->getRepository(Tag::class)->findOneBy(['label' => $label]);
        return $tag;
    }
   
    public function get($nid, $entity = Node::class)
    {
      return $this->doctrine->getRepository($entity)->find($nid);
    }
    
    public function find($nid, $entity = Node::class)
    {
      return $this->doctrine->getRepository($entity)->find($nid);
    }
    
    public function findNodeByTag(string $tag, $limit = null, $offset = null)
    {
        $nodes = $this->doctrine->getRepository(Node::class)->findByTag($tag, $limit, $offset);
        return $nodes;
    }
    
    public function findNodeByCategory(string $category, $limit = null, $offset = null)
    {
        $nodes = $this->doctrine->getRepository(Node::class)->findByCategory($category, $limit, $offset);
        return $nodes;
    }
    
    public function findNodeByRegionAndLocale($region_label, $locale)
    {
      $language = $this->findOneBy(['locale' => $locale], Language::class);
      $region = $this->findOneBy(['label' => $region_label], Region::class);
      $node = $this->findOneBy(['language' => $language, 'region' => $region]);
        
      return $node;
    }
    
    public function findNodesByRegionAndLocale($region_label, $locale)
    {
      $language = $this->findOneBy(['locale' => $locale], Language::class);
      $region = $this->findOneBy(['label' => $region_label], Region::class);
      $nodes = $this->findBy(['language' => $language, 'region' => $region]);
        
      return $nodes;
    }
    
    public function findNodesByRegion(Region $region, $locale, $limit = null, $offset = null)
    {
      return $this->doctrine->getRepository(Node::class)->findByRegion($region, $locale, $limit, $offset);
    }
    
    public function findNodesByRegionLabel(string $label, $locale, $limit = null, $offset = null)
    {
      return $this->doctrine->getRepository(Node::class)->findByRegionLabel($label, $locale, $limit, $offset);
    }
    
    public function findConfByLocale($locale)
    {
      $language = $this->findOneBy(['locale' => $locale], Language::class);
      $conf = $this->findOneBy(['language' => $language], Conf::class);
        
      return $conf;
    }
    
    public function findBy($criteria, $entity = Node::class)
    {
      return $this->doctrine->getRepository($entity)->findBy($criteria);
    }
    
    public function findOneBy($criteria, $entity = Node::class)
    {
      return $this->doctrine->getRepository($entity)->findOneBy($criteria);
    }
    
    public function findAll($criteria, $entity = Node::class)
    {
      return $this->doctrine->getRepository($entity)->findAll($criteria);
    }
    
    /*
     * Get all site basic infomation in one place, add logic as you need
     */
    public function getInfo(string $locale)
    {
      $conf = $this->findConfByLocale($locale);
      $categories = $this->findAll([], Category::class);
      // $more = $this->findAll([], More::class);
      
      return [
        'conf' => $conf,
        'categories' => $categories,
        // 'more' => $more,
      ];
    }
    
    public function findNodeByCategoryAndRegion($cate_label, $region_label, int $limit)
    {
      return $this->doctrine->getRepository(Node::class)
                            ->findByCategoryAndRegion(['category' => $cate_label, 'region' => $region_label], [], $limit)
                        ;
    }
    
    public function findNodeByCategoryAndTag($cate_label, $tag_label, int $limit)
    {
      return $this->doctrine->getRepository(Node::class)
                            ->findByCategoryAndTag(['category' => $cate_label, 'tag' => $tag_label], [], $limit)
                        ;
    }

    public function getOrder(int $oid)
    {
        return $this->doctrine->getRepository(Order::class)->find($oid);
    }

    public function getOrderBySn(string $sn)
    {
        return $this->doctrine->getRepository(Order::class)->findOneBy(['sn' => $sn]);
    }
}
