<?php

namespace Heyday\SphinxSearch;

use Heyday\QueryBuilder\QueryBuilder;

/**
 * @package Heyday
 */
class SphinxIndexConfiguration
{
    use DbValidateTrait;

    /**
     * @var string
     */
    protected $indexIdentifier;

    /**
     * @var \Heyday\QueryBuilder\QueryBuilder|string
     */
    protected $query;

    /**
     * @var array
     */
    protected $fieldWeights;

    /**
     * @var array
     */
    protected $attributes;

    /**
     * @var array|\ArrayObject|bool
     */
    protected $dbConfig;

    /**
     * @var string
     */
    protected $morphology = "none";

    /**
     * @param string $indexIdentifier
     * @param \Heyday\QueryBuilder\QueryBuilder|string $query
     * @param array|\ArrayObject $dbConfig
     * @param array $fieldWeights
     * @param array $attributes
     */
    public function __construct(
        $indexIdentifier,
        $query,
        $dbConfig = null,
        $fieldWeights = [],
        $attributes = []
    ) {

        $this->indexIdentifier = $indexIdentifier;
        $this->setQuery($query);
        if (is_array($dbConfig) || $dbConfig instanceof \ArrayAccess) {
            $this->setDbConfig($dbConfig);
        }
        $this->fieldWeights = $fieldWeights;
        $this->attributes = $attributes;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->indexIdentifier;
    }

    /**
     * @param string $basePath
     * @return string
     */
    public function getPath($basePath)
    {
        return sprintf(
            "%s/%s",
            realpath($basePath),
            $this->indexIdentifier
        );
    }

    /**
     * @param array|\ArrayObject $dbConfig
     */
    public function setDbConfig($dbConfig)
    {
        $this->assertValidDbConfig($dbConfig);
        $this->dbConfig = $dbConfig;
    }

    /**
     * @return array|bool
     */
    public function getDbConfig()
    {
        if ($this->dbConfig instanceof \ArrayObject) {
            return $this->dbConfig->getArrayCopy();
        }

        return $this->dbConfig;
    }

    /**
     * @param \Heyday\QueryBuilder\QueryBuilder $query
     */
    public function setQuery($query)
    {
        if (is_string($query) || $query instanceof QueryBuilder) {
            $this->query = $query;
        }
    }

    /**
     * @return mixed|null
     */
    public function getQueryString()
    {
        return $this->query instanceof QueryBuilder ? $this->query->getQuery()->sql() : $this->query;
    }
    
    /**
     * @param $morph
     */
    public function setMorphology($morph)
    {
        $this->morphology = $morph;
    }

    /**
     * @return string
     */
    public function getMorphology()
    {
        return $this->morphology;
    }

    /**
     * @return array|null
     */
    public function getFieldWeights()
    {
        return is_array($this->fieldWeights) && count($this->fieldWeights) ? $this->fieldWeights : null;
    }

    /**
     * @return \ArrayList
     */
    public function getAttributes()
    {
        $attributes = new \ArrayList();

        if (is_array($this->attributes) && count($this->attributes)) {
            foreach ($this->attributes as $attribute => $type){
                $attributes->push(new \ArrayData([
                    'Attribute' => $attribute,
                    'Type' => $type
                ]));
            }
        }

        return $attributes;
    }
}