<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Conf;
use App\Service\Wx;

class SecurityController extends AbstractController
{
    private $doctrine;
    
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $conf = $this->doctrine->getRepository(Conf::class)->find(1);
        
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        // return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
        return $this->render('@EasyAdmin/page/login.html.twig', [
          'last_username' => $lastUsername,
          'error' => $error,
          // 'page_title' => $conf->getSiteName(),
          'csrf_token_intention' => 'authenticate',
          'target_path' => $this->generateUrl('admin'),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/api/wxlogin', name: 'api_wx_login', methods: ['POST'])]
    public function wxLogin(Request $request)
    {
        $data = json_decode($request->getContent());
        $code = $data->code;
        $openid = $this->wx->getOpenid($code);

        $user = $this->doctrine->getRepository(User::class)->findOneBy(['openid' => $openid]);
        $em = $this->doctrine->getManager();
        if (is_null($user)) {
            // create
            $user = new User();
            $user->setOpenid($openid);
            if (isset($data->referrerId)) {
                $referrer = $this->doctrine->getRepository(User::class)->find($data->referrerId);
                if ($referrer) {
                    $user->setReferrer($referrer);
                }
            }
            $em->persist($user);
            $em->flush();
        }
        
        $resp = [
            "id" => $user->getId(),
            "roles" => $user->getRoles(),
            "name" => $user->getName(),
            "phone" => $user->getPhone(),
            "firm" => $user->getFirm(),
        ];
        return $this->json($resp);
    }
}
