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
        $querystring .= "item_number=1&";

        $querystring .= "return=".urlencode(stripslashes($return_url))."&";
        $querystring .= "cancel_return=".urlencode(stripslashes($cancel_url))."&";
        $querystring .= "notify_url=".urlencode($notify_url);
        $querystring .= "custom=".$custom;

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
            $req = 'cmd=_notify-validate';
            foreach ($request->request->all() as $key => $value) {
                $value = urlencode(stripslashes($value));
                $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i','${1}%0D%0A${3}',$value);// IPN fix
                $req .= "&$key=$value";
            }

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



            // post back to PayPal system to validate
            $header = "POST /cgi-bin/webscr HTTP/1.0\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

            $fp = fsockopen ('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);

            if (!$fp) {
                // HTTP ERROR

            } else {
                fputs($fp, $header . $req);
                while (!feof($fp)) {
                    $res = fgets ($fp, 1024);
                    if (strcmp($res, "VERIFIED") == 0) {

                        // Used for debugging
                        // mail('user@domain.com', 'PAYPAL POST - VERIFIED RESPONSE', print_r($post, true));

                        // Validate payment (Check unique txnid & correct price)
                        $valid_txnid = $this->check_txnid($data['txn_id']);
                        $valid_price = $this->check_price($data['payment_amount'], $data['item_number']);
                        // PAYMENT VALIDATED & VERIFIED!
                        if ($valid_txnid && $valid_price) {

                            $orderid = $this->updatePayments($data);

                            if ($orderid) {
                                // Payment has been made & successfully inserted into the Database
                            } else {
                                // Error inserting into DB
                                // E-mail admin or alert user
                                // mail('user@domain.com', 'PAYPAL POST - INSERT INTO DB WENT WRONG', print_r($data, true));
                            }
                        } else {
                            // Payment made but data has been changed
                            // E-mail admin or alert user
                        }

                    } else if (strcmp ($res, "INVALID") == 0) {

                        // PAYMENT INVALID & INVESTIGATE MANUALY!
                        // E-mail admin or alert user

                        // Used for debugging
                        //@mail("user@domain.com", "PAYPAL DEBUGGING", "Invalid Response

                    }
                }
            fclose ($fp);
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
        return $check->getTxnid();
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