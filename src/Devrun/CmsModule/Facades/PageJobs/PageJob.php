<?php


namespace Devrun\CmsModule\Facades\PageJobs;

use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Translation\Translator;
use Nette\Reflection\ClassType;
use Nette\Utils\Strings;

class PageJob
{
    /** @var EntityManager */
    private $entityManager;

    /** @var Translator */
    private $translator;

    /** @var array */
    private $urlRoutes;

    /** @var string */
    private $wwwDir;



    /**
     * PageJob constructor.
     * @param EntityManager $entityManager
     * @param Translator $translator
     */
    public function __construct(EntityManager $entityManager, Translator $translator, string $wwwDir)
    {
        $this->entityManager = $entityManager;
        $this->translator    = $translator;
        $this->wwwDir        = $wwwDir;
    }


    /**
     * @param $packageName
     * @param $moduleName
     * @return PackageEntity
     */
    public function createPackage($packageName, $moduleName)
    {
        return new PackageEntity($packageName, $moduleName, $this->translator);
    }


    public function initFileStructures($packageName, $moduleName)
    {
        if (!is_dir($dir = "{$this->wwwDir}/css/$moduleName/themes")) {
            mkdir($dir, 0775, true);
        }

        if (!is_dir($dir = "{$this->wwwDir}/images/$moduleName")) {
            mkdir($dir, 0775, true);
        }


    }


    /**
     * @param string $class
     * @param string $actionName
     *
     * @return PageEntity
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function createPage(string $class, string $actionName)
    {
        $classType = ClassType::from($class);

        $moduleName = $this->getModuleNameFromClass($class);

        $presenterName = str_replace('Presenter', '', $classType->getShortName());
        $actionName    = lcfirst($actionName);

        $file = Strings::replace($class, "/\w+$/") . "templates/$presenterName/$actionName.latte";
        $file = str_replace("\\", "/", $file);

        $pageEntity = (new PageEntity($moduleName, $presenterName, $actionName, $this->translator))
            ->setClass($class)
            ->setFile($file)
            ->setRootPosition($this->getPageRootPosition() + 1);

        $pageEntity->mergeNewTranslations();
        return $pageEntity;
    }


    public function createRoute(PageEntity $pageEntity, string $url, string $translatorName, PackageEntity $packageEntity = null)
    {
        $routeEntity = new RouteEntity($pageEntity, $this->translator);
        $routeEntity->setPackage($packageEntity);
        $routeEntity->setName($this->translator->translate($translatorName));
        $routeEntity->setUrl($this->checkUrl($url));
        $routeEntity->setUri($this->checkUrl( ':' . ucfirst($pageEntity->getModule()) . ':' . ucfirst($pageEntity->getPresenter()) . ':' . $pageEntity->getAction()));

        $routeEntity->mergeNewTranslations();
        return $routeEntity;
    }


    private function checkUrl(string $url)
    {
        $urlRoutes = $this->getUrlRoutes();
        $inc      = 1;
        $checkUrl = $url;

        while (in_array($checkUrl, $urlRoutes)) {
            $checkUrl = $url . "-" . $inc++;
        }

        $this->urlRoutes[] = $url = $checkUrl;

        return $url;
    }


    private function getUrlRoutes()
    {
        if (null === $this->urlRoutes) {
            $urlRoutes = $this->entityManager
                ->createQueryBuilder()
                ->from(\Devrun\CmsModule\Entities\RouteTranslationEntity::class, 'e')
                ->select('e.url')
                ->getQuery()
                ->getArrayResult();

            foreach ($urlRoutes as $urlRoute) {
                $this->urlRoutes[] = $urlRoute['url'];
            }
        }

        return $this->urlRoutes;
    }


    /**
     * @param string $class
     * @return string  [input FrontModule\Homepage... , return front]
     */
    public function getModuleNameFromClass(string $class)
    {
        $moduleName = Strings::before($class, "\\");
        return str_replace('Module', '', $moduleName);
    }


    /**
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPageRootPosition()
    {
        return intval($this->entityManager
            ->createQueryBuilder()
            ->from(\Devrun\CmsModule\Entities\PageEntity::class, 'e')
            ->select('max(e.rootPosition)')
            ->getQuery()
            ->getSingleScalarResult());
    }


    public function flush(array $entities = [])
    {
        if (!empty($entities)) {
            $this->entityManager->persist($entities)->flush();
        }

    }

}