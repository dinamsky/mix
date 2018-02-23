<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Seo;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class PayPalController extends Controller
{
    /**
     * @Route("/paypalCreatePayment", name="createPayment")
     */
    public function indexAction(Request $request)
    {

        $data = '{
          "intent":"sale",
          "redirect_urls":{
            "return_url":"https://mix.rent/return_url",
            "cancel_url":"https://mix.rent/cancel_url"
          },
          "payer":{
            "payment_method":"paypal"
          },
          "transactions":[
            {
              "amount":{
                "total":"7.47",
                "currency":"USD"
              },
              "description":"This is the payment transaction description."
            }
          ]
        }';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json",
          "Authorization: Bearer EOjEJigcsRhdOgD7_76lPfrr45UfuI43zzNzTktUk1MK",
          "Content-length: ".strlen($data))
        );

        $get = curl_exec($ch);

        dump($get);

        return new Response();
    }

    /**
     * @Route("/paypalSuccess", name="createPayment")
     */
    public function paypalSuccessAction(Request $request, EntityManagerInterface $em)
    {


        $query = $em->createQuery('SELECT g FROM AppBundle:GeneralType g WHERE g.total !=0 ORDER BY g.weight, g.total DESC');
        $generalTypes = $query->getResult();

        $city = $this->get('session')->get('city');

        $in_city = $city->getUrl();
       return $this->render('paypal/success.html.twig', [
            'city' => $city,
            'cityId' => $city->getId(),
            'generalTypes' => $generalTypes,
            'in_city' => $in_city,
            'lang' => $_SERVER['LANG'],
        ]);
    }
}

//multiprokat.msk.merchant@gmail.com
//multi261262