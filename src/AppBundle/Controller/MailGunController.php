<?php

namespace AppBundle\Controller;


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
        # Instantiate the client.
        $mg = Mailgun::create('key-5f23100bafffe48a6225c2bf4792e85f');
        $domain = "mail.mix.rent";

        # Make the call to the client.


        $mg->messages()->send($domain, [
            'from'    => 'mail@mix.rent',
            'to'      => 'wqs-info@mail.ru',
            'subject' => 'Hello',
            'text'    => 'Testing some Mailgun awesomness!',
            'html'    => 'Testing some <b>Mailgun</b> awesomness!'
        ]);


        //return new RedirectResponse($url['links'][1]['href']);
        return new Response('ok');
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

}
