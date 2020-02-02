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
        $comment = $this->getChangesComment();
        if( !$comment )
            return false;
        $this->commitChanges($comment);
        $this->execute('push origin master');
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

    protected function getChangesComment(){
        $list = $this->execute('diff --staged --name-status');

        if (empty($list)) {
            return null;
        }

        $list = array_map(function ($i) { return substr($i, 2); }, $list);
        return implode(';', $list);
    }

    protected function commitChanges($comment) {
        $authorString = $this->default['name'];
        $mail = $this->default['email'];
        $authorString .= " <$mail>";
//                $repo->execute(['commit', '-m "Changed pages"', '-m '.implode(';', $list)]);

        $this->execute('commit -m "Changed resource: '.$comment.'" --author="'.$authorString.'"');

    }

}
