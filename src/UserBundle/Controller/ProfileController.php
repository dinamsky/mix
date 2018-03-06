<?php

namespace UserBundle\Controller;

use AppBundle\Controller\MailGunController;
use AppBundle\Entity\Card;
use AppBundle\Foto\FotoUtils;
use AppBundle\Menu\ServiceStat;
use UserBundle\Entity\Blocking;
use UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface as em;
use AppBundle\Menu\MenuCity;
use AppBundle\Menu\MenuGeneralType;
use AppBundle\Menu\MenuMarkModel;
use AppBundle\Menu\MenuSubFieldAjax;
use AppBundle\SubFields\SubFieldUtils;
use UserBundle\Entity\UserInfo;
use UserBundle\Security\Password;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use UserBundle\UserBundle;

class ProfileController extends Controller
{

    /**
     * @Route("/user/cards", name="user_cards")
     */
    public function userCardsAction(EntityManagerInterface $em, Request $request, ServiceStat $stat)
    {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($this->get('session')->get('logged_user')->getId());

        if(!$user->getIsBanned()) {
            $query = $em->createQuery('SELECT c FROM AppBundle:Card c WHERE c.userId = ?1');
            $query->setParameter(1, $this->get('session')->get('logged_user')->getId());
            $cards = $query->getResult();


            $city = $this->get('session')->get('city');
            $in_city = $city->getUrl();





            $stat_arr = [
                'url' => $request->getPathInfo(),
                'event_type' => 'visit',
                'page_type' => 'cabinet',
                'user_id' => $user->getId(),
            ];
            $stat->setStat($stat_arr);

            $query = $em->createQuery('SELECT g FROM AppBundle:GeneralType g WHERE g.total !=0 ORDER BY g.total DESC');
            $generalTypes = $query->getResult();

            return $this->render('user/user_cards.html.twig', [
                'share' => true,
                'cards' => $cards,
                'city' => $city,

                'in_city' => $in_city,
                'cityId' => $city->getId(),
                'generalTypes' => $generalTypes,
                'lang' => $_SERVER['LANG'],

            ]);
        } else return new Response("",404);
    }

    /**
     * @Route("/user/messages", name="user_messages")
     */
    public function userMessagesAction(EntityManagerInterface $em, Request $request, ServiceStat $stat)
    {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($this->get('session')->get('logged_user')->getId());

        if(!$user->getIsBanned()) {


            $city = $this->get('session')->get('city');
            $in_city = $city->getUrl();
            $query = $em->createQuery('SELECT g FROM AppBundle:GeneralType g WHERE g.total !=0 ORDER BY g.total DESC');
            $generalTypes = $query->getResult();

            $m_users = $users = [];
            $query = $em->createQuery('SELECT m FROM UserBundle:Message m WHERE m.fromUserId = ?1 OR m.toUserId = ?1 ORDER BY m.dateCreate ASC');
            $query->setParameter(1, $user->getId());
            $msgs = $query->getResult();


            $res = [];$cards = [];$blockings = [];$blockme = [];
            foreach ($msgs as $m){
                $m_users[$m->getFromUserId()] = 1;
                $m_users[$m->getToUserId()] = 1;

                if($m->getFromUserId() != $user->getId()) $chat_visitor_id = $m->getFromUserId();
                else $chat_visitor_id = $m->getToUserId();
                $res[$chat_visitor_id][$m->getCardId()][] = $m;

                $cards[$m->getCardId()] = $this->getDoctrine()
                    ->getRepository(Card::class)
                    ->find($m->getCardId());

            }

            foreach($m_users as $u=>$v){

                $u_object = $this->getDoctrine()
                    ->getRepository(User::class)
                    ->find($u);

                $u_object->user_foto = false;

                foreach ($u_object->getInformation() as $info) {
                    if ($info->getUiKey() == 'foto' and $info->getUiValue() != '') $u_object->user_foto = '/assets/images/users/t/' . $info->getUiValue() . '.jpg';
                }

                $users[$u] = $u_object;

                $blockings[$u] = $this->getDoctrine()
                    ->getRepository(Blocking::class)
                    ->findBy([
                        'userId' => $this->get('session')->get('logged_user')->getId(),
                        'visitorId' => $u
                    ]);

                $blockme[$u] = $this->getDoctrine()
                    ->getRepository(Blocking::class)
                    ->findBy([
                        'userId' => $u,
                        'visitorId' => $this->get('session')->get('logged_user')->getId()
                    ]);

            }




            return $this->render('user/user_messages.html.twig', [
                'city' => $city,
                'in_city' => $in_city,
                'cityId' => $city->getId(),
                'generalTypes' => $generalTypes,
                'lang' => $_SERVER['LANG'],


                'messages' => $res,
                'users' => $users,
                'cards' => $cards,
                'blockings' => $blockings,
                'blockme' => $blockme

            ]);
        } else return new Response("",404);
    }

    /**
     * @Route("/user/delete_blocking_user_messages", name="delete_blocking_user_messages")
     */
    public function duUserMessagesAction(EntityManagerInterface $em, Request $request)
    {
        $id = $request->request->get('id');
        $action = $request->request->get('user_action');

        if($action=='delete'){
            $query = $em->createQuery('DELETE UserBundle:Message m WHERE (m.fromUserId = ?1 AND m.toUserId = ?2) OR (m.fromUserId = ?2 AND m.toUserId = ?1)');
            $query->setParameter(1, $id);
            $query->setParameter(2, $this->get('session')->get('logged_user')->getId());
            $query->execute();
        }

        if($action=='block'){
            $blocking = new Blocking();
            $blocking->setUserId($this->get('session')->get('logged_user')->getId());
            $blocking->setVisitorId($id);
            $em->persist($blocking);
            $em->flush();
        }

        if($action=='unblock'){
            $blocking = $this->getDoctrine()
            ->getRepository(Blocking::class)
            ->findOneBy([
                'userId' => $this->get('session')->get('logged_user')->getId(),
                'visitorId' => $id,
            ]);
            $em->remove($blocking);
            $em->flush();
        }

        return $this->redirectToRoute('user_messages');
    }

    /**
     * @Route("/user", name="user_main")
     */
    public function indexAction(Password $password, EntityManagerInterface $em)
    {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($this->get('session')->get('logged_user')->getId());

        $city = $this->get('session')->get('city');
        $in_city = $city->getUrl();



        $query = $em->createQuery('SELECT g FROM AppBundle:GeneralType g WHERE g.total !=0 ORDER BY g.total DESC');
        $generalTypes = $query->getResult();




        if(!$user->getIsBanned()) return $this->render('user/profile_main.html.twig',[
            'user' => $user,
            'city' => $city,

            'in_city' => $in_city,
            'cityId' => $city->getId(),
            'generalTypes' => $generalTypes,
            'lang' => $_SERVER['LANG'],

        ]);
        else return new Response("",404);
    }



    /**
     * @Route("/profile", name="user_profile")
     */
    public function editProfileAction(EntityManagerInterface $em, Request $request, ServiceStat $stat)
    {
        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($this->get('session')->get('logged_user')->getId());




        if(!$user->getIsBanned()) {


            $city = $this->get('session')->get('city');
            $in_city = $city->getUrl();



            $stat_arr = [
                'url' => $request->getPathInfo(),
                'event_type' => 'visit',
                'page_type' => 'cabinet',
                'user_id' => $user->getId(),
            ];
            $stat->setStat($stat_arr);

            $query = $em->createQuery('SELECT g FROM AppBundle:GeneralType g WHERE g.total !=0 ORDER BY g.total DESC');
            $generalTypes = $query->getResult();




            return $this->render('user/user_profile.html.twig', [
                'user' => $user,
                'city' => $city,

                'in_city' => $in_city,
                'cityId' => $city->getId(),
                'generalTypes' => $generalTypes,
                'lang' => $_SERVER['LANG'],

            ]);
        }
        else return new Response("",404);
    }

    /**
     * @Route("/profile/save")
     */
    public function updateProfileAction(Request $request)
    {
        $post = $request->request;

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($this->get('session')->get('logged_user')->getId());

        $user->setHeader(trim(strip_tags($post->get('header'))));

        /**
         * @var $info UserInfo
         */

        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('DELETE UserBundle\Entity\UserInfo u WHERE u.userId = ?1 AND u.uiKey != ?2');
        $query->setParameter(1, $user->getId());
        $query->setParameter(2, 'foto');
        $query->execute();

        foreach($post->get('info') as $uiKey =>$uiValue ){
            $info = new UserInfo();
            $info->setUiKey($uiKey);

//            if (in_array($uiKey,['website','about'])) $uiValue = strip_tags($uiValue);
            $uiValue = trim(strip_tags($uiValue));

            $info->setUiValue($uiValue);
            $info->setUser($user);
            $em->persist($info);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $this->get('session')->set('logged_user', $user);

        return $this->redirectToRoute('user_profile');
    }

    /**
     * @Route("/user/sendAbuse")
     */
    public function sendAbuseAction(Request $request, \Swift_Mailer $mailer)
    {
        $_t = $this->get('translator');

        $post = $request->request;

        $card_id = $post->get('card_id');

        if ($this->captchaVerify($post->get('g-recaptcha-response'))) {

            foreach($post->get('abuse') as $ms){
                $msg[] = $ms.'<br>';
            }
            $msg[] = 'Жалоба отправлена со <a href="https://mix.rent/card/'.$card_id.'">страницы</a>';
            $msg = implode("",$msg);

            $message = (new \Swift_Message('Жалоба от пользователя'))
                ->setFrom(['mail@multiprokat.com' => 'Робот Мультипрокат'])
                ->setTo('mail@multiprokat.com')
                ->setBody(
                    $msg,
                    'text/html'
                );
            $mailer->send($message);

            $this->addFlash(
                'notice',
                $_t->trans('Ваша жалоба успешно отправлена!')
            );
        } else {
            $this->addFlash(
                'notice',
                $_t->trans('Каптча не пройдена!')
            );
        }

        return $this->redirect('/card/'.$card_id);

    }


    /**
 * @Route("/user/sendMessage")
 */
    public function sendMessageAction(Request $request, MailGunController $mgc)
    {
        $_t = $this->get('translator');

        $post = $request->request;


        if($post->has('card_id')) {
            $card_id = $post->get('card_id');

            $card = $this->getDoctrine()
                ->getRepository(Card::class)
                ->find($card_id);
            $user = $card->getUser();
        }

        if($post->has('user_id')){
            $user = $this->getDoctrine()
                ->getRepository(User::class)
                ->find($post->get('user_id'));
            $card = false;
        }


        if ($this->captchaVerify($post->get('g-recaptcha-response'))) {




//            $message = (new \Swift_Message($_t->trans('Сообщение от пользователя')))
//                ->setFrom(['mail@multiprokat.com' => 'Робот Мультипрокат'])
//                ->setTo($user->getEmail())
//                ->setCc('mail@multiprokat.com')
//                ->setBody(
//                    $this->renderView(
//                        $_SERVER['LANG'] == 'ru' ? 'email/request.html.twig' : 'email/request_'.$_SERVER['LANG'].'.html.twig',
//                        array(
//                            'header' => $user->getHeader(),
//                            'message' => $post->get('message'),
//                            'email' => $post->get('email'),
//                            'name' => $post->get('name'),
//                            'phone' => $post->get('phone'),
//                            'card' => $card,
//                            'user' => $user
//                        )
//                    ),
//                    'text/html'
//                );
//            $mailer->send($message);

            $mgc->sendMG($user->getEmail(),$_t->trans('Сообщение от пользователя'),$this->renderView(
                        $_SERVER['LANG'] == 'ru' ? 'email/request.html.twig' : 'email/request_'.$_SERVER['LANG'].'.html.twig',
                        array(
                            'header' => $user->getHeader(),
                            'message' => $post->get('message'),
                            'email' => $post->get('email'),
                            'name' => $post->get('name'),
                            'phone' => $post->get('phone'),
                            'card' => $card,
                            'user' => $user
                        )
                    ));

            $this->addFlash(
                'notice',
                $_t->trans('Ваше сообщение успешно отправлено!')
            );
        } else {
            $this->addFlash(
                'notice',
                $_t->trans('Каптча не пройдена!')
            );
        }

        if(isset($card)) return $this->redirect('/card/'.$card_id);
        if(isset($user)) return $this->redirect('/user/'.$user->getId());
    }

     /**
 * @Route("/user/bookMessage")
 */
    public function bookMessageAction(Request $request, \Swift_Mailer $mailer)
    {
        $_t = $this->get('translator');

        $post = $request->request;

        if ($this->captchaVerify($post->get('g-recaptcha-response'))) {


            $card_id = $post->get('card_id');

            $card = $this->getDoctrine()
                ->getRepository(Card::class)
                ->find($card_id);
            $user = $card->getUser();




            $message = (new \Swift_Message($_t->trans('Запрос на бронирование')))
                ->setFrom(['mail@multiprokat.com' => 'Робот Мультипрокат'])
                ->setTo($user->getEmail())
                ->setBcc('mail@multiprokat.com')
                ->setBody(
                    $this->renderView(
                        $_SERVER['LANG'] == 'ru' ? 'email/book.html.twig' : 'email/book_'.$_SERVER['LANG'].'.html.twig',
                        array(
                            'header' => $post->get('header'),
                            'date_in' => $post->get('date_in'),
                            'date_out' => $post->get('date_out'),
                            'city_in' => $post->get('city_in'),
                            'city_out' => $post->get('city_out'),
                            'alternative' => $post->get('alternative'),
                            'email' => $post->get('email'),
                            'full_name' => $post->get('full_name'),
                            'phone' => $post->get('phone'),
                            'card' => $card,
                            'user' => $user
                        )
                    ),
                    'text/html'
                );
            $mailer->send($message);

            $this->addFlash(
                'notice',
                $_t->trans('Ваше сообщение успешно отправлено!')
            );
        } else {
            $this->addFlash(
                'notice',
                $_t->trans('Каптча не пройдена!')
            );
        }

        return $this->redirect('/card/'.$card->getId());

    }

//
    /**
     * @Route("/user/contactsMessage")
     */
    public function contactsMessageAction(Request $request, \Swift_Mailer $mailer)
    {
        $_t = $this->get('translator');

        $post = $request->request;

//        $card_id = $post->get('card_id');
//
//        $card = $this->getDoctrine()
//            ->getRepository(Card::class)
//            ->find($card_id);
//
//        $user = $card->getUser();

        $message = (new \Swift_Message($_t->trans('Сообщение от пользователя')))
            ->setFrom(['mail@multiprokat.com' => 'Робот Мультипрокат'])
            ->setTo('mail@multiprokat.com')
            ->setBody(
                $this->renderView(
                    $_SERVER['LANG'] == 'ru' ? 'email/request.html.twig' : 'email/request_'.$_SERVER['LANG'].'.html.twig',
                    array(
                        'header' => 'Админ',
                        'message' => $post->get('message'),
                        'email' => $post->get('email'),
                        'name' => $post->get('name'),
                        'phone' => $post->get('phone'),
                    )
                ),
                'text/html'
            );
        $mailer->send($message);

        $this->addFlash(
            'notice',
            $_t->trans('Ваше сообщение успешно отправлено!')
        );

        return $this->redirect('/');
    }

    /**
     * @Route("/profile/saveFoto")
     */
    public function saveFotoAction(Request $request, FotoUtils $fu)
    {
        $post = $request->request;
        $em = $this->getDoctrine()->getManager();

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($post->get('user_id'));


        $query = $em->createQuery('DELETE UserBundle\Entity\UserInfo u WHERE u.userId = ?1 AND u.uiKey = ?2');
        $query->setParameter(1, $post->get('user_id'));
        $query->setParameter(2, 'foto');
        $query->execute();
        if (is_file($_SERVER['DOCUMENT_ROOT'].'/assets/images/users/'.$post->get('user_id')))
            unlink ($_SERVER['DOCUMENT_ROOT'].'/assets/images/users/'.$post->get('user_id'));

        if($post->has('delete')) return $this->redirectToRoute('user_profile');

        $userInfo = new UserInfo();
        $userInfo->setUser($user);
        $userInfo->setUiKey('foto');
        $userInfo->setUiValue('user_'.$post->get('user_id'));
        $em->persist($userInfo);
        $em->flush();



        $fu->uploadImage(
            'foto',
            'user_'.$post->get('user_id'),
            $_SERVER['DOCUMENT_ROOT'].'/assets/images/users',
            $_SERVER['DOCUMENT_ROOT'].'/assets/images/users/t');

        return $this->redirectToRoute('user_profile');
    }

    /**
     * @Route("/ajax/goPro")
     */
    public function goProAction(Request $request)
    {
        $post = $request->request;
        $em = $this->getDoctrine()->getManager();

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($post->get('user_id'));
        $user->setAccountTypeId(1);
        $em->persist($user);
        $em->flush();

        return new Response();
    }

    /**
     * @Route("/user/{id}", name="user_page")
     */
    public function userPageAction($id, MenuMarkModel $mm, EntityManagerInterface $em, Request $request, ServiceStat $stat)
    {

        $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find((int)$id);
        if(!$user->getIsBanned()) {
            $user_foto = false;
            foreach ($user->getInformation() as $info) {
                if ($info->getUiKey() == 'foto' and $info->getUiValue() != '') $user_foto = '/assets/images/users/t/' . $info->getUiValue() . '.jpg';
            }

            $city = $this->get('session')->get('city');
            $in_city = $city->getUrl();



            $mark_arr = $mm->getExistMarks('',1);
            $mark_arr_sorted = $mark_arr['sorted_marks'];
            $models_in_mark = $mark_arr['models_in_mark'];

            $query = $em->createQuery('SELECT g FROM AppBundle:GeneralType g WHERE g.total !=0 ORDER BY g.total DESC');
            $generalTypes = $query->getResult();


            $stat_arr = [
                'url' => $request->getPathInfo(),
                'event_type' => 'visit',
                'page_type' => 'profile',
                'user_id' => $user->getId(),
            ];
            $stat->setStat($stat_arr);

            return $this->render('user/user_page.html.twig', [
                'user' => $user,
                'user_foto' => $user_foto,
                'city' => $city,

                'mark_arr_sorted' => $mark_arr_sorted,
                'models_in_mark' => $models_in_mark,
                'in_city' => $in_city,
                'cityId' => $city->getId(),
                'mark' => $mark_arr_sorted[1][0]['mark'],
                'model' => $mark_arr_sorted[1][0]['models'][0],
                'generalTypes' => $generalTypes,
                'lang' => $_SERVER['LANG']
            ]);
        } else return new Response("",404);
    }

    private function captchaverify($recaptcha){
        $url = "https://www.google.com/recaptcha/api/siteverify";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            "secret"=>"6LcGCzUUAAAAAH0yAEPu8N5h9b5BB8THZtFDx3r2","response"=>$recaptcha));
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);

        return $data->success;
    }


}