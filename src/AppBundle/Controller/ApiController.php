<?php

namespace AppBundle\Controller;



use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManagerInterface as em;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Security\Password;

class ApiController extends Controller
{

    var $db;

    public function __construct(em $em)
    {
        $this->db = $em->getConnection();
        $this->base_url = (isset($_SERVER['HTTPS']) ? 'http' : 'https' ). "://" . $_SERVER['SERVER_NAME']  ;
    }

    /**
     * @Route("/api/card/{id}", name="api_card")
     */
    public function api_card($id)
    {
        $id = (int)$id;
        //$card = $this->db->fetchAssoc('SELECT id,header,content,general_type_id as vehicle_type_id,model_id,user_id,views,city_id,currency FROM card WHERE id = ?', array($id));


                    $card = $this->db->fetchAssoc('SELECT c.id,c.header,c.content,c.user_id,c.views,c.city_id,c.model_id,m.header as model,k.header as mark,k.id as mark_id,s.header as city,c.prod_year,g.url as category,c.general_type_id as vehicle_type_id FROM card as c LEFT JOIN car_model as m ON m.id = c.model_id LEFT JOIN car_mark as k ON k.id = m.car_mark_id LEFT JOIN city as s ON s.id = c.city_id LEFT JOIN general_type as g ON g.id = c.general_type_id WHERE c.id=? ',array($id));



        $prices = $this->db->fetchAll('SELECT cp.price_id,cp.value FROM card_price cp WHERE card_id = ?', array($id));

        $main_foto = $this->db->fetchAssoc('SELECT id,folder FROM foto WHERE card_id = ? AND is_main=1', array($id));
        $extra_fotos = $this->db->fetchAll('SELECT id,folder FROM foto WHERE card_id = ? AND is_main!=1', array($id));

        if($card) $card['status'] = 'OK';
        else $card['status'] = 'error';

        $card['prices'] = $prices;
        $card['main_foto_url'] = $this->base_url.'/assets/images/cards/'.$main_foto['folder'].'/'.$main_foto['id'].'.jpg';
        $card['main_foto_url_thumb'] = $this->base_url.'/assets/images/cards/'.$main_foto['folder'].'/t/'.$main_foto['id'].'.jpg';

        foreach ($extra_fotos as $ef){
            $card['extra_fotos'][] = $this->base_url.'/assets/images/cards/'.$ef['folder'].'/'.$ef['id'].'.jpg';
            $card['extra_fotos_thumb'][] = $this->base_url.'/assets/images/cards/'.$ef['folder'].'/t/'.$ef['id'].'.jpg';
        }

        header('Content-Type: application/json');
        echo json_encode($card, JSON_PRETTY_PRINT);
        return new Response();
    }

    /**
     * @Route("/api/list", name="api_list")
     */
    public function api_list()
    {
        $data = json_decode(file_get_contents("php://input"),true);

        $rez = [];

        if(
            isset($data['vehicle_type_id']) and
            isset($data['city_id']) and
            isset($data['model_id'])
        ){
//            $cards = $this->db->fetchAll('SELECT id,header,content,user_id,views FROM card WHERE model_id = ? AND general_type_id=? AND city_id=? ',
//            array($data['model_id'],$data['vehicle_type_id'],$data['city_id']));

            $cards = $this->db->fetchAll('SELECT 
c.id,c.header,c.content,c.user_id,c.views,c.city_id,c.model_id,m.header as model,k.header as mark,k.id as mark_id,s.header as city,c.prod_year,g.url as category,c.general_type_id as vehicle_type_id 
FROM card as c 
LEFT JOIN car_model as m ON m.id = c.model_id 
LEFT JOIN car_mark as k ON k.id = m.car_mark_id 
LEFT JOIN city as s ON s.id = c.city_id 
LEFT JOIN general_type as g ON g.id = c.general_type_id 

WHERE c.model_id = ? AND c.general_type_id=? AND c.city_id=?

',array($data['model_id'],$data['vehicle_type_id'],$data['city_id']));



            foreach ($cards as $c){
                $c['prices'] = $this->db->fetchAll('SELECT cp.price_id,cp.value FROM card_price cp WHERE card_id = ?', array($c['id']));
                $mfu = $this->db->fetchAssoc('SELECT id,folder FROM foto WHERE card_id = ? AND is_main=1', array($c['id']));
                $c['main_foto_url_thumb'] = $this->base_url.'/assets/images/cards/'.$mfu['folder'].'/t/'.$mfu['id'].'.jpg';
                $rez[] = $c;
            }

            $status = 'OK';

        } else {
            $status = 'error';
        }

        $send['status'] = $status;
        $send['result'] = $rez;

        header('Content-Type: application/json');
        echo json_encode($send, JSON_PRETTY_PRINT);
        return new Response();
    }

    /**
     * @Route("/api/vehicle_types", name="vehicle_types")
     */
    public function vehicle_types()
    {
        $vt = $this->db->fetchAll('SELECT id,url as header,car_type_ids as car_type_id FROM general_type WHERE parent_id IS NOT NULL ',array());
        $vt['status'] = 'OK';
        header('Content-Type: application/json');
        echo json_encode($vt, JSON_PRETTY_PRINT);
        return new Response();
    }

    /**
     * @Route("/api/marks/{car_type_id}", name="api_marks")
     */
    public function api_marks($car_type_id)
    {
        $car_type_id = (int)$car_type_id;
        $m = $this->db->fetchAll('SELECT id,header FROM car_mark WHERE car_type_id=?',array($car_type_id));
        $r['status'] = 'OK';
        $r['result'] = $m;
        header('Content-Type: application/json');
        echo json_encode($r, JSON_PRETTY_PRINT);
        return new Response();
    }

    /**
     * @Route("/api/models/{mark_id}", name="api_models")
     */
    public function api_models($mark_id)
    {
        $mark_id = (int)$mark_id;
        $m = $this->db->fetchAll('SELECT id,header FROM car_model WHERE car_mark_id=?',array($mark_id));
        $r['status'] = 'OK';
        $r['result'] = $m;
        header('Content-Type: application/json');
        echo json_encode($r, JSON_PRETTY_PRINT);
        return new Response();
    }

    /**
     * @Route("/api/cities/{country_iso}", name="api_cities")
     */
    public function api_cities($country_iso)
    {
        $m = $this->db->fetchAll('SELECT id,header,iso FROM city WHERE country=?',array($country_iso));
        $r['status'] = 'OK';
        $r['result'] = $m;
        header('Content-Type: application/json');
        echo json_encode($r, JSON_PRETTY_PRINT);
        return new Response();
    }

    /**
     * @Route("/api/authorize", name="authorize")
     */
    public function authorize()
    {
        $r = array();

        $data = json_decode(file_get_contents("php://input"),true);

        $user = $this->db->fetchAssoc('SELECT * FROM user WHERE email = ? AND is_activated=1', array($data['email']));

        if($user){

            $password = new Password();

            if ($password->CheckPassword($data['password'], $user['password'])){

                $token_name = hash("sha256",uniqid($user['id']));
                $server_hash = hash("sha256",uniqid($token_name));

                $this->db->executeQuery("INSERT INTO tokens SET 
                  user_id = ?,
                  token_name = ?,
                  server_hash = ?,
                  client_hash = ? 
                  ",array(
                      $user['id'],
                      $token_name,
                      $server_hash,
                      $data['client_hash']
                ));

                $r['status'] = 'OK';
                $r['result'] = array('token'=>$token_name,'server_hash'=>$server_hash);
            } else {
                $r['status'] = 'error';
                $r['result'] = array('code'=>110); // wrong password
            }

        } else {
            $r['status'] = 'error';
            $r['result'] = array('code'=>100); // no user
        }


        header('Content-Type: application/json');
        echo json_encode($r, JSON_PRETTY_PRINT);
        return new Response();
    }

    /**
     * @Route("/api/get_user/{id}", name="get_user")
     */
    public function get_user($id)
    {
        $u = $this->db->fetchAssoc('SELECT id,email,header as name FROM user WHERE id=?',array($id));

        foreach ($this->db->fetchAll('SELECT * FROM user_info WHERE user_id=?',array($id)) as $i){
            if($i['ui_key'] == 'phone') $u['phone'] = $i['ui_value'];
            if($i['ui_key'] == 'foto') $foto = '/assets/images/users/t/'.$i['ui_value'].'.jpg';
        }

        if($u){
            $r['status'] = 'OK';

            if(isset($foto) and file_exists($_SERVER['DOCUMENT_ROOT'].$foto)) $u['foto'] = $this->base_url.$foto;
            else $u['foto'] = 'none';

            $cards = $this->db->fetchAll('SELECT c.id,c.header,c.content,c.user_id,c.views,c.city_id,c.model_id,m.header as model,k.header as mark,k.id as mark_id,s.header as city,c.prod_year,g.url as category,c.general_type_id as vehicle_type_id FROM card as c LEFT JOIN car_model as m ON m.id = c.model_id LEFT JOIN car_mark as k ON k.id = m.car_mark_id LEFT JOIN city as s ON s.id = c.city_id LEFT JOIN general_type as g ON g.id = c.general_type_id WHERE user_id=? ',array($id));

            $rez = array();

            foreach ($cards as $cd) $ids[] = $cd['user_id'];$ids = implode(",",$ids);
            foreach ($this->db->fetchAll("SELECT ui_value,user_id FROM user_info WHERE ui_key='foto' AND user_id IN (".$ids.")") as $if) $uf[$if['user_id']] = $if['ui_value'];

            if(!empty($cards)) foreach ($cards as $c){
                $c['prices'] = $this->db->fetchAll('SELECT cp.price_id,cp.value FROM card_price cp WHERE card_id = ?', array($c['id']));
                $mfu = $this->db->fetchAssoc('SELECT id,folder FROM foto WHERE card_id = ? AND is_main=1', array($c['id']));
                $c['main_foto_url_thumb'] = $this->base_url.'/assets/images/cards/'.$mfu['folder'].'/t/'.$mfu['id'].'.jpg';

                if(isset($uf[$c['user_id']])) $c['user_foto'] = $this->base_url.'/assets/images/users/t/'.$uf[$c['user_id']].'.jpg';
                else $c['user_foto'] = 'none';

                $rez[] = $c;
            }

            $u['cards'] = $rez;

            $r['result'] = $u;
        }


        header('Content-Type: application/json');
        echo json_encode($r, JSON_PRETTY_PRINT);
        return new Response();
    }

    /**
     * @Route("/api/check_email", name="check_email")
     */
    public function check_email()
    {
        $data = json_decode(file_get_contents("php://input"),true);

        $u = $this->db->fetchAssoc('SELECT id FROM user WHERE email=?',array($data['email']));

        if($u) $this->get_user($u['id']);
        else{
            $r['status'] = 'error';
            $r['result'] = array('code'=>100); // no user
            echo json_encode($r, JSON_PRETTY_PRINT);
        }

        return new Response();
    }

    /**
     * @Route("/api/check_phone", name="check_phone")
     */
    public function check_phone()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $i = $this->db->fetchAssoc("SELECT user_id FROM user_info WHERE ui_key='phone' AND ui_value=?", array($data['phone']));
        if ($i and $i['user_id'] != 0) {
            $this->get_user($i['user_id']);
        } else {
            $r['status'] = 'error';
            $r['result'] = array('code'=>100); // no user
            echo json_encode($r, JSON_PRETTY_PRINT);
        }

        return new Response();
    }

}
