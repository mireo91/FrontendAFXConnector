<?php
namespace Mireo\FrontendAFXConnector\Repository;

use Neos\Utility\Files;
use Neos\Flow\Annotations as Flow;

class GitRepository extends AbstractRepository
{

    /**
     * @var array
     */
    protected $default = [
        'name' => 'Mireo.FrontendAFXConnector',
        'email' => 'Mireo@FrontendAFX.Connector'
    ];

    /**
     * @param array $files
     * @return bool
     * @throws
     */
    public function push($files){
        $this->init();
//        \Neos\Flow\var_dump($files);exit;
        $this->stageFiles($files);
        $this->commitChanges();
        return true;
    }

    protected function stageFiles($files){

        foreach( $files as $file ){
            $this->execute('add '.$file);
        }
    }

    protected function execute($cmd){
        $cwd = getcwd();
        chdir($this->packagePath);
        exec('git '.$cmd . ' 2>&1', $output, $ret);
        chdir($cwd);
        if($ret !== 0)
        {
            throw new \Exception(sprintf('Git Error: %s', implode(';', $output)));
        }
        return $output;
    }

    protected function init() {

        $path = realpath($this->packagePath);

        if (!is_dir(Files::concatenatePaths([$path, '.git']))) {
            throw new \Exception(sprintf('Dir %s is not git repository', $path));
        }


    }

    public function commitChanges($message = null) {

        $list = $this->execute('diff --staged --name-status');
//        \Neos\Flow\var_dump($list);

        if (empty($list)) {
            return;
        }

//        if ($message === null) {
//            $list = $repo->execute(['diff', '--staged', '--name-status']);
//            var_dump($list);
            $list = array_map(function ($i) { return substr($i, 2); }, $list);
//            $list = array_filter($list, function ($i) { return strpos($i, '_Resources') === false; });
//            $list2 = array_map(function ($i) { return '-m ' . substr($i, 2); }, $list);
//            var_dump($list, '------------------------');
//            array_unshift($list, 'Changed pages:');
//            $message = '"'.implode(PHP_EOL, $list).'"';
//            $repo->commit('Changed pages:', $list2);
//            array_unshift($list2, 'commit');

            if (empty($list)) {
                throw new \Exception('Nothing to commit');
            } else {
                $authorString = $this->default['name'];
                $mail = $this->default['email'];
                $authorString .= " <$mail>";
//                $repo->execute(['commit', '-m "Changed pages"', '-m '.implode(';', $list)]);

                $this->execute('commit -m "Changed resource: '.implode(';', $list).'" --author="'.$authorString.'"');
            }
//        } else {
//
//            $repo->commit($message);
//        }



//        var_dump($x);

    }

}
