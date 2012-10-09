<?php

class Sphinx
{

	/**
	 * Section: Database
	 */
	protected static $db_sphinx_config = array();

	public function set_db_sphinx_config(array $db_sphinx_config)
	{

		self::$db_sphinx_config = $db_sphinx_config;

	}

	/**
	 * Section: Sphinx configuration
	 */
	protected static $sphinx_indexer_mem_limit = '128M';
	protected static $sphinx_binary_location = '';
	protected static $sphinx_config_folder = '../heyday-sphinx/configs';
	protected static $sphinx_index_folder = '../heyday-sphinx/indexes';
	protected static $sphinx_searchd_log_folder = '../heyday-sphinx/logs';
	protected static $sphinx_port = 3313;
	protected static $sphinx_host = '127.0.0.1';
    protected static $sphinx_indexes = array();

	public static function set_sphinx_indexer_mem_limit($sphinx_indexer_mem_limit)
	{

		self::$sphinx_indexer_mem_limit = $sphinx_indexer_mem_limit;

	}

	public static function set_sphinx_binary_location($sphinx_binary_location)
	{

		self::$sphinx_binary_location = $sphinx_binary_location;

	}

	public static function set_sphinx_config_folder($sphinx_config_folder)
	{

		self::$sphinx_config_folder = $sphinx_config_folder;

	}

	public static function set_sphinx_index_folder($sphinx_index_folder)
	{

		self::$sphinx_index_folder = $sphinx_index_folder;

	}

	public static function set_sphinx_searchd_log_folder($sphinx_searchd_log_folder)
	{

		self::$sphinx_searchd_log_folder = $sphinx_searchd_log_folder;

	}

	public static function set_sphinx_port($sphinx_port)
	{

		self::$sphinx_port = $sphinx_port;

	}
    
    public static function get_sphinx_port()
    {
        
        return self::$sphinx_port;
        
    }

	public static function set_sphinx_host($sphinx_host)
	{

		self::$sphinx_host = $sphinx_host;

	}
    
    public static function get_sphinx_host()
    {
        
        return self::$sphinx_host;
        
    }

	public static function get_sphinx_config_folder()
	{

		return realpath(self::$sphinx_config_folder);

	}

	public static function get_sphinx_config_path()
	{

		return self::get_sphinx_config_folder() . "/Sphinx-" . Director::get_environment_type() . '.config';

	}

	public static function get_sphinx_index_path($index)
	{

		return realpath(self::$sphinx_index_folder) . '/' . $index;

	}

	public static function get_sphinx_searchd_log_folder()
	{

		return realpath(self::$sphinx_searchd_log_folder);

	}
    
    public static function add_sphinx_index(SphinxIndexConfiguration $config)
    {
        
        self::$sphinx_indexes[$config->getIdentifier()] = $config;
        
    }
    
    public static function get_sphinx_index($index)
    {
        return isset(self::$sphinx_indexes[$index]) ? self::$sphinx_indexes[$index] : null;
    }
    
    public static function add_sphinx_indexes(array $indexes)
    {
        
        foreach($indexes as $config){
            
            self::add_sphinx_index($config);
            
        }
        
    }
    
    public static function remove_sphinx_index($identifier)
    {
        if(isset(self::$sphinx_indexes[$identifier])){
            
            unset(self::$sphinx_indexes[$identifier]);
            
        }
    }

	public function __construct()
	{

		if (!self::$db_sphinx_config) {

			user_error('Sphinx DB not configured');

		}

	}

	public function running()
	{

		$file = self::get_sphinx_searchd_log_folder() . '/searchd.pid';

		if (file_exists($file)) {
			$pid = (int) trim(file_get_contents($file));
			if (!$pid) return false;
			if (preg_match("/(^|\\s)$pid\\s/m", `ps ax`)) return $pid;
			return false;
		}

		return false;

	}

	public function allrunning()
	{

		$output = array();

		exec("ps aux | grep 'searchd --config " . realpath('..') . "'", $output);

		return implode(PHP_EOL, $output);

	}

	public function config()
	{

		SSViewer::set_source_file_comments(false);
        Versioned::reading_stage('Live');

        $indexes = new DataObjectSet();
        
        foreach(self::$sphinx_indexes as $index => $indexConfig){
            
            $dbConfig = $indexConfig->getDbConfig() ? $indexConfig->getDbConfig() : self::$db_sphinx_config;
            
            $indexes->push(new ArrayData(array_merge(array(
                'Index' => $index,
                'IndexPath' => self::get_sphinx_index_path($index),
                'Query' => str_replace('"', '`', $indexConfig->getQuery()),
                'Attributes' => $indexConfig->getAttributes(),
                'ConfigFolder' => $this->get_sphinx_config_folder()
            ),$dbConfig)));
            
            
        }
            
        $viewer = new SSViewer('Config');

        $config = self::get_sphinx_config_path($index);

        file_put_contents($config, $viewer->process(
            new ArrayData(array_merge(array(
                'Indexes' => $indexes,
                'LogFolder' => self::get_sphinx_searchd_log_folder(),
                'Port' => self::$sphinx_port,
                'Host' => self::$sphinx_host,
                'IndexerMemLimit' => self::$sphinx_indexer_mem_limit
            ), self::$db_sphinx_config))
        ));

	}

	public function index($options = array(
		'--all'
	))
	{

		$this->config();

        $config = self::get_sphinx_config_path();
        $output = array();

        if ($this->running()) {

            $options[] = '--rotate';

        }

        exec(self::$sphinx_binary_location . "indexer --config $config " . implode(' ', $options), $output);

        echo implode(PHP_EOL, $output), PHP_EOL;

		return true;

	}

	public function search($str = '', $index = null, $limit = 10, $start = false)
	{

		if (self::$sphinx_host == '127.0.0.1' && !$this->running()) {

			if ($start) {

				$this->start(false);

				SS_Log::log(new Exception('Tried to search without searchd running'), SS_Log::NOTICE);

				sleep(1);

			} else {

				SS_Log::log(new Exception('Tried to search without searchd running'), SS_Log::ERR);

			}

		}

		$s = new SphinxClient();
        
		$s->SetServer(self::$sphinx_host, self::$sphinx_port);
        
		$s->SetMatchMode(SPH_MATCH_ANY);
		$s->SetSortMode(SPH_SORT_RELEVANCE);
        
        if(isset($index) && isset(self::$sphinx_indexes[$index])){
            
            $weights = self::$sphinx_indexes[$index]->getFieldWeights();
            
            if(is_array($weights) && count($weights)){
                
                $s->SetFieldWeights($weights);
                
            }
            
        }

		if ($limit) {

			$s->SetLimits(0, $limit);

		}

		$result = $s->Query($str, isset($index) ? $index : '*');
        
        var_dump($result);

	}

	public function start($display = true)
	{

		if (!$this->running()) {

			$this->config();

			$config = self::get_sphinx_config_path();

			$output = array();

			exec(self::$sphinx_binary_location . "searchd --config $config", $output);

			if ($display) {

				echo implode(PHP_EOL, $output), PHP_EOL;

			}

		}

		return true;

	}

	public function stop($force = false)
	{

		if ($this->running() || $force) {

			$config = self::get_sphinx_config_path();

			$output = array();

			exec(self::$sphinx_binary_location . "searchd --config $config --stop", $output);

			echo implode(PHP_EOL, $output), PHP_EOL;

		}

		return true;

	}
	
}

class SphinxIndexConfiguration
{
    protected $indexIdentifier;
    protected $sphinxSearch;
    protected $fieldWeights;
    protected $attributes;
    protected $query;
    protected $dbConfig;
    
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
    
    public function getFieldWeights()
    {
        
        return is_array($this->fieldWeights) && count($this->fieldWeights) ? $this->fieldWeights : null;
        
    }
    
    public function getAttributes()
    {
        
        $attributes = null;
        
        if(is_array($this->attributes) && count($this->attributes)){
            
            $attributes = new DataObjectSet();

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