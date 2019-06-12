<?php

namespace GraphQL\QueryBuilder;

use GraphQL\Exception\EmptySelectionSetException;
use GraphQL\Query;
use GraphQL\RawObject;

/**
 * Class AbstractQueryBuilder
 *
 * @package GraphQL
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    /**
     * @var Query
     */
    protected $query;

    /**
     * @var array
     */
    protected $selectionSet;

    /**
     * @var array
     */
    protected $argumentsList;

    /**
     * QueryBuilder constructor.
     *
     * @param string $queryObject
     */
    public function __construct(string $queryObject)
    {
        $this->query         = new Query($queryObject);
        $this->selectionSet  = [];
        $this->argumentsList = [];
    }

    /**
     * @return Query
     */
    public function getQuery(): Query
    {
        if (empty($this->selectionSet)) {
            throw new EmptySelectionSetException(static::class);
        }

        // Convert nested query builders to query objects
        foreach ($this->selectionSet as $key => $field) {
            if ($field instanceof AbstractQueryBuilder) {
                $this->selectionSet[$key] = $field->getQuery();
            }
        }

        $this->query->setArguments($this->argumentsList);
        $this->query->setSelectionSet($this->selectionSet);

        return $this->query;
    }

    /**
     * @param string|QueryBuilder|Query $selectedField
     *
     * @return $this
     */
    protected function selectField($selectedField)
    {
        if (is_string($selectedField) || $selectedField instanceof AbstractQueryBuilder || $selectedField instanceof Query) {
            $this->selectionSet[] = $selectedField;
        }

        return $this;
    }

    /**
     * @param $argumentName
     * @param $argumentValue
     *
     * @return $this
     */
    protected function setArgument(string $argumentName, $argumentValue)
    {
        if (is_scalar($argumentValue) || is_array($argumentValue) || $argumentValue instanceof RawObject) {
            $this->argumentsList[$argumentName] = $argumentValue;
        }

        return $this;
    }

    /***
     * @return array
     */
    protected function getSelectionSet()
    {
        return $this->selectionSet;
    }

    /***
     * Returns existing selction object if exists
     * @param $object
     * @return mixed
     */
    protected function getSelectionObjectIfExists($object)
    {
        foreach ($this->getSelectionSet() as $selection_object) {
            if ($selection_object instanceof $object) {
                unset($object);
                return $selection_object;
            }
        }
        return $object;
    }

    /***
     * Returns true if passed selection object already exists
     * @param Object $object
     * @return Bool
     */
    protected function selectionObjectExists($object)
    {
        foreach ($this->getSelectionSet() as $selection_object) {
            if ($selection_object instanceof $object) {
                unset($object);
                return true;
            }
        }
        return false;
    }
}