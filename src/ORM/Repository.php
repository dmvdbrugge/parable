<?php

namespace Parable\ORM;

class Repository
{
    /** @var \Parable\ORM\Database */
    protected $database;

    /** @var \Parable\ORM\Model */
    protected $model;

    /** @var bool */
    protected $onlyCount = false;

    /** @var array */
    protected $orderBy = [];

    /** @var array */
    protected $limitOffset = [];

    /** @var bool */
    protected $returnOne = false;

    public function __construct(
        \Parable\ORM\Database $database
    ) {
        $this->database = $database;
    }

    /**
     * Generate a query set to use the current Model's table name & key
     *
     * @return \Parable\ORM\Query
     */
    public function createQuery()
    {
        $query = \Parable\ORM\Query::createInstance();
        $query->setTableName($this->getModel()->getTableName());
        $query->setTableKey($this->getModel()->getTableKey());
        if ($this->onlyCount) {
            $query->select(['count(*)']);
        }
        if (!empty($this->orderBy)) {
            $query->orderBy($this->orderBy['key'], $this->orderBy['direction']);
        }
        if (!empty($this->limitOffset)) {
            $query->limitOffset($this->limitOffset['limit'], $this->limitOffset['offset']);
        }
        if ($this->returnOne) {
            $query->limitOffset(1);
        }
        return $query;
    }

    /**
     * Returns all rows for this model type
     *
     * @return \Parable\ORM\Model[]|\Parable\ORM\Model
     */
    public function getAll()
    {
        $query = $this->createQuery();
        $result = $this->database->query($query);

        $entities = [];
        if ($result) {
            $result = $result->fetchAll(\PDO::FETCH_ASSOC);
            $entities = $this->handleResult($result);
        }
        if ($this->returnOne && is_array($entities)) {
            return current($entities);
        }
        return $entities;
    }

    /**
     * Returns a single model
     *
     * @param int $id
     *
     * @return null|\Parable\ORM\Model
     */
    public function getById($id)
    {
        $tableKey = $this->getModel()->getTableKey();

        if (is_array($tableKey)) {
            // Model is a CombinedKey Model. User made a dumb decision calling this function anyway.
            // Use only the first key instead of breaking things.
            $tableKey = reset($tableKey);
        }

        $query = $this->createQuery();
        $query->where(
            $query->buildAndSet([$tableKey, '=', $id])
        );
        $result = $this->database->query($query);

        $model = null;
        if ($result) {
            $result = $result->fetchAll(\PDO::FETCH_ASSOC);
            $entities = $this->handleResult($result);
            $model = current($entities);
        }
        return $model;
    }

    /**
     * @param string[] $keyValues
     *
     * @return null|\Parable\ORM\Model|\Parable\ORM\Model\CombinedKey
     */
    public function getByCombinedKey(array $keyValues)
    {
        $tableKeys = $this->getModel()->getTableKey();

        if (!($this->getModel() instanceof Model\CombinedKey) || !is_array($tableKeys)) {
            // User shouldn't have called this. Gracefully fall back.
            return $this->getById(reset($keyValues));
        }

        $conditions = [];
        foreach ($tableKeys as $i => $key) {
            $value = isset($keyValues[$i]) ? $keyValues[$i] : null;
            $conditions[] = [$key, '=', $value];
        }

        $query = $this->createQuery();
        $query->where(
            $query->buildAndSet($conditions)
        );
        $result = $this->database->query($query);

        $model = null;
        if ($result) {
            $result = $result->fetchAll(\PDO::FETCH_ASSOC);
            $entities = $this->handleResult($result);
            $model = current($entities);
        }
        return $model;
    }

    /**
     * Returns all rows matching specific condition parameters given
     *
     * @param string     $key
     * @param string     $comparator
     * @param mixed|null $value
     * @param string     $andOr
     *
     * @return \Parable\ORM\Model[]|\Parable\ORM\Model
     * @throws \Parable\ORM\Exception
     */
    public function getByCondition($key, $comparator, $value = null, $andOr = \Parable\ORM\Query\ConditionSet::SET_AND)
    {
        $query = $this->createQuery();
        if ($andOr === \Parable\ORM\Query\ConditionSet::SET_AND) {
            $conditionSet = $query->buildAndSet([$key, $comparator, $value]);
        } elseif ($andOr === \Parable\ORM\Query\ConditionSet::SET_OR) {
            $conditionSet = $query->buildOrSet([$key, $comparator, $value]);
        } else {
            throw new \Parable\ORM\Exception('Invalid andOr type given.');
        }
        return $this->getByConditionSet($conditionSet);
    }

    /**
     * Returns all rows matching specific conditionSet passed
     *
     * @param \Parable\ORM\Query\ConditionSet $conditionSet
     *
     * @return \Parable\ORM\Model[]|\Parable\ORM\Model
     */
    public function getByConditionSet(\Parable\ORM\Query\ConditionSet $conditionSet)
    {
        return $this->getByConditionSets([$conditionSet]);
    }

    /**
     * Returns all rows matching all conditions passed
     *
     * @param array $conditionSets
     *
     * @return \Parable\ORM\Model[]|\Parable\ORM\Model
     */
    public function getByConditionSets(array $conditionSets)
    {
        $query = $this->createQuery();
        $query->whereMany($conditionSets);
        $result = $this->database->query($query);

        $entities = [];
        if ($result) {
            $result = $result->fetchAll(\PDO::FETCH_ASSOC);
            $entities = $this->handleResult($result);
        }
        if ($this->returnOne && is_array($entities)) {
            return current($entities);
        }
        return $entities;
    }

    /**
     * Allow multiple orders by $key in $direction
     *
     * @param string $key
     * @param string $direction ASC by default
     *
     * @return $this
     */
    public function orderBy($key, $direction = \Parable\ORM\Query::ORDER_ASC)
    {
        $this->orderBy = ['key' => $key, 'direction' => $direction];
        return $this;
    }

    /**
     * Sets the limitOffset
     *
     * @param int      $limit
     * @param null|int $offset
     *
     * @return $this
     */
    public function limitOffset($limit, $offset = null)
    {
        $this->limitOffset = ['limit' => $limit, 'offset' => $offset];
        return $this;
    }

    /**
     * Sets the repo to return only one (the first), the same as getById always does
     *
     * @return $this
     */
    public function returnOne()
    {
        $this->returnOne = true;
        return $this;
    }

    /**
     * Sets the repo to return all values, always in an array (except for getById)
     *
     * @return $this
     */
    public function returnAll()
    {
        $this->returnOne = false;
        return $this;
    }

    /**
     * Returns a fresh clone of the stored Model, with no values set
     *
     * @return null|\Parable\ORM\Model
     */
    public function createModel()
    {
        $clone = clone $this->getModel();
        return $clone->reset();
    }

    /**
     * Set a model on the repository. Reset it so there's no unwanted values stored on it.
     *
     * @param \Parable\ORM\Model $model
     *
     * @return $this
     */
    public function setModel(\Parable\ORM\Model $model)
    {
        $this->model = $model->reset();
        return $this;
    }

    /**
     * Return model
     *
     * @return null|\Parable\ORM\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set onlyCount to true or false
     *
     * @param bool $value
     *
     * @return $this
     */
    public function onlyCount($value = true)
    {
        $this->onlyCount = (bool)$value;
        return $this;
    }

    /**
     * @param \Parable\ORM\Query\Condition[] $conditions
     *
     * @return \Parable\ORM\Query\Condition\AndSet
     */
    public function buildAndSet(array $conditions)
    {
        return $this->createQuery()->buildAndSet($conditions);
    }

    /**
     * @param \Parable\ORM\Query\Condition[] $conditions
     *
     * @return \Parable\ORM\Query\Condition\OrSet
     */
    public function buildOrSet(array $conditions)
    {
        return $this->createQuery()->buildOrSet($conditions);
    }

    /**
     * Handle the result of one of the get functions
     *
     * @param array $result
     *
     * @return \Parable\ORM\Model[]|int
     */
    protected function handleResult(array $result)
    {
        if ($this->onlyCount && isset($result[0]) && is_array($result[0])) {
            return (int)current($result[0]);
        }

        $entities = [];
        foreach ($result as $row) {
            $model = clone $this->getModel();
            $model->populate($row);
            $entities[] = $model;
        }
        return $entities;
    }
}
