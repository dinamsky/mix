<?php

namespace AppBundle\Controller;


use AppBundle\Entity\Settings;
use Mailgun\Mailgun;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Card;
use UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;


class MailGunController extends Controller
{

    /**
     * @Route("/mc_test", name="mc_test")
     */
    public function mc_testAction(Request $request, EntityManagerInterface $em )
    {


        $query = $em->createQuery('SELECT c,u,f FROM AppBundle:Card c LEFT JOIN c.user u WITH u.id = c.userId LEFT JOIN c.fotos f WITH f.cardId = c.id AND f.isMain =1 WHERE c.cityId > 1257 GROUP BY c.userId');
        $query->setMaxResults(7);
        $result = $query->getResult();
        foreach($result as $r)
        {
            echo $this->renderView(
                            'email/admin_registration_en.html.twig',
                            array(
                                'header' => $r->getUser()->getHeader(),
                                'password' => $r->getUser()->getTempPassword(),
                                'email' => $r->getUser()->getEmail(),
                                'card' => $r,
                                'main_foto' => 'http://mix.rent/assets/images/cards/'.$r->getFotos()[0]->getFolder().'/t/'.$r->getFotos()[0]->getId().'.jpg',
                                'c_price' => 0,
                                'c_ed' => '$'
                            )
                        );
        }


//        # Instantiate the client.
//        $mg = Mailgun::create('key-5f23100bafffe48a6225c2bf4792e85f');
//        $domain = "mail.mix.rent";
//
//        # Make the call to the client.
//
//
//        $mg->messages()->send($domain, [
//            'from'    => 'mail@mix.rent',
//            'to'      => 'wqs-info@mail.ru',
//            'subject' => 'Hello',
//            'text'    => 'Testing some Mailgun awesomness!',
//            'html'    => 'Testing some <b>Mailgun</b> awesomness!'
//        ]);


        //return new RedirectResponse($url['links'][1]['href']);
        return new Response('ok2');
    }


    public function sendMG($to,$subject,$message)
    {

        $mg = Mailgun::create('key-5f23100bafffe48a6225c2bf4792e85f');
        $domain = "mail.mix.rent";

        $mg->messages()->send($domain, [
            'from'    => 'MixRent <mail@mix.rent>',
            'to'      => $to,
            'subject' => $subject,
            'html'    => $message
        ]);

        return new Response();
    }

    public function sendForAll()
    {
        $em = $this->getDoctrine()->getManager();

        $query = $em->createQuery("SELECT s FROM AppBundle:Settings s WHERE s.sKey = 'mailsend'");
        $res = $query->getResult();
        $res = $res[0];

        if($res->getSValue() == 'ready') {

            $mg = Mailgun::create('key-5f23100bafffe48a6225c2bf4792e85f');
            $domain = "mail.mix.rent";

            $query = $em->createQuery('SELECT c,u,f FROM AppBundle:Card c LEFT JOIN c.user u WITH u.id = c.userId LEFT JOIN c.fotos f WITH f.cardId = c.id AND f.isMain =1 WHERE c.cityId > 1257 GROUP BY c.userId');
            //$query->setMaxResults(7);
            $result = $query->getResult();
            foreach ($result as $r) {

                $message = $this->renderView(
                    'email/admin_registration_en.html.twig',
                    array(
                        'header' => $r->getUser()->getHeader(),
                        'password' => $r->getUser()->getTempPassword(),
                        'email' => $r->getUser()->getEmail(),
                        'card' => $r,
                        'main_foto' => 'http://mix.rent/assets/images/cards/' . $r->getFotos()[0]->getFolder() . '/t/' . $r->getFotos()[0]->getId() . '.jpg',
                        'c_price' => 0,
                        'c_ed' => '$'
                    )
                );

                //$to = $r->getUser()->getEmail();
                $to = 'wqs-info@mail.ru';

                $subject = '';

                $mg->messages()->send($domain, [
                    'from' => 'MixRent <mail@mix.rent>',
                    'to' => $to,
                    'subject' => $subject,
                    'html' => $message
                ]);
            }

                $st = $this->getDoctrine()
                    ->getRepository(Settings::class)
                    ->findBy(['sKey'=>'mailsend']);
                $st->setSValue('done');
                $em->persist($st);
                $em->flush();

        }
        //return new RedirectResponse($url['links'][1]['href']);
        return new Response('ok2');
    }
}
