<?php
namespace Mireo\FrontendAFXConnector\Repository;

use Neos\Flow\Annotations as Flow;

abstract class AbstractRepository
{
    /**
     * @var string
     */
    protected $packagePath;

    public function __construct($packagePath)
    {
        $this->packagePath = $packagePath;
    }

    /**
     * @param array $files
     * @return boolean
     */
    abstract public function push($files);

}
