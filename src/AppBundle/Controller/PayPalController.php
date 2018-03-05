<?php

namespace AppBundle\Controller;

use AppBundle\Entity\PaypalPayments;
use AppBundle\Entity\Seo;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Entity\Card;
use UserBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;


class PayPalController extends Controller
{
    /**
     * @Route("/paypalSuccess", name="paypalSuccess")
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
     * @Route("/paypalCancel", name="paypalCancel")
     */
    public function paypalCancelAction(Request $request, EntityManagerInterface $em)
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
     * @Route("/paypalPayPro", name="paypalPayPro")
     */
    public function paypalPayProAction(Request $request, EntityManagerInterface $em )
    {
        $url = "https://api.sandbox.paypal.com/v1/oauth2/token";
                $headers = array(
                    'Accept' => 'application/json',
                    'Accept-Language' => 'en_US',
                );

                $clientID = 'AVtyX4DQ_AxvLHzGbdGk3meMLtJD6vNPEcR1Ffqq23AKfZAqOWyUSb_QXES9_l25nPdITbiNJVQLenOz';
                $clientSecret = 'EAWY5q29JVzJbcfX4oM0GmsEy987zoD-_fyps0yRTg__pSa1SFwR1uOMdwFSjJtPDwbtIEwmm9dfSXv_';

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_USERPWD, $clientID . ':' . $clientSecret);
                $curl = curl_exec($ch);
                curl_close($ch);

                dump($curl);

                $x = json_decode($curl, TRUE);
                $accesstoken = $x['access_token'];


                $headers2 = array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accesstoken
                );

                $data = array(
                    "intent" => "sale",
                    "redirect_urls" => array(
                        "return_url" => "https://mix.rent/paypalSuccess",
                        "cancel_url" => "https://mix.rent/paypalCancel"
                    ),
                    "payer" => array(
                        "payment_method" => "paypal"
                    ),
                    "transactions" => array(
                        "amount" => array(
                            "total" => '99.99',
                            "currency" => "USD"

                        ),
                        "custom" => 'pro'
                    )
                );

                $saleurl = "https://api.sandbox.paypal.com/v1/payments/payment";

                $sale = curl_init();
                curl_setopt($sale, CURLOPT_URL, $saleurl);
                curl_setopt($sale, CURLOPT_VERBOSE, TRUE);
                curl_setopt($sale, CURLOPT_RETURNTRANSFER, TRUE);
                curl_setopt($sale, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($sale, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($sale, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($sale, CURLOPT_HTTPHEADER, $headers2);
                 //curl_setopt($sale, CURLOPT_USERPWD, $clientID . ':' . $clientSecret);
                $finalsale = curl_exec($sale);
                curl_close($sale);

                dump($finalsale);

                $url = json_decode($finalsale, TRUE);

                dump($finalsale);
                //$response = new RedirectResponse($url);

        //return new RedirectResponse($url);
    }

    /**
     * @Route("/paypalPayment", name="paypalPayment")
     */
    public function paypalPaymentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $post = $request->request;

        $data['item_number']        = $post->get('item_number');
        $data['payment_status']     = $post->get('payment_status');
        $data['payment_amount']     = $post->get('mc_gross');
        $data['payment_currency']   = $post->get('mc_currency');
        $data['txn_id']             = $post->get('txn_id');
        $data['receiver_email']     = $post->get('receiver_email');
        $data['payer_email']        = $post->get('payer_email');
        $data['custom']             = $post->get('custom');

        $paypal = new PaypalPayments();
        $paypal->setAmount($data['payment_amount']);
        $paypal->setItemId($data['item_number']);
        $paypal->setPaymentType('tariff');
        $paypal->setStatus('new');
        $paypal->setTxnid($data['txn_id']);
        $em->persist($paypal);
        $em->flush();



        $raw_post_data = file_get_contents('php://input');

        //file_put_contents('raw_post.txt',$raw_post_data);

        $raw_post_array = explode('&', $raw_post_data);
        $myPost = array();
        foreach ($raw_post_array as $keyval) {
          $keyval = explode ('=', $keyval);
          if (count($keyval) == 2)
            $myPost[$keyval[0]] = urldecode($keyval[1]);
        }
        // read the IPN message sent from PayPal and prepend 'cmd=_notify-validate'
        $req = 'cmd=_notify-validate';
        $get_magic_quotes_exists = false;
        if (function_exists('get_magic_quotes_gpc')) {
          $get_magic_quotes_exists = true;
        }
        foreach ($myPost as $key => $value) {
          if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
            $value = urlencode(stripslashes($value));
          } else {
            $value = urlencode($value);
          }
          $req .= "&$key=$value";
        }

        $ch = curl_init('https://ipnpb.paypal.com/cgi-bin/webscr');
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));

        $res = curl_exec($ch);
        curl_close($ch);

        //file_put_contents('curl.txt',$res);

        if ( !($res) ) {
          // error
          exit;
        } else {
            if (strcmp ($res, "VERIFIED") == 0) {

              $valid_txnid = $this->check_txnid($data);
              if ($valid_txnid) $this->updatePayments($data);

            } else if (strcmp ($res, "INVALID") == 0) {
              // IPN invalid, log for manual investigation
            }
        }

        return new Response();
    }

    function check_txnid($data){
        $check = $this->getDoctrine()
                    ->getRepository(PaypalPayments::class)
                    ->findOneBy(['txnid'=>$data['txn_id']]);
        if ($check->getAmount() == $data['payment_amount'] and $check->getItemId() == $data['item_number']) return true;
    }

    function check_price(){
        return true;
    }

    function updatePayments($data){
        $em = $this->getDoctrine()->getManager();

        $xc = explode("_",$data['custom']);

        if($data['custom'] == 'card') {
            $card = $this->getDoctrine()
                ->getRepository(Card::class)
                ->find($data['item_number']);
            $card->setIsActive(true);
            $em->persist($card);
            $em->flush();
        }

        if($xc[0] == 'pro') {

            if(isset($xc[1]) and $xc[1]!='') { // if new card and not only PRO
                $card = $this->getDoctrine()
                    ->getRepository(Card::class)
                    ->find($xc[1]);
                $card->setIsActive(true);
                $em->persist($card);
                $em->flush();
            }

            $user = $this->getDoctrine()
            ->getRepository(User::class)
            ->find($data['item_number']);
            $user->setAccountTypeId(1);
            $em->persist($user);
            $em->flush();
        }

        return true;

    }

    /**
     * @Route("/paypalReturnUrl", name="paypalReturnUrl")
     */
    public function paypalReturnUrlAction(Request $request)
    {

    }

}

//multiprokat.msk.merchant@gmail.com
//multi261262
//https://www.paypal.com/cgi-bin/webscr?business=multiprokat.msk%40gmail.com&item_number=3665&amount=99.99&cmd=_xclick&no_note=1&currency_code=USD&bn=MixRentPRO_BuyNow_WPS_RU&return=https%3A%2F%2Fmix.rent%2FpaypalSuccess&cancel_return=https%3A%2F%2Fmix.rent%2FpaypalCancel&notify_url=https%3A%2F%2Fmix.rent%2FpaypalPayment&custom=pro