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
        // PayPal settings
        $paypal_email = 'wsq-info2@mail.ru';
        $return_url = 'https://mix.rent/paypalSuccess';
        $cancel_url = 'https://mix.rent/paypalCancel';
        $notify_url = 'https://mix.rent/paypalPayment';

        $item_id = $this->get('session')->get('logged_user')->getId();
        $item_amount = 99.99;
        $custom = 'pro';

        $querystring = '';

        $querystring .= "?business=".urlencode($paypal_email)."&";

        $querystring .= "item_number=".urlencode($item_id)."&";
        $querystring .= "amount=".urlencode($item_amount)."&";

        $querystring .= "cmd=_xclick&";
        $querystring .= "no_note=1&";
        $querystring .= "lc=US&";
        $querystring .= "currency_code=USD&";
        $querystring .= "bn=PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest&";


        $querystring .= "return=".urlencode(stripslashes($return_url))."&";
        $querystring .= "cancel_return=".urlencode(stripslashes($cancel_url))."&";
        $querystring .= "notify_url=".urlencode($notify_url);
        $querystring .= "&custom=".$custom;

        $url ='https://www.sandbox.paypal.com/cgi-bin/webscr'.$querystring;

        return new RedirectResponse($url);
    }

    /**
     * @Route("/paypalPayment", name="paypalPayment")
     */
    public function paypalPaymentAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $post = $request->request;
        //file_put_contents('test.txt',json_encode($request->request->all()));

           // Response from PayPal
//            $req = 'cmd=_notify-validate';
//            foreach ($request->request->all() as $key => $value) {
//                $value = urlencode(stripslashes($value));
//                $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i','${1}%0D%0A${3}',$value);// IPN fix
//                $req .= "&$key=$value";
//            }

            // assign posted variables to local variables
            //$data['item_name']          = $_POST['item_name'];
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
            if ( !($res) ) {
              // error
              exit;
            } else {
                if (strcmp ($res, "VERIFIED") == 0) {
                  // The IPN is verified, process it
                  $valid_txnid = $this->check_txnid($data['txn_id']);
                  if ($valid_txnid) $this->updatePayments($data);

                } else if (strcmp ($res, "INVALID") == 0) {
                  // IPN invalid, log for manual investigation
                }
            }

        return new Response();
    }

    function check_txnid($txnid){
        //global $link;
        //return true;
//        $valid_txnid = true;
//        //get result set
//        $sql = mysql_query("SELECT * FROM `payments` WHERE txnid = '$tnxid'", $link);
//        if ($row = mysql_fetch_array($sql)) {
//            $valid_txnid = false;
//        }
//        return $valid_txnid;

        $check = $this->getDoctrine()
                    ->getRepository(PaypalPayments::class)
                    ->findOneBy(['txnid'=>$txnid]);
        return $check;
    }

    function check_price($price, $id){
        //$valid_price = false;
        //you could use the below to check whether the correct price has been paid for the product

        /*
        $sql = mysql_query("SELECT amount FROM `products` WHERE id = '$id'");
        if (mysql_num_rows($sql) != 0) {
            while ($row = mysql_fetch_array($sql)) {
                $num = (float)$row['amount'];
                if($num == $price){
                    $valid_price = true;
                }
            }
        }
        return $valid_price;
        */
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
        //
        //global $link;

//        if (is_array($data)) {
//            $sql = mysql_query("INSERT INTO `payments` (txnid, payment_amount, payment_status, itemid, createdtime) VALUES (
//                    '".$data['txn_id']."' ,
//                    '".$data['payment_amount']."' ,
//                    '".$data['payment_status']."' ,
//                    '".$data['item_number']."' ,
//                    '".date("Y-m-d H:i:s")."'
//                    )", $link);
//            return mysql_insert_id($link);
//        }
    }
    /**
     * @Route("/testPI", name="testPI")
     */
    public function testInsert()
    {
        $em = $this->getDoctrine()->getManager();
        $paypal = new PaypalPayments();
            $paypal->setAmount('7.00');
            $paypal->setItemId(1);
            $paypal->setPaymentType('tariff');
            $paypal->setStatus('new');
            $paypal->setTxnid('4567asdfuyrt4567');
            $em->persist($paypal);
            $em->flush();
            return new Response();
    }
}

//multiprokat.msk.merchant@gmail.com
//multi261262