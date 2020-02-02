<?php
/**
 * Created by PhpStorm.
 * User: pkaminski
 * Date: 2019-10-15
 * Time: 21:24
 */

namespace Hb180\StaticRendering\Storage;

use Cz\Git\GitException;
use Cz\Git\GitRepository as GitClient;
use Neos\Neos\Service\UserService;
use Neos\Utility\Files;
use Neos\Flow\Annotations as Flow;

class GitStorage
{

    /**
     * @Flow\InjectConfiguration(package="Hb180.StaticRendering.storage.workingDir")
     * @var string
     */
    protected $storageDir;

    /**
     * @var GitClient
     */
    protected $repo = null;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @var array
     */
    protected $default = [
        'name' => 'Hb180.StaticRenderer',
        'email' => 'Hb180@Static.Renderer'
    ];

    public function getRepo() {
        if ($this->repo === null) {
            $path = realpath($this->storageDir);
            $this->repo = new GitClient($path);
        }

        return $this->repo;
    }

    public function getCurrentTag(){
        if( $this->repo === null ){
            $this->initRepoIfNecessary();
        }
        $cmd = "git describe --tags";
        $cwd = getcwd();
        chdir($this->repo->getRepositoryPath());
        exec($cmd . ' 2>&1', $tag, $ret);
        chdir($cwd);
        if($ret !== 0)
        {
            return null;
        }
        if( $tag && isset($tag[0]) ){
            $tag = $tag[0];
        }
        preg_match('/^(?<commitTag>[\d\.]*)-.*$|^(?<tag>.*)$/', $tag, $result);
        return isset($result['commitTag']) && $result['commitTag']?$result['commitTag']:$result['tag'];
    }

    public function getLastTag(){
        if( $this->repo === null ){
            $this->initRepoIfNecessary();
        }
        $cmd = 'git for-each-ref --count=1 --sort=-refname --format="%(refname:short)"';
        $current = $this->execute($cmd);
        if( $current && isset($current[0]) ){
            return (string) $current[0] !== 'master'?$current[0]:null;
        }
        return null;
//        $this->repo->getTags();
    }

    protected function execute($cmd){
        $cwd = getcwd();
        chdir($this->repo->getRepositoryPath());
        exec($cmd . ' 2>&1', $output, $ret);
        chdir($cwd);
        if($ret !== 0)
        {
            throw new GitException(sprintf('Git Error: %s', implode(';', $output)));
        }
        return $output;
    }

    public function createBuild(){
        if( $this->repo === null ){
            $this->initRepoIfNecessary();
        }
        $lastTag = $this->getLastTag();
        $start = $lastTag;
        if($start){
            $start = (integer) str_replace('.','', $start);
            ++$start;
        }else{
            $start = 100;
        }
        $helperTag = (string) $start;
        $tagName = [];
        for( $i=1; $i<3; ++$i){
            $tagName[] = substr($helperTag, 0-$i, 1);
            if($i == 2){
                $tagName[] = substr($helperTag, 0, -2);
            }
        }

        $tagName = implode('.', array_reverse($tagName));

        $name = $this->default['name'];
        $mail = $this->default['email'];

        $author = $this->userService->getBackendUser();
        if( $author ){
            $name = $author->getLabel();
            $primaryAddress = $author->getPrimaryElectronicAddress();
            if( $primaryAddress ){
                $mail = $primaryAddress->__toString();
            }
        }
        $currentTag = $this->getCurrentTag();
        $lastCommit = $this->getLastCommit();
        $this->repo->checkout($lastCommit);

        $list = $this->execute('git diff '.$lastTag.' --staged --name-status');
        $list = array_map(function ($i) { return substr($i, 2); }, $list);
        $list = array_filter($list, function ($i) { return strpos($i, '_Resources') === false; });

        $message = $list;

        $this->repo->execute(['config', 'user.name', $name]);
        $this->repo->execute(['config', 'user.email', $mail]);
        if( $tagName == '1.0.0' ){
            $this->execute('git tag '.$tagName.' -a -m "Initial"');
        }else{
            $this->execute('git tag '.$tagName.' -a -m "Changed pages: '.implode(';',$message).'"');
        }
        $this->repo->execute(['config', 'user.name', $this->default['name']]);
        $this->repo->execute(['config', 'user.email', $this->default['email']]);
        $this->repo->checkout($currentTag);
        //git tag -a annoted -m "commits"
    }


    public function checkIfNewTagAllowed(){
        if( $this->repo === null ){
            $this->initRepoIfNecessary();
        }

        $cmd = 'git describe --contains';
        try{
            $this->execute($cmd);
        }catch(\Exception $e){
//            \Neos\Flow\var_dump($e->getMessage());exit;
            return TRUE;
        }

        $lastTag = $this->getLastTag();

        $lastCommit = $this->getLastCommit();

        $tags = $this->execute("git describe --tags ".$lastCommit);

        if( $tags && $tags[0] && $tags[0] == $lastTag ){
            return false;
        }

        return TRUE;
    }

    public function getCurrentCommit(){
        if( $this->repo === null ){
            $this->initRepoIfNecessary();
        }
        $cwd = getcwd();
        chdir($this->repo->getRepositoryPath());
        $cmd = 'git rev-parse --verify HEAD';
        exec($cmd . ' 2>&1', $current, $ret);
        if( $current && isset($current[0]) ){
            $current = $current[0];
        }
        chdir($cwd);
        if($ret !== 0)
        {
            throw new GitException("Command '$cmd' failed (exit-code $ret).", $ret);
        }
        return $current;
    }

    public function getLastCommit(){
        if( $this->repo === null ){
            $this->initRepoIfNecessary();
        }
        $cmd = 'git log --format="%H" --reflog -n 1';
        $last = $this->execute($cmd);
        if( $last && isset($last[0]) ){
            $last = $last[0];
        }
        return $last;
    }

    public function getCommitsList(){
        if( $this->repo === null ){
            $this->initRepoIfNecessary();
        }
//        \Neos\Flow\var_dump($this->repo->getTags());exit;
//        $cmd = 'git log --pretty="%H|%cd|%s" --date=local --reflog';
//        $cmd = 'git for-each-ref --format="%(refname:short)|%(creatordate:local)|%(subject)|%(body)" refs/tags';
        $cmd = 'git for-each-ref --format="%(objectname)|%(taggerdate:local)|%(subject)|%(refname:short)|%(taggername)|%(taggeremail)" refs/tags --sort=-refname';

        $cwd = getcwd();
        chdir($this->repo->getRepositoryPath());
        exec($cmd . ' 2>&1', $logs, $ret);
        chdir($cwd);

        if($ret !== 0)
        {
            throw new GitException("Command '$cmd' failed (exit-code $ret).", $ret);
        }
        $current = $this->getCurrentTag();

//        preg_match_all('/^(?<commit>.*)\|(?<date>.*)\|(?<comment>.*)/m', implode(PHP_EOL, $logs), $output_array);
        $data = [];
        foreach( $logs as $log ){
            preg_match('/^(?<commit>.*)\|(?<date>.*)\|(?<comment>.*)\|(?<tag>.*)\|(?<authorname>.*)\|(?<authoremail>.*)$/m', $log, $output_array);
            $data[] = [
                'commit' => $output_array['commit'],
                'date' => $output_array['date'],
                'comment' => trim($output_array['comment']),
                'tag' => $output_array['tag'],
                'authorname' => trim($output_array['authorname']),
                'authoremail' => trim($output_array['authoremail']),
                'selected' => $output_array['tag']==substr( $current, 0, strlen($output_array['tag']))?TRUE:FALSE
            ];
        }
        return $data;
    }

    public function selectCommit($commit){
        if( $this->repo === null ){
            $this->initRepoIfNecessary();
        }
        $this->repo->checkout($commit);
    }

    public function initRepoIfNecessary() {

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
