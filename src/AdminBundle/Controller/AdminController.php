<?php

namespace AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use UserBundle\Security\Password;
use UserBundle\Entity\User;
use AdminBundle\Entity\Admin;

class AdminController extends Controller
{
    /**
     * @Route("/admin", name="admin_main")
     */
    public function indexAction(Password $password)
    {

        if ($this->get('session')->get('admin') === null) return $this->render('AdminBundle::admin_enter_form.html.twig');
        else {
            return $this->render('AdminBundle::admin_main.html.twig');
        }
    }

    /**
     * @Route("/adminSignIn")
     */
    public function signInAction(Request $request, Password $password)
    {
        $admins = $this->getDoctrine()
            ->getRepository(Admin::class)
            ->findBy(array(
                'email' => $request->request->get('email')
            ));

        foreach($admins as $admin){

            if ($password->CheckPassword($request->request->get('password'), $admin->getPassword())){
                $this->get('session')->set('admin', $admin);
                return $this->redirectToRoute('admin_main');
                break;
            }
        }

        $this->addFlash(
            'notice',
            'Неправильная пара логин/пароль!'
        );

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/adminLogout")
     */
    public function logoutAction(Request $request)
    {
        $this->get('session')->remove('admin');
        $this->addFlash(
            'notice',
            'Вы успешно вышли из аккаунта!'
        );
        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/adminNewUser")
     */
    public function newUserAction(Request $request, Password $password, \Swift_Mailer $mailer)
    {
        if($request->isMethod('GET')) {
            if ($this->get('session')->get('admin') === null) return $this->render('AdminBundle::admin_enter_form.html.twig');
            else {
                return $this->render('AdminBundle::admin_new_user.html.twig');
            }
        }
        if($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            $user->setLogin($request->request->get('header'));
            $user->setPassword($password->HashPassword($request->request->get('password')));
            $user->setHeader($request->request->get('header'));
            $user->setActivateString('');
            $user->setTempPassword('');
            $user->setIsActivated(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $message = (new \Swift_Message('Администратор зарегистрировал аккаунт для вас на сайте multiprokat.com'))
                ->setFrom('robot@multiprokat.com')
                ->setTo($user->getEmail())
                ->setCc('test.multiprokat@gmail.com')
                ->setBody(
                    $this->renderView(
                        'email/admin_registration.html.twig',
                        array(
                            'header' => $user->getHeader(),
                            'password' => $request->request->get('password'),
                        )
                    ),
                    'text/html'
                );
            $mailer->send($message);
            $this->addFlash(
                'notice',
                'Новый аккаунт успешно создан!'
            );
            return $this->redirectToRoute('admin_main');
        }
    }
}
