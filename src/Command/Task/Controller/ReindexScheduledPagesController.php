<?php

namespace Macareux\ReindexScheduledPage\Command\Task\Controller;

use Concrete\Core\Command\Batch\Batch;
use Concrete\Core\Command\Task\Controller\AbstractController;
use Concrete\Core\Command\Task\Input\InputInterface;
use Concrete\Core\Command\Task\Runner\BatchProcessTaskRunner;
use Concrete\Core\Command\Task\Runner\TaskRunnerInterface;
use Concrete\Core\Command\Task\TaskInterface;
use Concrete\Core\Database\Connection\Connection;
use Concrete\Core\Localization\Service\Date;
use Concrete\Core\Page\Command\ReindexPageTaskCommand;

class ReindexScheduledPagesController extends AbstractController
{
    /**
     * @var \Concrete\Core\Database\Connection\Connection
     */
    protected $connection;

    /**
     * @var \Concrete\Core\Localization\Service\Date
     */
    protected $date;

    /**
     * @param \Concrete\Core\Database\Connection\Connection $connection
     * @param \Concrete\Core\Localization\Service\Date $date
     */
    public function __construct(Connection $connection, Date $date)
    {
        $this->connection = $connection;
        $this->date = $date;
    }

    public function getName(): string
    {
        return t('Reindex Scheduled Pages');
    }

    public function getDescription(): string
    {
        return t('Reindex pages that has not indexed scheduled version.');
    }

    public function getTaskRunner(TaskInterface $task, InputInterface $input): TaskRunnerInterface
    {
        $batch = Batch::create();
        $qb = $this->connection->createQueryBuilder();
        // Get all pages that has not indexed scheduled version
        $qb->select('p.cID')
            ->from('Pages', 'p')
            ->leftJoin('p', 'CollectionVersions', 'cv', 'p.cID = cv.cID')
            ->leftJoin('p', 'PageSearchIndex', 'i', 'p.cID = i.cID')
            ->where('p.cIsActive = 1')
            ->andWhere('p.cPointerID < 1')
            ->andWhere('p.cIsTemplate = 0')
            ->andWhere('cv.cvID = (select max(cvID) from CollectionVersions where cID = cv.cID and cvIsApproved = 1 and cvPublishDate > i.cDateLastIndexed and cvPublishDate <= :now)')
            ->setParameter('now', $this->date->getOverridableNow())
        ;
        $result = $qb->execute();
        $count = 0;
        foreach ($result->fetchAllAssociative() as $row) {
            $batch->add(new ReindexPageTaskCommand($row['cID']));
            $count++;
        }

        return new BatchProcessTaskRunner($task, $batch, $input, t('Reindexing %s pages...', $count));
    }
}
