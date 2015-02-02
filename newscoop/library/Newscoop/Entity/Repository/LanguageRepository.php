<?php
/**
 * @package Newscoop
 * @subpackage Languages
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl.txt
 */

namespace Newscoop\Entity\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\QueryBuilder,
    Newscoop\Entity\Language;

/**
 * Language repository
 */
class LanguageRepository extends EntityRepository
{
    /**
     * Save language
     *
     * @param  Newscoop\Entity\Language $language
     * @param  array                    $values
     * @return void
     */
    public function save(Language $language, array $values)
    {
        $em = $this->getEntityManager();

        $language->setName($values['name'])
            ->setNativeName($values['native_name'])
            ->setCodePage($values['code_page'])
            ->setCode($values['code'])
            ->setMonth1($values['month1'])
            ->setMonth2($values['month2'])
            ->setMonth3($values['month3'])
            ->setMonth4($values['month4'])
            ->setMonth5($values['month5'])
            ->setMonth6($values['month6'])
            ->setMonth7($values['month7'])
            ->setMonth8($values['month8'])
            ->setMonth9($values['month9'])
            ->setMonth10($values['month10'])
            ->setMonth11($values['month11'])
            ->setMonth12($values['month12'])
            ->setShortMonth1($values['short_month1'])
            ->setShortMonth2($values['short_month2'])
            ->setShortMonth3($values['short_month3'])
            ->setShortMonth4($values['short_month4'])
            ->setShortMonth5($values['short_month5'])
            ->setShortMonth6($values['short_month6'])
            ->setShortMonth7($values['short_month7'])
            ->setShortMonth8($values['short_month8'])
            ->setShortMonth9($values['short_month9'])
            ->setShortMonth10($values['short_month10'])
            ->setShortMonth11($values['short_month11'])
            ->setShortMonth12($values['short_month12'])
            ->setDay1($values['day1'])
            ->setDay2($values['day2'])
            ->setDay3($values['day3'])
            ->setDay4($values['day4'])
            ->setDay5($values['day5'])
            ->setDay6($values['day6'])
            ->setDay7($values['day7'])
            ->setShortDay1($values['short_day1'])
            ->setShortDay2($values['short_day2'])
            ->setShortDay3($values['short_day3'])
            ->setShortDay4($values['short_day4'])
            ->setShortDay5($values['short_day5'])
            ->setShortDay6($values['short_day6'])
            ->setShortDay7($values['short_day7'])
            ->setRFC3066bis($values['RFC3066bis']);

        $em->persist($language);
        $em->flush();
    }

    /**
     * Delete language
     *
     * @param  int  $id
     * @return void
     */
    public function delete($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference('Newscoop\Entity\Language', $id);
        $em->remove($proxy);
        $em->flush();
    }

    /**
     * Get languages
     *
     * @return array
     */
    public function getLanguages()
    {
        $qb = $this->createQueryBuilder('l');

        return $qb->orderBy('l.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get languages query
     *
     * @return array
     */
    public function getAllLanguages($order = 'asc')
    {
        $qb = $this->createQueryBuilder('l');

        return $qb->orderBy('l.name', $order)
            ->getQuery();
    }

    /**
     * Check if in use
     *
     * @param  Language $language
     * @return bool
     */
    public function isUsed(Language $language)
    {
        $em = $this->getEntityManager();

        $dql = "SELECT COUNT(p.id) FROM Newscoop\Entity\Publication p WHERE p.language = ?1";
        $query = $em->createQuery($dql);
        $query->setParameter(1, $language);
        if ($query->getSingleScalarResult()) {
            return true;
        }

        $dql = "SELECT COUNT(i.number) FROM Newscoop\Entity\Issue i WHERE i.language = ?1";
        $query = $em->createQuery($dql);
        $query->setParameter(1, $language);
        if ($query->getSingleScalarResult()) {
            return true;
        }

        $dql = "SELECT COUNT(s.number) FROM Newscoop\Entity\Section s WHERE s.language = ?1";
        $query = $em->createQuery($dql);
        $query->setParameter(1, $language);
        if ($query->getSingleScalarResult()) {
            return true;
        }

        $dql = "SELECT COUNT(a.number) FROM Newscoop\Entity\Article a WHERE a.language = ?1";
        $query = $em->createQuery($dql);
        $query->setParameter(1, $language);
        if ($query->getSingleScalarResult()) {
            return true;
        }

        $dql = "SELECT COUNT(c.code) FROM Newscoop\Entity\Country c WHERE c.language = ?1";
        $query = $em->createQuery($dql);
        $query->setParameter(1, $language);
        if ($query->getSingleScalarResult()) {
            return true;
        }

        return false;
    }

    /**
     * Tries to find Language entity by string. String should be in correct RFC3066bis standard.
     *
     * @param string $languageString  Language string
     * @param string $firstResultOnly Return first result
     *
     * @return \Newscoop\Entity\Language|null Returns found entity or null
     */
    public function findByRFC3066bis($languageString, $firstResultOnly=true)
    {
        if (count(explode('-', $languageString)) < 2) {
            throw new \Exception('Language string in correct according to RFC3066bis standard.', 1);
        }

        $queryBuilder = $this->getEntityManager()
            ->getRepository('\Newscoop\Entity\Language')
            ->createQueryBuilder('L');

        $localeArray    = \Locale::parseLocale($languageString);

        if (array_key_exists('language', $localeArray) && array_key_exists('region', $localeArray) && array_key_exists('script', $localeArray)) {
            $queryBuilder
                ->where('L.RFC3066bis = :longLocale')
                ->orWhere('L.RFC3066bis = :shortLocale')
                ->setParameter('longLocale', $localeArray['language'].'-'.$localeArray['script'].'-'.$localeArray['region'])
                ->setParameter('shortLocale', $localeArray['language'].'-'.$localeArray['region']);

        } elseif (array_key_exists('language', $localeArray) && array_key_exists('script', $localeArray)) {
            $queryBuilder
                ->where('L.RFC3066bis = :strictLocale')
                ->orWhere('L.RFC3066bis LIKE :nonStrictLocale')
                ->setParameter('strictLocale', $localeArray['language'].'-'.$localeArray['script'])
                ->setParameter('nonStrictLocale', $localeArray['language'].'-'.$localeArray['script'].'%');

        } elseif (array_key_exists('language', $localeArray) && array_key_exists('region', $localeArray)) {
            $queryBuilder
                ->where('L.RFC3066bis = :strictLocale')
                ->orWhere('L.RFC3066bis LIKE :nonStrictLocale')
                ->setParameter('strictLocale', $localeArray['language'].'-'.$localeArray['region'])
                ->setParameter('nonStrictLocale', $localeArray['language'].'-%'.$localeArray['region']);

        } else {
            throw new \Exception('Parsed RFC3066bis string did not match expected results.', 1);
        }

        $foundLanguages = $queryBuilder->getQuery()->getResult();

        return ($firstResultOnly) ? $foundLanguages[0] : $foundLanguages;
    }
}
