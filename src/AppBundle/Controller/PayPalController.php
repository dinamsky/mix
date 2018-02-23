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
     * @Route("/paypalIPN", name="createPayment")
     */
    public function indexAction(Request $request)
    {

        file_put_contents('IPN', json_encode($request));

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

    /**
     * @Route("/paypalTestPage", name="createPayment")
     */
    public function paypalTestPageAction(Request $request, EntityManagerInterface $em)
    {


        $query = $em->createQuery('SELECT g FROM AppBundle:GeneralType g WHERE g.total !=0 ORDER BY g.weight, g.total DESC');
        $generalTypes = $query->getResult();

        $city = $this->get('session')->get('city');

        $in_city = $city->getUrl();

        $customData = ['user_id' => 1, 'product_id' => 5];

       return $this->render('paypal/test_page.html.twig', [
            'city' => $city,
            'cityId' => $city->getId(),
            'generalTypes' => $generalTypes,
            'in_city' => $in_city,
            'lang' => $_SERVER['LANG'],
           'customData' => json_encode($customData)
        ]);
    }
}

//multiprokat.msk.merchant@gmail.com
//multi261262