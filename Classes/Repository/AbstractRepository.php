<?php
namespace Mireo\FrontendAFXConnector\Repository;

use Neos\Flow\Annotations as Flow;

abstract class AbstractRepository
{

    /**
     * @param $packagePath
     * @return boolean
     */
    abstract public function push($packagePath);

}
