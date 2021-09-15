<?php

class Sphinx extends SS_Object
{

    protected $indexes = array ();


    protected function getDBConfig()
    {
        global $databaseConfig;

        $db = $this->config()->db;

        return array_merge(
            $databaseConfig,
            $db
        );
    }


    protected function getIndexConfig()
    {
        if(!empty($this->indexes)) {
            return $this->indexes;
        }

        $indexes = $this->config()->sphinx_indexes;

        if(!$indexes || !is_array($indexes)) {
            SS_Log::log(new Exception('No indexes configured'), SS_Log::NOTICE);
        }

        $ret = array ();
        foreach($indexes as $class) {
            $indexConfig = Injector::inst()->get($class);
            $ret[$indexConfig->getIdentifier()] = $indexConfig;
        }

        return $this->indexes = $ret;
    }


    protected function getSphinxCommand($cmd)
    {
        return Controller::join_links($this->config()->sphinx_binary_location, $cmd);
    }


    protected function getSphinxConfigFolder()
    {
        return realpath($this->config()->sphinx_config_folder);
    }


    protected function getSphinxConfigPath()
    {
        return Controller::join_links($this->getSphinxConfigFolder(), "Sphinx-" . Director::get_environment_type() . '.config');
    }



    public function getIndex($index)
    {
        $indexes = $this->getIndexConfig();

        return isset($indexes[$index]) ? $indexes[$index] : false;
    }


    public function running()
    {
        $file = $this->config()->sphinx_searchd_log_folder . '/searchd.pid';

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


    public function doConfig()
    {

        SSViewer::set_source_file_comments(false);
        Versioned::reading_stage('Live');

        $indexes = new ArrayList();

        foreach($this->getIndexConfig() as $identifier => $indexConfig) {
            $dbConfig = $indexConfig->getDbConfig() ? $indexConfig->getDbConfig() : $this->getDBConfig();

            $indexes->push(new ArrayData(array_merge(array(
                        'Index' => $identifier,
                        'IndexPath' => $indexConfig->getPath(),
                        'Query' => str_replace('"', '`', $indexConfig->getQuery()),
                        'Attributes' => $indexConfig->getAttributes(),
                        'ConfigFolder' => $this->getSphinxConfigFolder(),
                        'Morphology' => $indexConfig->getMorphology()
                    ),$dbConfig)));

        }

        $viewer = new SSViewer('Config');

        $text = $viewer->process(
            new ArrayData(array(
                    'Indexes' => $indexes,
                    'LogFolder' => $this->config()->sphinx_searchd_log_folder,
                    'Port' => $this->config()->sphinx_port,
                    'Host' => $this->config()->sphinx_host,
                    'IndexerMemLimit' => $this->config()->sphinx_indexer_mem_limit
                ))
        );

        file_put_contents($this->getSphinxConfigPath(), $text);
    }


    public function index($options = array('all' => true))
    {
        $this->doConfig();

        $config = $this->getSphinxConfigPath();
        $output = array();

        $runoptions = array();

        if ($this->running()) {
            $runoptions[] = '--rotate';
        }

        if (isset($options['skip'])) {
            foreach (array_keys(self::$sphinx_indexes) as $index) {
                if (!in_array($index, $options['skip'])) {
                    $runoptions[] = $index;
                }
            }
        } elseif($options['all']) {
            $runoptions[] = '--all';
        }

        $sphinx = $this->getSphinxCommand("indexer");
        $cmd = "$sphinx --config $config " . implode(' ', $runoptions);
        echo $cmd;
        exec($cmd, $output);
        echo implode(PHP_EOL, $output), PHP_EOL;

        return true;
    }


    public function search($str = '', $index = null, $limit = 10, $start = false)
    {
        if ($this->config()->sphinx_host == '127.0.0.1' && !$this->running()) {
            if ($start) {
                $this->start(false);
                SS_Log::log(new Exception('Tried to search without searchd running'), SS_Log::NOTICE);
                sleep(1);

            } else {
                SS_Log::log(new Exception('Tried to search without searchd running'), SS_Log::ERR);
            }

        }

        $s = new SphinxClient();
        $s->SetServer(
            $this->config()->sphinx_host,
            $this->config()->sphinx_port
        );
        $s->SetMatchMode(SPH_MATCH_ANY);
        $s->SetSortMode(SPH_SORT_RELEVANCE);


        if($i = $this->getIndex($index)) {
            $weights = $i->getFieldWeights();
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
            $this->doConfig();
            $config = $this->getSphinxConfigPath();
            $output = array();
            $sphinx = $this->getSphinxCommand("searchd");
            $cmd = "$sphinx --config $config";
            echo $cmd;
            exec($cmd, $output);

            if ($display) {
                echo implode(PHP_EOL, $output), PHP_EOL;
            }
        }
        else echo "running";

        return true;
    }


    public function stop($force = false)
    {
        if ($this->running() || $force) {
            $config = $this->getSphinxConfigPath();
            $output = array();
            $sphinx = $this->getSphinxCommand("searchd");
            $cmd = "$sphinx --config $config --stop";
            exec($cmd, $output);
            echo implode(PHP_EOL, $output), PHP_EOL;
        }

        return true;
    }

}


