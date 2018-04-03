<?php

namespace AppBundle\Repository;

/**
 * CardRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CardRepository extends \Doctrine\ORM\EntityRepository
{
    public function getLimitedSlider($gt)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT c.id FROM AppBundle:Card c JOIN c.tariff t WHERE c.generalTypeId = '.$gt.' ORDER BY t.weight DESC, c.dateTariffStart DESC, c.dateUpdate DESC');
        $query->setMaxResults(7);
        foreach ($query->getScalarResult() as $cars_id) $cars_ids[] = $cars_id['id'];
        $dql = 'SELECT c,f,p FROM AppBundle:Card c JOIN c.tariff t LEFT JOIN c.fotos f LEFT JOIN c.cardPrices p WHERE c.id IN ('.implode(",",$cars_ids).') ORDER BY t.weight DESC, c.dateTariffStart DESC, c.dateUpdate DESC';
        $query = $em->createQuery($dql);
        return $query->getResult();
    }

    public function getLimitedSliders($gts,$cityId)
    {
        $cars_ids = [];
        $result = [];
        $em = $this->getEntityManager();
        foreach ($gts as $gt) {
            $query = $em->createQuery('SELECT c.id FROM AppBundle:Card c JOIN c.tariff t WHERE c.generalTypeId = ' . $gt . ' AND c.cityId= ?1 ORDER BY c.isTop DESC, c.dateTariffStart DESC, c.dateUpdate DESC');
            if ($cityId>1251) $query->setParameter(1, $cityId);
            else $query->setParameter(1, 2407); // los angeles
            $query->setMaxResults(7);
            if(count($query->getScalarResult())<2) {
                $query = $em->createQuery('SELECT c.id FROM AppBundle:Card c JOIN c.tariff t WHERE c.generalTypeId = ' . $gt . ' AND c.cityId > 1251 ORDER BY c.isTop DESC, c.dateTariffStart DESC, c.dateUpdate DESC');
                $query->setMaxResults(7);
            }

            foreach ($query->getScalarResult() as $cars_id) $cars_ids[] = $cars_id['id'];
        }



        $dql = 'SELECT c,f,p,g,m FROM AppBundle:Card c JOIN c.tariff t LEFT JOIN c.fotos f LEFT JOIN c.cardPrices p LEFT JOIN c.city g LEFT JOIN c.markModel m WHERE c.id IN ('.implode(",",$cars_ids).') ORDER BY c.isTop DESC, c.dateTariffStart DESC, c.dateUpdate DESC';
        $query = $em->createQuery($dql);
        foreach($query->getResult() as $row){
            $result[$row->getGeneralTypeId()][] = $row;
        }
        return $result;
    }

    public function getTopSlider($cityId)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT c.id FROM AppBundle:Card c JOIN c.tariff t WHERE c.cityId=?1 ORDER BY c.isBest DESC, c.dateTariffStart DESC, c.dateUpdate DESC');
        if ($cityId>1251) $query->setParameter(1, $cityId);
        else $query->setParameter(1, 2407); // los angeles
        $query->setMaxResults(20);

        if(count($query->getScalarResult())<6) {
            $query = $em->createQuery('SELECT c.id FROM AppBundle:Card c JOIN c.tariff t WHERE c.cityId > 1251 ORDER BY c.isBest DESC, c.dateTariffStart DESC, c.dateUpdate DESC');
            $query->setMaxResults(20);
        }


        foreach ($query->getScalarResult() as $cars_id) $cars_ids[] = $cars_id['id'];
        $dql = 'SELECT c,f,p,g,m FROM AppBundle:Card c JOIN c.tariff t LEFT JOIN c.fotos f LEFT JOIN c.cardPrices p LEFT JOIN c.city g LEFT JOIN c.markModel m WHERE c.id IN ('.implode(",",$cars_ids).') ORDER BY c.isBest DESC, c.dateTariffStart DESC, c.dateUpdate DESC';
        $query = $em->createQuery($dql);
        return $query->getResult();
    }

    public function getTopOne($gt)
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery('SELECT c,f,p FROM AppBundle:Card c JOIN c.tariff t LEFT JOIN c.fotos f LEFT JOIN c.cardPrices p WHERE c.generalTypeId = '.$gt.' ORDER BY t.weight DESC, c.dateTariffStart DESC, c.views DESC');
        $query->setMaxResults(1);
//        $q = $query->getResult();
//        $dql = 'SELECT c,f,p FROM AppBundle:Card c JOIN c.tariff t LEFT JOIN c.fotos f LEFT JOIN c.cardPrices p WHERE c.id = '.$q[0]->getId();
//        $query = $em->createQuery($dql);
        $res = $query->getResult();
        return $res[0];
    }
}
