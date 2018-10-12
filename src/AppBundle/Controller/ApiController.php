<?php

namespace AppBundle\Controller;



use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Doctrine\ORM\EntityManagerInterface as em;
use Symfony\Component\HttpFoundation\Response;



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
        $card = $this->db->fetchAssoc('SELECT id,header,content,general_type_id as vehicle_type_id,model_id,user_id,views,city_id,currency FROM card WHERE id = ?', array($id));
        $prices = $this->db->fetchAll('SELECT cp.id,cp.value FROM card_price cp WHERE card_id = ?', array($id));

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
            $cards = $this->db->fetchAll('SELECT id,header,content,user_id,views FROM card WHERE model_id = ? AND general_type_id=? AND city_id=? ',
            array($data['model_id'],$data['vehicle_type_id'],$data['city_id']));

            foreach ($cards as $c){
                $c['prices'] = $this->db->fetchAll('SELECT сp.id,cp.value FROM card_price cp WHERE card_id = ?', array($c['id']));
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

}
