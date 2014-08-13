<?php

use Heyday\SphinxSearch\Sphinx;

/**
 *
 */
class SphinxController extends Controller
{
    /**
     * @var Sphinx
     */
    protected $sphinx;

    /**
     * @param \Heyday\SphinxSearch\Sphinx $sphinx
     */
    public function __construct(Sphinx $sphinx)
    {
        $this->sphinx = $sphinx;
        parent::__construct();
    }

    /**
     * 
     */
    public function init()
    {
        if (!Director::is_cli() && !Permission::check('ADMIN')) {
            echo 'Not allowed';
            exit;
        }

        parent::init();
    }

    /**
     * @return array
     */
    public function index()
    {
        if (Director::is_cli()) {
            echo implode(PHP_EOL, [
                'Commands available:',
                'sake sphinx/indexer (indexes mysql db)',
                'sake sphinx/searchd/start (starts searchd)',
                'sake sphinx/searchd/stop (stops searchd)',
                'sake sphinx/searchd/stop/force (force stop of searchd)',
                'sake sphinx/searchd/isRunning (check if searchd is running)',
                'sake sphinx/search search=37+elmslie+road+pinehaven index=Address'
            ]), PHP_EOL;

            exit;

        } else {
            return [
                'Searchd' => $this->sphinx->isRunning()
            ];
        }
    }

    /**
     *
     */
    public function indexer()
    {
        $request = $this->getRequest();

        if ($skipList = $request->getVar('skip')) {
            $options = [
                'skip' => explode(',', $skipList)
            ];
        } else {
            $options = [
                'all' => true
            ];
        }
        
        $this->output([
            $this->sphinx->index($options) ? 'success' : 'failed'
        ]);
        
        exit;
    }

    /**
     *
     */
    public function searchd()
    {
        $request = $this->getRequest();
        $id = $request->param('ID');

        switch ($id) {
            case 'isRunning':
                $pid = $this->sphinx->isRunning();
                $this->output([
                    $pid ? 'Running ' . $pid : 'Not running'
                ]);
                break;
            case 'stop':
                $this->output($this->sphinx->stop($request->param('OtherID') == 'force'));
                break;
            case 'start':
                $this->output($this->sphinx->start());
                break;
            default:
                $this->output([
                    'failed'
                ]);
        }
        
        exit;
    }

    /**
     *
     */
    public function search()
    {
        $request = $this->getRequest();

        if ($search = $request->getVar('search')) {
            $this->output(
                $this->sphinx->search($search, $request->getVar('index'))
            );
        }
        
        exit;
    }

    /**
     * @param array|bool $messages
     */
    protected function output($messages = []) {
        $messages && array_map(function ($message) {
            echo $message, PHP_EOL;
        }, $messages);
    }
}