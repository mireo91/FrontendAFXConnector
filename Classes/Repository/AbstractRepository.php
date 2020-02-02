<?php
/**
 * Created by PhpStorm.
 * User: pkaminski
 * Date: 2019-10-15
 * Time: 21:24
 */

namespace Hb180\StaticRendering\Storage;

use Neos\Utility\Files;
use Neos\Flow\Annotations as Flow;

class GitRepository
{
    /**
     * @var string
     */
    protected $storageDir = '';

    /**
     * @var array
     */
    protected $default = [
        'name' => 'Mireo.FrontendAFXConnector',
        'email' => 'Mireo@FrontendAFX.Connector'
    ];

    public function push($packagePath){
      $this->storageDir = $packagePath;

    }

    protected function execute($cmd){
        $cwd = getcwd();
        chdir($this->repo->getRepositoryPath());
        exec($cmd . ' 2>&1', $output, $ret);
        chdir($cwd);
        if($ret !== 0)
        {
            throw new \Exception(sprintf('Git Error: %s', implode(';', $output)));
        }
        return $output;
    }

    protected function initRepoIfNecessary() {

        $path = realpath($this->storageDir);

        if (!is_dir(Files::concatenatePaths([$path, '.git']))) {
            GitClient::init($path);

            $repo = $this->getRepo();
            $repo->execute(['config', 'user.name', $this->default['name']]);
            $repo->execute(['config', 'user.email', $this->default['email']]);
            $repo->addAllChanges()->commit('initial commit');

        }else{
            $this->getRepo();
        }


    }

    public function commitChanges($message = null) {

        $repo = $this->getRepo();

        $repo->addAllChanges();

        $list = $repo->execute(['diff', '--staged', '--name-status']);

        if (empty($list)) {
            return;
        }

        if ($message === null) {
            $list = $repo->execute(['diff', '--staged', '--name-status']);
//            var_dump($list);
            $list = array_map(function ($i) { return substr($i, 2); }, $list);
            $list = array_filter($list, function ($i) { return strpos($i, '_Resources') === false; });
//            $list2 = array_map(function ($i) { return '-m ' . substr($i, 2); }, $list);
//            var_dump($list, '------------------------');
//            array_unshift($list, 'Changed pages:');
//            $message = '"'.implode(PHP_EOL, $list).'"';
//            $repo->commit('Changed pages:', $list2);
//            array_unshift($list2, 'commit');

            if (empty($list)) {
                $repo->commit('Assets update');
            } else {
                $authorString = $this->default['name'];
                $mail = $this->default['email'];
                $author = $this->userService->getBackendUser();
                if( $author ){
                    $authorString = $author->getLabel();
                    $primaryAddress = $author->getPrimaryElectronicAddress();
                    if( $primaryAddress ){
                        $mail = $primaryAddress->__toString();
                    }
                }
                $authorString .= " <$mail>";
//                $repo->execute(['commit', '-m "Changed pages"', '-m '.implode(';', $list)]);

                $this->execute('git commit -m "Changed pages: '.implode(';', $list).'" --author="'.$authorString.'"');
            }
        } else {

            $repo->commit($message);
        }



//        var_dump($x);

    }

}
