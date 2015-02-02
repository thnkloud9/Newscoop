<?php
/**
 * @package Newscoop
 * @copyright 2011 Sourcefabric o.p.s.
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 */

namespace Newscoop\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Newscoop\Entity\User;
use Newscoop\User\UserCriteria;
use Newscoop\ListResult;
use Newscoop\Search\RepositoryInterface;

/**
 * User repository
 */
class UserRepository extends EntityRepository implements RepositoryInterface
{
    /** @var array */
    protected $setters = array(
        'username' => 'setUsername',
        'password' => 'setPassword',
        'first_name' => 'setFirstName',
        'last_name' => 'setLastName',
        'email' => 'setEmail',
        'status' => 'setStatus',
        'is_admin' => 'setAdmin',
        'is_public' => 'setPublic',
        'image' => 'setImage',
    );

    /**
     * Save user
     *
     * @param  Newscoop\Entity\User $user
     * @param  array                $values
     * @return void
     */
    public function save($user, array $values)
    {
        $this->setProperties($user, $values);

        if (!$user->getUsername()) {
            throw new \InvalidArgumentException('username_empty');
        }

        if (!$this->isUnique('username', $user->getUsername(), $user->getId())) {
            throw new \InvalidArgumentException('username_conflict');
        }

        if (!$user->getEmail()) {
            throw new \InvalidArgumentException('email_empty');
        }

        if (!$this->isUnique('email', $user->getEmail(), $user->getId())) {
            throw new \InvalidArgumentException('email_conflict');
        }

        if (array_key_exists('attributes', $values)) {
            $this->setAttributes($user, (array) $values['attributes']);
        }

        if (array_key_exists('user_type', $values)) {
            $this->setUserTypes($user, (array) $values['user_type']);
        }

        if (array_key_exists('author', $values)) {
            $author = null;
            if (!empty($values['author'])) {
                $author = $this->getEntityManager()->getReference('Newscoop\Entity\Author', $values['author']);
            }
            $user->setAuthor($author);
        }

        $this->getEntityManager()->persist($user);
    }

    /**
     * Set user properties
     *
     * @param  Newscoop\Entity\User $user
     * @param  array                $values
     * @return void
     */
    private function setProperties(User $user, array $values)
    {
        foreach ($this->setters as $property => $setter) {
            if (array_key_exists($property, $values)) {
                $user->$setter($values[$property]);
            }
        }
    }

    /**
     * Set user attributes
     *
     * @param  Newscoop\Entity\User $user
     * @param  array                $attributes
     * @return void
     */
    private function setAttributes(User $user, array $attributes)
    {
        if (!$user->getId()) { // must persist user before adding attributes
            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }

        foreach ($attributes as $name => $value) {
            $user->addAttribute($name, $value);
        }
    }

    /**
     * Set user types
     *
     * @param  Newscoop\Entity\User $user
     * @param  array                $types
     * @return void
     */
    private function setUserTypes(User $user, array $types)
    {
        $user->getUserTypes()->clear();
        foreach ($types as $type) {
            $user->addUserType($this->getEntityManager()->getReference('Newscoop\Entity\User\Group', $type));
        }
    }

    /**
     * Test if property value is unique
     *
     * @param  string $property
     * @param  string $value
     * @param  int    $id
     * @return bool
     */
    public function isUnique($property, $value, $id = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from('Newscoop\Entity\User', 'u')
            ->where("LOWER(u.{$property}) = LOWER(?0)");

        $params = array($value);

        if ($id !== null) {
            $qb->andWhere('u.id <> ?1');
            $params[] = $id;
        }

        $qb->setParameters($params);

        return !$qb->getQuery()->getSingleScalarResult();
    }

    public function getActiveUsers($public = true)
    {
        $em = $this->getEntityManager();

        $queryBuilder = $em->getRepository('Newscoop\Entity\User')
            ->createQueryBuilder('u')
            ->where('u.status = :status')
            ->andWhere('u.is_public = :public')
            ->setParameters(array(
                'status' => User::STATUS_ACTIVE,
                'public' => $public
            ));

        $query = $queryBuilder->getQuery();

        return $query;
    }

    /**
     * Get getLatelyLoggedInUsers (logged in x days before today)
     *
     * @return int
     */
    public function getLatelyLoggedInUsers($daysNumber = 7, $count = false)
    {
        $query = $this->createQueryBuilder('u');

        if ($count) {
            $query->select('COUNT(u)');
        }

        $query = $query->where('u.lastLogin > :date')
            ->getQuery();

        $date = new \DateTime();
        $query->setParameter('date', $date->modify('- '.$daysNumber.' days'));

        return $query;
    }

    public function getOneActiveUser($id, $public = true)
    {
        $em = $this->getEntityManager();

        $queryBuilder = $em->getRepository('Newscoop\Entity\User')
            ->createQueryBuilder('u')
            ->where('u.status = :status')
            ->andWhere('u.id = :id')
            ->setParameters(array(
                'status' => User::STATUS_ACTIVE,
                'id' => $id
            ));

        if ($public) {
            $queryBuilder->andWhere('u.is_public = :public')
                ->setParameter('public', $public);
        }

        $query = $queryBuilder->getQuery();

        return $query;
    }

    /**
     * Find active members of community
     *
     * @param  bool      $countOnly
     * @param  int       $offset
     * @param  int       $limit
     * @param  array     $editorRoles
     * @return array|int
     */
    public function findActiveUsers($countOnly, $offset, $limit, array $editorRoles)
    {
        $expr = $this->getEntityManager()->getExpressionBuilder();
        $qb = $this->createPublicUserQueryBuilder();

        $editorIds = $this->getEditorIds($editorRoles);
        if (!empty($editorIds)) {
            $qb->andWhere($qb->expr()->in('u.id', $editorIds));
        }

        if ($countOnly) {
            $qb->select('COUNT(u.id)');

            return $qb->getQuery()->getSingleScalarResult();
        }

        $qb->addOrderBy('u.id', 'ASC');
        $qb->groupBy('u.id');
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();

        return $results;
    }

    public function findVerifiedUsers($countOnly, $offset, $limit)
    {
        if ($countOnly) {
            $qb = $this->getEntityManager()->createQuery('SELECT COUNT(u.id) FROM Newscoop\Entity\User u JOIN u.attributes a WHERE a.attribute = \'is_verified\' AND a.value = 1');

            return $qb->getSingleScalarResult();
        }

        $qb = $this->getEntityManager()->createQuery('SELECT u FROM Newscoop\Entity\User u JOIN u.attributes a WHERE a.attribute = \'is_verified\' AND a.value = 1');
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb->getResult();
    }

    /**
     * Create query builder for public users
     *
     * @return Doctrine\ORM\QueryBuilder
     */
    private function createPublicUserQueryBuilder()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.status = :status')
            ->andWhere('u.is_public = :public')
            ->setParameter('status', User::STATUS_ACTIVE)
            ->setParameter('public', true);
    }

    /**
     * Get editor ids
     *
     * @param  array $editorRoles
     * @return array
     */
    private function getEditorIds(array $editorRoles)
    {
        if (empty($editorRoles)) {
            return array();
        }

        $expr = $this->getEntityManager()->getExpressionBuilder();
        $query = $this->createQueryBuilder('u')
            ->select('DISTINCT(u.id)')
            ->innerJoin('u.groups', 'g', Expr\Join::WITH, $expr->in('g.id', $editorRoles))
            ->getQuery();

        $ids = array_map(function ($row) {
            return (int) $row['id'];
        }, $query->getResult());

        return $ids;
    }

    /**
     * Get user points select statement
     *
     * @return string
     */
    private function getUserPointsSelect()
    {
        $commentsCount = "(SELECT COUNT(c)";
        $commentsCount .= " FROM Newscoop\Entity\Comment c, Newscoop\Entity\Comment\Commenter cc";
        $commentsCount .= " WHERE c.commenter = cc AND cc.user = u AND c.status = 0) as comments";

        return "{$commentsCount}";
    }

    /**
     * Return Users if their last name begins with one of the letter passed in.
     *
     * @param array $letters = ['a', 'b']
     *
     * @return array Newscoop\Entity\User
     */
    public function findUsersLastNameInRange($letters, $countOnly, $offset, $limit, $firstName = false)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        if ($countOnly) {
            $qb->select('COUNT(u.id)');
        } else {
            $qb->select('u');
        }

        $qb->from('Newscoop\Entity\User', 'u');

        $qb->where($qb->expr()->eq("u.status", User::STATUS_ACTIVE));
        $qb->andWhere($qb->expr()->eq("u.is_public", true));

        $letterIndex = $qb->expr()->orx();
        for ($i=0; $i < count($letters); $i++) {
            $letterIndex->add($qb->expr()->like("LOWER(u.last_name)", "'$letters[$i]%'"));
            if ($firstName) {
                $letterIndex->add($qb->expr()->like("LOWER(u.first_name)", "'$letters[$i]%'"));
            }
        }
        $qb->andWhere($letterIndex);

        if ($countOnly === false) {
            $qb->orderBy('u.username', 'ASC');
            $qb->addOrderBy('u.id', 'ASC');

            $qb->setFirstResult($offset);
            $qb->setMaxResults($limit);

            return $qb->getQuery()->getResult();
        } else {
            return $qb->getQuery()->getOneOrNullResult();
        }
    }

    /**
     * Return Users if any of their searched attributes contain the searched term.
     *
     * @param string $search
     *
     * @param array $attributes
     *
     * @return array Newscoop\Entity\User
     */
    public function searchUsers($search, $countOnly, $offset, $limit, $attributes = array("first_name", "last_name", "username"))
    {
        $keywords = explode(" ", $search);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from('Newscoop\Entity\User', 'u');

        $outerAnd = $qb->expr()->andx();

        for ($i=0; $i < count($keywords); $i++) {
            $innerOr = $qb->expr()->orx();
            for ($j=0; $j < count($attributes); $j++) {
                $innerOr->add($qb->expr()->like("u.{$attributes[$j]}", "'$keywords[$i]%'"));
            }
            $outerAnd->add($innerOr);
        }

        $outerAnd->add($qb->expr()->eq("u.status", User::STATUS_ACTIVE));
        $outerAnd->add($qb->expr()->eq("u.is_public", true));

        $qb->where($outerAnd);

        $qb->orderBy('u.last_name', 'ASC');
        $qb->addOrderBy('u.first_name', 'ASC');
        $qb->addOrderBy('u.id', 'DESC');

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get random list of users
     *
     * @param  int   $limit
     * @return array
     */
    public function getRandomList($limit)
    {
        $query = $this->getEntityManager()->createQuery("SELECT u, RAND() as random FROM {$this->getEntityName()} u WHERE u.status = :status AND u.is_public = :public ORDER BY random");
        $query->setMaxResults($limit);
        $query->setParameters(array(
            'status' => User::STATUS_ACTIVE,
            'public' => True,
        ));

        $users = array();
        foreach ($query->getResult() as $result) {
            $users[] = $result[0];
        }

        return $users;
    }

    /**
     * Get editors
     *
     * @param  int   $blogRole
     * @param  int   $limit
     * @param  int   $offset
     * @return array
     */
    public function findEditors($blogRole, $limit, $offset)
    {
        $query = $this->createQueryBuilder('u')
            ->leftJoin('u.groups', 'g', Expr\Join::WITH, 'g.id = ' . $blogRole)
            ->where('u.is_admin = :admin')
            ->andWhere('u.status = :status')
            ->andWhere('u.author IS NOT NULL')
            ->andWhere('g.id IS NULL')
            ->orderBy('u.username', 'asc')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery();

        $query->setParameters(array(
            'admin' => 1,
            'status' => User::STATUS_ACTIVE,
        ));

        return $query->getResult();
    }

    /**
     * Get editors count
     *
     * @param  int $blogRole
     * @return int
     */
    public function getEditorsCount($blogRole)
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(u)')
            ->from($this->getEntityName(), 'u')
            ->leftJoin('u.groups', 'g', Expr\Join::WITH, 'g.id = ' . $blogRole)
            ->where('u.is_admin = :admin')
            ->andWhere('u.status = :status')
            ->andWhere('u.author IS NOT NULL')
            ->andWhere('g.id IS NULL')
            ->getQuery();

        $query->setParameters(array(
            'admin' => 1,
            'status' => User::STATUS_ACTIVE,
        ));

        return $query->getSingleScalarResult();
    }

    /**
     * Get total users count
     *
     * @return int
     */
    public function countAll()
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u)')
            ->from($this->getEntityName(), 'u')
            ->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Get users count for given criteria
     *
     * @param  array $criteria
     * @return int
     */
    public function countBy(array $criteria)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(u)')
            ->from($this->getEntityName(), 'u');

        foreach ($criteria as $property => $value) {
            if (!is_array($value)) {
                $queryBuilder->andWhere("u.$property = :$property");
            }
        }

        $query = $queryBuilder->getQuery();
        foreach ($criteria as $property => $value) {
            if (!is_array($value)) {
                $query->setParameter($property, $value);
            }
        }

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Delete user
     *
     * @param  Newscoop\Entity\User $user
     * @return void
     */
    public function delete(User $user)
    {
        if ($user->isPending()) {
            $this->getEntityManager()->remove($user);
        } else {
            $user->setStatus(User::STATUS_DELETED);
            $user->setEmail(null);
            $user->setFirstName(null);
            $user->setLastName(null);
            $this->removeAttributes($user);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Remove user attributes
     *
     * @param  Newscoop\Entity\User $user
     * @return void
     */
    private function removeAttributes(User $user)
    {
        $attributes = $this->getEntityManager()->getRepository('Newscoop\Entity\UserAttribute')->findBy(array(
            'user' => $user->getId(),
        ));

        foreach ($attributes as $attribute) {
            $user->addAttribute($attribute->getName(), null);
            $this->getEntityManager()->remove($attribute);
        }
    }

    /**
     * Find users for indexing
     *
     * @return array
     */
    public function getBatch($count = self::BATCH_COUNT, array $filter = null)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.indexed IS NULL OR u.indexed < u.updated')
            ->getQuery()
            ->setMaxResults(50)
            ->getResult();
    }

    /**
     * Set indexed now
     *
     * @param  array $users
     * @return void
     */
    public function setIndexedNow(array $users)
    {
        if (empty($users)) {
            return;
        }

        $this->getEntityManager()->createQuery('UPDATE Newscoop\Entity\User u SET u.indexed = CURRENT_TIMESTAMP() WHERE u.id IN (:users)')
            ->setParameter('users', array_map(function ($user) { return $user->getId(); }, $users))
            ->execute();
    }

    /**
     * Set indexed null
     *
     * @return void
     */
    public function setIndexedNull(array $items = null)
    {
        $this->getEntityManager()->createQuery('UPDATE Newscoop\Entity\User u SET u.indexed = NULL')
            ->execute();
    }

    /**
     * Get newscoop login count
     *
     * @return int
     */
    public function getNewscoopLoginCount()
    {
        $query = $this->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->leftJoin('u.identities', 'ui')
            ->where('ui.user IS NULL')
            ->andWhere('u.status = :status')
            ->getQuery();

        $query->setParameter('status', User::STATUS_ACTIVE);

        return $query->getSingleScalarResult();
    }

    /**
     * Get external login count
     *
     * @return int
     */
    public function getExternalLoginCount()
    {
        $query = $this->createQueryBuilder('u')
            ->select('COUNT(u)')
            ->leftJoin('u.identities', 'ui')
            ->where('ui.user IS NOT NULL')
            ->andWhere('u.status = :status')
            ->getQuery();

        $query->setParameter('status', User::STATUS_ACTIVE);

        return $query->getSingleScalarResult();
    }

    /**
     * Set user points
     *
     * @param  Newscoop\Entity\User|null $user
     * @param  string|int                $authorId
     * @return void
     */
    public function setUserPoints(User $user = null, $authorId = null)
    {
        $em = $this->getEntityManager();

        if (!is_null($authorId)) {
            $user = $em->getRepository('Newscoop\Entity\User')
                ->findOneByAuthor($authorId);
        }

        if (!$user) {
            return false;
        }

        $query = $this->createQueryBuilder('u')
            ->select('u.id, ' . $this->getUserPointsSelect())
            ->where('u.id = :user')
            ->getQuery();

        $query->setParameter('user', $user->getId());
        $result = $query->getSingleResult();

        $articlesCount = $em->getRepository('Newscoop\Entity\Article')
            ->countByAuthor($user);

        $total = (int) $result['comments'] + $articlesCount;

        if ($user) {
            $user->setPoints($total);
            $em->flush();
        }
    }

    /**
     * Get list for given criteria
     *
     * @param  Newscoop\User\UserCriteria $criteria
     * @return Newscoop\ListResult
     */
    public function getListByCriteria(UserCriteria $criteria, $results = true)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->andWhere('u.status = :status')
            ->setParameter('status', $criteria->status);

        if (!is_null($criteria->is_public)) {
            $qb->andWhere('u.is_public = :is_public')
                ->setParameter('is_public', $criteria->is_public);
        }

        foreach ($criteria->perametersOperators as $key => $operator) {
            $qb->andWhere('u.'.$key.' = :'.$key)
                ->setParameter($key, $criteria->$key);
        }

        if ($criteria->is_author) {
            $qb->andWhere($qb->expr()->isNotNull("u.author"));
        }

        if (!empty($criteria->groups)) {
            $em = $this->getEntityManager();
            $groupRepo = $em->getRepository('Newscoop\Entity\User\Group');
            $users = array();
            foreach ($criteria->groups as $groupId) {
                $group = $groupRepo->findOneById($groupId);
                if ($group instanceof \Newscoop\Entity\User\Group) {
                    $users = array_unique(array_merge($users, array_keys($group->getUsers()->toArray())), SORT_REGULAR);
                }
            }
            $op = $criteria->excludeGroups ? 'notIn' : 'in';
            $qb->andWhere($qb->expr()->$op('u.id', ':userIds'));
            $qb->setParameter('userIds', $users);
        }

        if (!empty($criteria->query)) {
            $qb->andWhere($qb->expr()->orX("(u.username LIKE :query)", "(u.email LIKE :query)"));
            $qb->setParameter('query', '%' . trim($criteria->query, '%') . '%');
        }

        if (!empty($criteria->query_name)) {
            $qb->andWhere($qb->expr()->orX("(u.last_name LIKE :query)", "(u.first_name LIKE :query)"));
            $qb->setParameter('query', trim($criteria->query_name, '%') . '%');
            $qb->groupBy('u.last_name', 'u.first_name');
        }

        if (!empty($criteria->nameRange)) {
            $this->addNameRangeWhere($qb, $criteria->nameRange);
        }

        if (!empty($criteria->lastLoginDays)) {
            $qb->andWhere('u.lastLogin > :lastLogin');
            $date = new \DateTime();
            $qb->setParameter('lastLogin', $date->modify('- '.$criteria->lastLoginDays.' days'));
        }

        if (count($criteria->attributes) > 0) {
            $qb->leftJoin('u.attributes', 'ua');
            $qb->andWhere('ua.attribute = ?1')
                ->andWhere('ua.value = ?2')
                ->setParameter(1, $criteria->attributes[0])
                ->setParameter(2, $criteria->attributes[1]);
        }

        $list = new ListResult();
        $countQb = clone $qb;
        $countQb->select('COUNT(u)')->resetDQLPart('groupBy');
        $list->count = (int) $countQb->getQuery()->getSingleScalarResult();

        if ($criteria->firstResult != 0) {
            $qb->setFirstResult($criteria->firstResult);
        }

        if ($criteria->maxResults != 0) {
            $qb->setMaxResults($criteria->maxResults);
        }

        $metadata = $this->getClassMetadata();
        foreach ($criteria->orderBy as $key => $order) {
            if (array_key_exists($key, $metadata->columnNames)) {
                $key = 'u.' . $key;
            }

            $qb->orderBy($key, $order);
        }

        if (!$results) {
            return array($qb, $list->count);
        }

        $list->items = $qb->getQuery()->getResult();

        return $list;
    }

    /**
     * Add name first letter where condition to query builder
     *
     * @param  Doctrine\ORM\QueryBuilder $qb
     * @param  array                     $letters
     * @return void
     */
    private function addNameRangeWhere($qb, array $letters)
    {
        $orx = $qb->expr()->orx();
        foreach ($letters as $letter) {
            $orx->add($qb->expr()->like(
                'u.username',
                $qb->expr()->literal(substr($letter, 0, 1) . '%')
            ));
        }

        $qb->andWhere($orx);
    }
}
