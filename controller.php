<?php

namespace Concrete\Package\MdReindexScheduledPage;

use Concrete\Core\Command\Task\Manager;
use Concrete\Core\Command\Task\TaskService;
use Concrete\Core\Command\Task\TaskSetService;
use Concrete\Core\Entity\Automation\Task;
use Concrete\Core\Entity\Command\TaskProcess;
use Concrete\Core\Package\Package;
use Doctrine\ORM\EntityManagerInterface;
use Macareux\ReindexScheduledPage\Command\Task\Controller\ReindexScheduledPagesController;

class Controller extends Package
{
    protected $pkgHandle = 'md_reindex_scheduled_page';

    protected $appVersionRequired = '9.0.0';

    protected $pkgVersion = '0.0.1';

    protected $pkgAutoloaderRegistries = [
        'src' => '\Macareux\ReindexScheduledPage',
    ];

    public function getPackageName()
    {
        return t('Reindex Scheduled Page Task');
    }

    public function getPackageDescription()
    {
        return t('A package to add a task to reindex pages that has not indexed scheduled version.');
    }

    public function install()
    {
        $pkg = parent::install();

        /** @var TaskService $taskService */
        $taskService = $this->app->make(TaskService::class);
        $task = $taskService->getByHandle('md_reindex_scheduled_pages');
        if (!$task) {
            $task = new Task();
            $task->setHandle('md_reindex_scheduled_pages');
            $task->setPackage($pkg);
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->app->make(EntityManagerInterface::class);
            $entityManager->persist($task);
            $entityManager->flush();

            /** @var TaskSetService $taskSetService */
            $taskSetService = $this->app->make(TaskSetService::class);
            $taskSet = $taskSetService->getByHandle('seo');
            if ($taskSet) {
                $taskSetService->addTaskToSet($task, $taskSet);
            }
        }

        return $pkg;
    }

    public function uninstall()
    {
        /** @var TaskService $taskService */
        $taskService = $this->app->make(TaskService::class);
        $task = $taskService->getByHandle('md_reindex_scheduled_pages');
        if ($task) {
            /** @var EntityManagerInterface $entityManager */
            $entityManager = $this->app->make(EntityManagerInterface::class);
            $repository = $entityManager->getRepository(TaskProcess::class);
            $taskProcesses = $repository->findBy(['task' => $task]);
            foreach ($taskProcesses as $taskProcess) {
                $entityManager->remove($taskProcess);
            }
            $entityManager->remove($task);
            $entityManager->flush();
        }

        parent::uninstall();
    }

    public function on_start()
    {
        /** @var Manager $manager */
        $manager = $this->app->make(Manager::class);
        $manager->extend('md_reindex_scheduled_pages', function () {
            return $this->app->make(ReindexScheduledPagesController::class);
        });
    }
}
