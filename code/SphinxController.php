<?php

class SphinxController extends Controller
{

    protected $sphinx = false;

    public function getSphinx()
    {

        if (!$this->sphinx instanceof Sphinx) {

            $this->sphinx = new Sphinx();

        }

        return $this->sphinx;

    }

    public function init()
    {

        if (!Director::is_cli() && !Permission::check('ADMIN')) {

            echo 'Not allowed';
            exit;

        }

        parent::init();

    }

    public function index()
    {

        if (Director::is_cli()) {

            echo implode(PHP_EOL, array(
                    'Commands available:',
                    'sake sphinx/indexer (indexes mysql db)',
                    'sake sphinx/searchd/start (starts searchd)',
                    'sake sphinx/searchd/stop (stops searchd)',
                    'sake sphinx/searchd/stop/force (force stop of searchd)',
                    'sake sphinx/searchd/running (check if searchd is running)',
                    'sake sphinx/searchd/allrunning (check any searchd\'s running)',
                    'sake sphinx/search search=37+elmslie+road+pinehaven index=Address'
                )), PHP_EOL;

            exit;

        } else {

            $sphinx = $this->getSphinx();

            return array(
                'Searchd' => $sphinx->running()
            );

        }

    }

    public function indexer()
    {
        $request = $this->getRequest();

        if ($skipList = $request->getVar('skip')) {
            $options = array(
                'skip' => explode(',', $skipList)
            );
        } else {
            $options = array(
                'all' => true
            );
        }

        $sphinx = $this->getSphinx();

        if ($sphinx->index($options)) {

            echo 'Success', PHP_EOL;
            exit;

        }

        echo 'Failed', PHP_EOL;
        exit;

    }

    public function searchd()
    {

        $request = $this->getRequest();

        if ($request->param('ID') && in_array($request->param('ID'), array('start', 'stop', 'running', 'allrunning'))) {

            $sphinx = $this->getSphinx();

//			if ($sphinx->connect()) {

            if ($request->param('ID') == 'running') {

                if ($pid = $sphinx->{$request->param('ID')}()) {

                    echo 'Running: ', $pid, PHP_EOL;
                    exit;

                } else {

                    echo 'Not running', PHP_EOL;
                    exit;

                }

            } else if ($request->param('ID') == 'allrunning') {

                echo $sphinx->{$request->param('ID')}(), PHP_EOL;

                exit;

            } else if ($request->param('ID') == 'stop' && $sphinx->{$request->param('ID')}($request->param('OtherID') == 'force' ? true : false)) {

                echo 'Success', PHP_EOL;
                exit;

            } elseif($sphinx->{$request->param('ID')}()) {

                echo 'Success', PHP_EOL;
                exit;

            }

//			}

        }

        echo 'Failed', PHP_EOL;
        exit;

    }

    public function search()
    {

        $request = $this->getRequest();

        if ($request->getVar('search')) {

            $sphinx = $this->getSphinx();

            if ($sphinx->connect()) {

                $index = $request->getVar('index');

                $sphinx->search($request->getVar('search'), isset($index) ? $index : null);

                echo 'Success', PHP_EOL;
                exit;

            }

        }

        echo 'Failed', PHP_EOL;
        exit;

    }

}