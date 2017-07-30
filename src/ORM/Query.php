<?php

namespace Parable\ORM;

class Query
{
    /** Join types */
    const JOIN_INNER = 1;
    const JOIN_LEFT  = 2;
    const JOIN_RIGHT = 3;
    const JOIN_FULL  = 4;

    /** Order by types */
    const ORDER_ASC  = 'ASC';
    const ORDER_DESC = 'DESC';

    /** @var \Parable\ORM\Query\Condition[] */
    protected $where = [];

    /** @var \Parable\ORM\Query\Condition[] */
    protected $having = [];

    /** @var array */
    protected $values = [];

    /** @var array */
    protected $orderBy = [];

    /** @var array */
    protected $groupBy = [];

    /** @var array */
    protected $select = ['*'];

    /** @var string */
    protected $action = 'select';

    /** @var \Parable\ORM\Query\Condition[][] */
    protected $joins = [
        self::JOIN_INNER => [],
        self::JOIN_LEFT  => [],
        self::JOIN_RIGHT => [],
        self::JOIN_FULL  => [],
    ];

    /** @var null|int */
    protected $limitOffset = [];

    /** @var null|string */
    protected $tableName;

    /** @var null|string|string[] */
    protected $tableKey;

    /** @var \Parable\ORM\Database */
    protected $database;

    /** @var array */
    protected $acceptedValues = ['select', 'insert', 'update', 'delete'];

    /** @var array */
    protected $nonQuoteStrings = ['*', 'sum', 'max', 'min', 'count', 'avg'];

    public function __construct(
        \Parable\ORM\Database $database
    ) {
        $this->database = $database;
    }

    /**
     * Set the tableName to work on
     *
     * @param string $tableName
     *
     * @return $this
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Get the currently set tableName
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get the currently set tableName, quoted
     *
     * @return null|string
     */
    public function getQuotedTableName()
    {
        return $this->quoteIdentifier($this->tableName);
    }

    /**
     * Set the tableKey to work with (for delete & update)
     *
     * @param string|string[] $key
     *
     * @return $this
     */
    public function setTableKey($key)
    {
        $this->tableKey = $key;
        return $this;
    }

    /**
     * @return null|string|string[]
     */
    public function getTableKey()
    {
        return $this->tableKey;
    }

    /**
     * Set the type of query we're going to do
     *
     * @param string $action
     *
     * @return $this
     * @throws \Parable\ORM\Exception
     */
    public function setAction($action)
    {
        if (!in_array($action, $this->acceptedValues)) {
            $acceptedValuesString = implode(', ', $this->acceptedValues);
            throw new \Parable\ORM\Exception("Invalid action set, only {$acceptedValuesString} are allowed.");
        }
        $this->action = $action;
        return $this;
    }

    /**
     * Return the action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * In case of a select, what we're going to select (default *)
     *
     * @param array $select
     *
     * @return $this
     */
    public function select(array $select)
    {
        $this->select = $select;
        return $this;
    }

    /**
     * @param \Parable\ORM\Query\ConditionSet $set
     * @return $this
     */
    public function where(\Parable\ORM\Query\ConditionSet $set)
    {
        $this->where[] = $set;
        return $this;
    }

    /**
     * @param \Parable\ORM\Query\ConditionSet[] $sets
     */
    public function whereMany(array $sets)
    {
        foreach ($sets as $set) {
            $this->where($set);
        }
    }

    /**
     * @param \Parable\ORM\Query\ConditionSet $set
     *
     * @return $this
     */
    public function having(\Parable\ORM\Query\ConditionSet $set)
    {
        $this->having[] = $set;
        return $this;
    }

    /**
     * @param \Parable\ORM\Query\ConditionSet[] $sets
     */
    public function havingMany(array $sets)
    {
        foreach ($sets as $set) {
            $this->having($set);
        }
    }

    /**
     * @param \Parable\ORM\Query\Condition[] $conditions
     *
     * @return \Parable\ORM\Query\Condition\AndSet
     */
    public function buildAndSet(array $conditions)
    {
        return new Query\Condition\AndSet($this, $conditions);
    }

    /**
     * @param \Parable\ORM\Query\Condition[] $conditions
     *
     * @return \Parable\ORM\Query\Condition\OrSet
     */
    public function buildOrSet(array $conditions)
    {
        return new Query\Condition\OrSet($this, $conditions);
    }

    /**
     * @param int    $type
     * @param string $tableName
     * @param string $key
     * @param string $comparator
     * @param mixed  $value
     * @param bool   $shouldCompareFields
     *
     * @return $this
     */
    protected function join(
        $type,
        $tableName,
        $key,
        $comparator,
        $value = null,
        $shouldCompareFields = true
    ) {
        /** @var \Parable\ORM\Query\Condition $condition */
        $condition = new \Parable\ORM\Query\Condition();
        $condition
            ->setTableName($this->getTableName())
            ->setJoinTableName($tableName)
            ->setKey($key)
            ->setComparator($comparator)
            ->setValue($value)
            ->setQuery($this)
            ->setShouldCompareFields($shouldCompareFields);

        $this->joins[$type][] = $condition;
        return $this;
    }

    /**
     * @param string $tableName
     * @param string $key
     * @param string $comparator
     * @param mixed  $value
     * @param bool   $shouldCompareFields
     *
     * @return $this
     */
    public function innerJoin($tableName, $key, $comparator, $value = null, $shouldCompareFields = true)
    {
        return $this->join(self::JOIN_INNER, $tableName, $key, $comparator, $value, $shouldCompareFields);
    }

    /**
     * @param string $tableName
     * @param string $key
     * @param string $comparator
     * @param mixed  $value
     * @param bool   $shouldCompareFields
     *
     * @return $this
     */
    public function leftJoin($tableName, $key, $comparator, $value = null, $shouldCompareFields = true)
    {
        return $this->join(self::JOIN_LEFT, $tableName, $key, $comparator, $value, $shouldCompareFields);
    }

    /**
     * @param string $tableName
     * @param string $key
     * @param string $comparator
     * @param mixed  $value
     * @param bool   $shouldCompareFields
     *
     * @return $this
     */
    public function rightJoin($tableName, $key, $comparator, $value = null, $shouldCompareFields = true)
    {
        return $this->join(self::JOIN_RIGHT, $tableName, $key, $comparator, $value, $shouldCompareFields);
    }

    /**
     * @param string $tableName
     * @param string $key
     * @param string $comparator
     * @param mixed  $value
     * @param bool   $shouldCompareFields
     *
     * @return $this
     */
    public function fullJoin($tableName, $key, $comparator, $value = null, $shouldCompareFields = true)
    {
        return $this->join(self::JOIN_FULL, $tableName, $key, $comparator, $value, $shouldCompareFields);
    }

    /**
     * Adds a value to update/insert queries
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addValue($key, $value)
    {
        $this->values[$key] = $value;
        return $this;
    }

    /**
     * Sets the order for select queries
     *
     * @param string      $key
     * @param string      $direction
     * @param null|string $tableName
     *
     * @return $this
     */
    public function orderBy($key, $direction = self::ORDER_ASC, $tableName = null)
    {
        if (!$tableName) {
            $tableName = $this->getTableName();
        }
        $this->orderBy[] = ['key' => $key, 'direction' => $direction, 'tableName' => $tableName];
        return $this;
    }

    /**
     * Sets the group by for select queries
     *
     * @param string      $key
     * @param null|string $tableName
     *
     * @return $this
     */
    public function groupBy($key, $tableName = null)
    {
        if (!$tableName) {
            $tableName = $this->getTableName();
        }
        $this->groupBy[] = ['key' => $key, 'tableName' => $tableName];
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
     * Quote the string properly if a database instance is available, otherwise fudge it for debugging purposes.
     *
     * @param string $string
     *
     * @return string
     */
    public function quote($string)
    {
        if (!$this->database->getInstance()) {
            $string = str_replace("'", "", $string);
            return "'{$string}'";
        }
        return $this->database->quote($string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function quoteIdentifier($string)
    {
        if (!$this->database->getInstance()) {
            return "`{$string}`";
        }
        return $this->database->quoteIdentifier($string);
    }

    /**
     * @return string
     */
    protected function buildSelect()
    {
        $selects = [];
        foreach ($this->select as $select) {
            $shouldBeQuoted = true;

            // Check our list of nonQuoteStrings to see if we should quote or not.
            foreach ($this->nonQuoteStrings as $nonQuoteString) {
                if (strpos(strtolower($select), $nonQuoteString) !== false) {
                    $shouldBeQuoted = false;
                    break;
                }
            }

            if ($shouldBeQuoted) {
                $selects[] = $this->getQuotedTableName() . '.' . $this->quoteIdentifier($select);
            } else {
                $selects[] = $select;
            }
        }
        return implode(', ', $selects);
    }

    /**
     * Build JOIN string if they're available
     *
     * @return string
     */
    protected function buildJoins()
    {
        $builtJoins = [];
        foreach ($this->joins as $type => $joins) {
            if (count($joins) > 0) {
                foreach ($joins as $join) {
                    if ($type == self::JOIN_INNER) {
                        $builtJoins[] = "INNER JOIN";
                    } elseif ($type == self::JOIN_LEFT) {
                        $builtJoins[] = "LEFT JOIN";
                    } elseif ($type == self::JOIN_RIGHT) {
                        $builtJoins[] = "RIGHT JOIN";
                    } elseif ($type == self::JOIN_FULL) {
                        $builtJoins[] = "FULL JOIN";
                    }

                    $builtJoins[] = $this->quoteIdentifier($join->getJoinTableName()) . " ON";

                    // Use a ConditionSet to build the joins
                    $conditionSet = new Query\Condition\AndSet($this, [$join]);
                    $builtJoins[] = $conditionSet->buildWithoutParentheses();
                }
            }
        }

        return implode(" ", $builtJoins);
    }

    /**
     * Build WHERE string if they're available
     *
     * @return string
     */
    protected function buildWheres()
    {
        if (count($this->where) === 0) {
            return '';
        }

        // Use a ConditionSet to build the wheres
        $conditionSet = new Query\Condition\AndSet($this, $this->where);
        return "WHERE {$conditionSet->buildWithoutParentheses()}";
    }

    /**
     * Build HAVING string if they're available
     *
     * @return string
     */
    protected function buildHaving()
    {
        if (count($this->having) === 0) {
            return '';
        }

        // Use a ConditionSet to build the having clause
        $conditionSet = new Query\Condition\AndSet($this, $this->having);
        return "HAVING {$conditionSet->buildWithoutParentheses()}";
    }

    /**
     * Build ORDER BY string if it's available
     *
     * @return string
     */
    protected function buildOrderBy()
    {
        if (count($this->orderBy) === 0) {
            return '';
        }

        $orders = [];
        foreach ($this->orderBy as $orderBy) {
            $key = $this->quoteIdentifier($orderBy["tableName"]) . "." . $this->quote($orderBy["key"]);
            $orders[] = $key . ' ' . $orderBy['direction'];
        }
        return "ORDER BY " . implode(', ', $orders);
    }

    /**
     * Build GROUP BY string if it's available
     *
     * @return string
     */
    protected function buildGroupBy()
    {
        if (count($this->groupBy) === 0) {
            return '';
        }

        $groups = [];
        foreach ($this->groupBy as $groupBy) {
            $groupBy = $this->quoteIdentifier($groupBy["tableName"]) . "." . $this->quote($groupBy["key"]);
            $groups[] = $groupBy;
        }
        return "GROUP BY " . implode(', ', $groups);
    }

    /**
     * Build LIMIT/OFFSET string if it's available
     *
     * @return string
     */
    protected function buildLimitOffset()
    {
        if (empty($this->limitOffset)) {
            return '';
        }

        $limitOffset = "";
        if ($this->limitOffset["limit"] && $this->limitOffset["offset"]) {
            $limitOffset = $this->limitOffset['offset'] . ',' . $this->limitOffset['limit'];
        } elseif ($this->limitOffset["limit"]) {
            $limitOffset = $this->limitOffset["limit"];
        } elseif ($this->limitOffset["offset"]) {
            $limitOffset = $this->limitOffset["offset"];
        }

        return "LIMIT " . $limitOffset;
    }

    /**
     * @return $this
     */
    public static function createInstance()
    {
        return \Parable\DI\Container::create(static::class);
    }

    /**
     * Outputs the actual query for use, empty string if invalid/incomplete values given
     *
     * @return string
     */
    public function __toString()
    {
        $query = [];

        if ($this->action === 'select') {
            if (count($this->select) == 0) {
                return '';
            }

            $query[] = "SELECT " . $this->buildSelect();
            $query[] = "FROM " . $this->getQuotedTableName();
            $query[] = $this->buildJoins();
            $query[] = $this->buildWheres();
            $query[] = $this->buildGroupBy();
            $query[] = $this->buildHaving();
            $query[] = $this->buildOrderBy();
            $query[] = $this->buildLimitOffset();
        } elseif ($this->action === 'delete') {
            if (count($this->where) == 0) {
                return '';
            }

            $query[] = "DELETE FROM " . $this->getQuotedTableName();
            $query[] = $this->buildWheres();
        } elseif ($this->action === 'update') {
            if (count($this->values) == 0) {
                return '';
            }

            $query[] = "UPDATE " . $this->getQuotedTableName();

            $tableKeys = [];
            $values = [];
            foreach ($this->values as $key => $value) {
                // skip id, since we'll use that as a where condition
                if ($key !== $this->tableKey && !(is_array($this->tableKey) && in_array($key, $this->tableKey))) {
                    if ($value === null) {
                        $correctValue = 'NULL';
                    } else {
                        $correctValue = $this->quote($value);
                    }
                    // Quote the key
                    $key =  $this->quoteIdentifier($key);

                    // Add key & value combo to the array
                    $values[] = $key . " = " . $correctValue;
                } else {
                    $tableKeys[$key] = $value;
                }
            }

            // Possibly set the table values to defaults (backwards compatibility?)
            if (empty($tableKeys)) {
                $tableKeys['id'] = null;
            }
            $query[] = "SET " . implode(', ', $values);
            $query[] = "WHERE";

            foreach($tableKeys as $tableKey => $tableKeyValue) {
                $query[] = $this->getQuotedTableName() . '.' . $this->quoteIdentifier($tableKey);
                $query[] = "= " . $this->quote($tableKeyValue);
                $query[] = "AND";
            }
            // Remove the last 'AND'
            array_pop($query);
        } elseif ($this->action === 'insert') {
            if (count($this->values) == 0) {
                return '';
            }

            // set insert to the proper table
            $query[] = "INSERT INTO " . $this->getQuotedTableName();

            // now get the values
            $keys = [];
            $values = [];
            foreach ($this->values as $key => $value) {
                // Quote the key
                $keys[] = $this->quoteIdentifier($key);

                if ($value === null) {
                    $correctValue = 'NULL';
                } else {
                    $correctValue = $this->quote($value);
                }
                $values[] = $correctValue;
            }

            $query[] = "(" . implode(', ', $keys) . ")";
            $query[] = "VALUES";
            $query[] = "(" . implode(', ', $values) . ")";
        }


        // Clean up any empty lines we're not going to want in the string, to prevent double/triple spaces
        foreach ($query as $key => $queryPart) {
            if (empty($queryPart)) {
                unset($query[$key]);
            }
        }

        // Now make it nice.
        $queryString = implode(" ", $query);
        $queryString = trim($queryString) . ';';

        // Since we got here, we've got a query to output
        return $queryString;
    }
}
