<?php

class SphinxIndexConfiguration
{
    protected $indexIdentifier;
    protected $sphinxSearch;
    protected $fieldWeights;
    protected $attributes;
    protected $query;
    protected $dbConfig;
    protected $morphology = "none";

    function __construct($indexIdentifier, $sphinxSearch, $attributes = array(), $fieldWeights = array(),$dbConfig = false)
    {

        $this->indexIdentifier = $indexIdentifier;
        $this->fieldWeights = $fieldWeights;
        $this->attributes = $attributes;
        $this->dbConfig = $dbConfig;

        if($sphinxSearch instanceof HeydaySearch){

            $this->sphinxSearch = $sphinxSearch;

        }else{

            $this->query = preg_replace('/\s+/', ' ', $sphinxSearch);

        }

    }

    public function getIdentifier()
    {

        return $this->indexIdentifier;

    }



    public function getPath()
    {
        $folder = Config::inst()->forClass("Sphinx")->sphinx_index_folder;
        return realpath($folder) . '/' . $this->indexIdentifier;
    }


    public function setDbConfig(array $dbConfig)
    {

        $this->dbConfig = $dbConfig;

    }

    public function getDbConfig()
    {

        return $this->dbConfig;

    }

    public function setQuery($query)
    {

        $this->query = $query;

    }

    public function getQuery()
    {

        if(!is_null($this->query)){

            return $this->query;

        }

        return isset($this->sphinxSearch) ? $this->sphinxSearch->build()->sql() : null;

    }


    public function setMorphology($morph)
    {
        $this->morphology = $morph;
    }


    public function getMorphology()
    {
        return $this->morphology;
    }


    public function getFieldWeights()
    {

        return is_array($this->fieldWeights) && count($this->fieldWeights) ? $this->fieldWeights : null;

    }

    public function getAttributes()
    {

        $attributes = null;

        if(is_array($this->attributes) && count($this->attributes)){

            $attributes = new ArrayList();

            foreach($this->attributes as $attribute => $type){
                $attributes->push(new ArrayData(array(
                        'Attribute' => $attribute,
                        'Type' => $type
                    )));
            }
        }

        return $attributes;

    }

}