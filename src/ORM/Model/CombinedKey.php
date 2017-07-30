<?php

namespace Parable\ORM\Model;

/**
 * Class CombinedKey
 *
 * As with model, expects there to be an 'id' field in the key. If not, map one to it via mapper.
 *
 * @package Parable\ORM\Model
 */
class CombinedKey extends \Parable\ORM\Model
{
    /** @var string[] */
    protected $tableKey;

    /** @var bool */
    protected $isStored = false;

    /**
     * Saves the model, either inserting or updating.
     *
     * @return bool
     */
    public function save()
    {
        // Safeguard, to make sure we're needed.
        if (!is_array($this->tableKey)) {
            return parent::save();
        }

        $array = $this->toArrayWithoutEmptyValues();
        $query = $this->createQuery();
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        if ($this->isStored) {
            $query->setAction('update');

            foreach ($array as $key => $value) {
                $query->addValue($key, $value);
            }

            // Since it's an update, add updated_at if the model implements it
            if (property_exists($this, 'updated_at')) {
                $query->addValue('updated_at', $now);
                $this->updated_at = $now;
            }
        } else {
            $query->setAction('insert');

            foreach ($array as $key => $value) {
                if ($key !== $this->tableKey) {
                    $query->addValue($key, $value);
                }
            }

            // Since it's an insert, add created_at if the model implements it
            if (property_exists($this, 'created_at')) {
                $query->addValue('created_at', $now);
                $this->created_at = $now;
            }
        }

        $result = $this->database->query($query);
        if ($result) {
            $this->isStored = true;
        }
        return (bool) $result;
    }

    /**
     * Deletes the current model from the database
     *
     * @return bool
     */
    public function delete()
    {
        // Safeguard, to make sure we're needed.
        if (!is_array($this->tableKey)) {
            return parent::delete();
        }

        $mapped = $this->toArray();
        $conditions = [];

        foreach ($this->tableKey as $tableKey) {
            $conditions[] = [$tableKey, '=', $mapped[$tableKey]];
        }

        $query = $this->createQuery()->setAction('delete');
        $query->where($query->buildAndSet($conditions));

        if ($result = $this->database->query($query)) {
            $this->isStored = false;
        }
        return (bool) $result;
    }

    /**
     * Populates the current model with the data provided.
     * Assumes that it's stored in the database.
     * If not (f.e. when manually populating), call setIsStored() afterwards.
     *
     * @param array $data
     *
     * @return $this
     */
    public function populate(array $data)
    {
        parent::populate($data);
        $this->isStored = true;
        return $this;
    }

    /**
     * Reset all public properties to null, also mark as no longer stored
     *
     * @return $this
     */
    public function reset()
    {
        parent::reset();
        $this->isStored = false;
        return $this;
    }

    /**
     * Set whether or not this model is already stored in the database.
     * Necessary if you use populate() "manually".
     *
     * @param bool $isStored
     *
     * @return $this
     */
    public function setIsStored($isStored = false)
    {
        $this->isStored = $isStored;
        return $this;
    }
}