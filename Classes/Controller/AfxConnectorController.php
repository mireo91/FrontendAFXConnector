<?php
namespace Mireo\FrontendAFXConnector\Controller;

use Mireo\FrontendAFXConnector\Repository\GitRepository;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Package\PackageManager;
use Neos\Neos\View\FusionView;
use Neos\Utility\Files;

/**
 * A controller which allows for logging into the backend
 */
class AfxConnectorController extends ActionController
{

    /**
     * @var string
     */
    protected $defaultViewObjectName = FusionView::class;

    /**
     * @var FusionView
     */
    protected $view;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = [
        'html' => FusionView::class,
        'json' => JsonView::class,
    ];

    /**
     * @var array
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json',
    ];

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @param NodeInterface $node
     * @param array $fusionCode
     */
    public function showAction($node, $fusionCode){
        $this->view->setOption('enableContentCache', false);
        $this->view->assign('value', $node);
    }

    /**
     * @param array $fusionCode
     */
    public function saveAction($fusionCode){
        $packages = [];
        foreach( $fusionCode as $resource=>$content ){
            preg_match('/^resource:\/\/(?<packageKey>[^\/]*)\/(?<resourcePath>.*)$/', $resource, $matches);
            if( !isset($matches['packageKey']) ){
                continue;
            }
            $packagePath = $this->packageManager->getPackage($matches['packageKey'])->getPackagePath();
            $absoluteFilePath = Files::concatenatePaths([
                $packagePath,
                'Resources',
                $matches['resourcePath']
            ]);

            if( strpos($absoluteFilePath, 'afx') !== false ) {
                file_put_contents($resource, $content);
                if (isset($packages[$packagePath])) {
                    $packages[$packagePath][] = $absoluteFilePath;
                } else {
                    $packages[$packagePath][0] = $absoluteFilePath;
                }
            }
        }

        foreach($packages as $packagePath => $value){
            if( $value ){
                $gitRepo = new GitRepository($packagePath);
                $gitRepo->push($value);

            }
        }

//        \Neos\Flow\var_dump($packages);exit;

//        $cwd = getcwd();
//        \Neos\Flow\var_dump($cwd);exit;
//        chdir();
//        exec($cmd . ' 2>&1', $output, $ret);
//        chdir($cwd);

//        $this->view->setOption('enableContentCache', false);
        $this->view->assign('value', ['status'=>'OK']);
    }

}
