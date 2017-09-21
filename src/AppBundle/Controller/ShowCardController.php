<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Mark;
use AppBundle\Entity\SubField;
use AppBundle\Menu\MenuGeneralType;
use AppBundle\Menu\MenuCity;
use AppBundle\Menu\MenuMarkModel;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface as em;
use AppBundle\Entity\Card;
use AppBundle\Entity\CardField;
use AppBundle\Entity\City;
use AppBundle\Entity\GeneralType;
use AppBundle\SubFields\SubFieldUtils;
use Symfony\Component\HttpFoundation\Cookie;

class ShowCardController extends Controller
{

    /**
     * @Route("/card/{id}", requirements={"id": "\d+"}, name="showCard")
     */
    public function showCardAction($id, MenuGeneralType $mgt, SubFieldUtils $sf, MenuCity $mc, MenuMarkModel $mm)
    {
        $card = $this->getDoctrine()
            ->getRepository(Card::class)
            ->find($id);

        $views = $this->get('session')->get('views');
        if (!isset($views[$card->getId()])) {
            $views[$card->getId()] = 1;
            $this->get('session')->set('views', $views);
            $card->setViews($card->getViews() + 1);
            $em = $this->getDoctrine()->getManager();
            $em->persist($card);
            $em->flush();
        }

        $subFields = $sf->getCardSubFields($card);

        $city = $card->getCity();

        if ($card->getVideo() != '') $video = explode("=",$card->getVideo())[1];
        else $video = false;

        if ($card->getStreetView() != '') $streetView = unserialize($card->getStreetView());
        else $streetView = false;

        $dql = 'SELECT c FROM AppBundle:Card c JOIN c.tariff t WHERE c.generalTypeId = '.$card->getGeneralTypeId().' ORDER BY t.weight DESC, c.dateTariffStart DESC, c.dateUpdate DESC';
        $em = $this->get('doctrine')->getManager();
        $query = $em->createQuery($dql);
        $query->setMaxResults(10);
        $similar = $query->getResult();

        $model = $mm->getModel($card->getModelId());
        $mark = $mm->getMark($model->getCarMarkId());
        $models = $mm->getModels($model->getCarMarkId());
        $marks = $mm->getMarks($model->getCarTypeId());

        $user_foto = false;
        foreach ($card->getUser()->getInformation() as $info){
           if($info->getUiKey() == 'foto' and $info->getUiValue()!='') $user_foto =  '/assets/images/users/t/'.$info->getUiValue().'.jpg';
        }


        $general = $this->getDoctrine()
            ->getRepository(GeneralType::class)
            ->find($card->getGeneralTypeId());


        $pgtid = $card->getGeneralType()->getParentId();
        if($pgtid == null) $pgtid = $card->getGeneralTypeId();

        $mainFoto = '';
        foreach($card->getFotos() as $foto){
            if ($foto->getIsMain()) $mainFoto = '/assets/images/cards/'.$foto->getFolder().'/'.$foto->getId().'.jpg';
        }


        $seo = [];
        if ($card->getServiceTypeId() == 1) $seo['service'] = 'Прокат';
        if ($card->getServiceTypeId() == 2) $seo['service'] = 'Аренда';
        $seo['type']['singular'] = $general->getChegoSingular();
        $seo['type']['plural'] = $general->getChegoPlural();
        $seo['mark'] = $mark->getHeader();
        $seo['model'] = $model->getHeader();
        $seo['city']['chto'] = $city->getHeader();
        $seo['city']['gde'] = $city->getGde();

        return $this->render('card/card_show.html.twig', [

            'card' => $card,
            'streetView' => $streetView,
            'video' => $video,

            'sub_fields' =>$subFields,

//            'general_type' => $card->getGeneralTypeId(),
            'city' => $city,

            'countries' => $mc->getCountry(),
            'countryCode' => $city->getCountry(),
            'regionId' => $city->getParentId(),
            'regions' => $mc->getRegion($city->getCountry()),
            'cities' => $mc->getCities($city->getParentId()),
            'cityId' => $card->getCityId(),

            'generalTopLevel' => $mgt->getTopLevel(),
            'generalSecondLevel' => $mgt->getSecondLevel($card->getGeneralType()->getParentId()),
            'pgtid' => $pgtid,
            'gtid' => $card->getGeneralTypeId(),
            'similar' => $similar,

            'mark_groups' => $mm->getGroups(),
            'mark' => $mark,
            'model' => $model,
            'marks' => $marks,
            'models' => $models,
            'general' => $general,

            'user_foto' => $user_foto,
            'mainFoto' => $mainFoto,
            'seo' => $seo

        ]);
    }
}