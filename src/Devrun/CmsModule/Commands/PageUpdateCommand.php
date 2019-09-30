<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    PageUpdateCommand.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\Commands;

use Devrun\CmsModule\Entities\LogEntity;
use Devrun\CmsModule\Facades\PageFacade;
use Doctrine\ORM\Tools\SchemaTool;
use Kdyby\Doctrine\Console\ColoredSqlOutput;
use Kdyby\Doctrine\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PageUpdateCommand extends Command
{

    protected $name = 'devrun:page:update';

    /** @var EntityManager */
    private $em;

    /** @var PageFacade */
    private $pageFacade;

    /**
     * PageUpdateCommand constructor.
     *
     * @param EntityManager $em
     * @param PageFacade    $pageFacade
     */
    public function __construct(EntityManager $em, PageFacade $pageFacade)
    {
        parent::__construct();
        $this->em         = $em;
        $this->pageFacade = $pageFacade;
    }


    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName($this->name)
            ->setDescription('Executes (or dumps) the SQL needed to update the database to match the current pages.')
            ->setDefinition(array(
                new InputOption(
                    'dump-sql', null, InputOption::VALUE_NONE,
                    'Dumps the generated SQL statements to the screen (does not execute them).'
                ),
                new InputOption(
                    'force', 'f', InputOption::VALUE_NONE,
                    'Causes the generated SQL statements to be physically executed against your database.'
                ),
            ));


        $this->setHelp(<<<EOT
The <info>%command.name%</info> command generates the SQL needed to
synchronize the database schema with the current mapping metadata of the
default entity manager.

For example, if you add metadata for a new column to an entity, this command
would generate and output the SQL needed to add the new column to the database:

<info>%command.name% --dump-sql</info>

Alternatively, you can execute the generated queries:

<info>%command.name% --force</info>

If both options are specified, the queries are output and then executed:

<info>%command.name% --dump-sql --force</info>

Finally, be aware that if the <info>--complete</info> option is passed, this
task will drop all database assets (e.g. tables, etc) that are *not* described
by the current metadata. In other words, without this option, this task leaves
untouched any "extra" tables that exist in the database, but which aren't
described by any metadata.

<comment>Hint:</comment> If you have a database with tables that should not be managed
by the ORM, you can use a DBAL functionality to filter the tables and sequences down
on a global level:

    \$config->setFilterSchemaAssetsExpression(\$regexp);
EOT
        );
    }




    private function getSyncPagesSqlQueries()
    {
        $em = $this->em;
        $em->getConnection()
            ->getConfiguration()
            ->setSQLLogger($sqlLogger = new \Devrun\Doctrine\Logging\SQLLogger());

        try {
            $sqlQueries = [];
            $em->persist($rollback = new LogEntity(null, '', '', ''));

            $this->pageFacade->getSynchronizePagesJob()->synchronizePages($need = true);

            $em->flush();

        } catch (\Doctrine\DBAL\Exception\DriverException $exception) {

            $isErrTable   = \Nette\Utils\Strings::contains($exception->getMessage(), 'INSERT INTO log');
            $isErrMessage = \Nette\Utils\Strings::contains($exception->getMessage(), "Incorrect integer value: '' for column 'target_key'");

            if ($isErrTable && $isErrMessage) {
                $sqlQueries = $sqlLogger->getSqlQueries();

                if ($sqlQueries && $sqlQueries[count($sqlQueries) - 1] == 'ROLLBACK;') {
                    $sqlQueries[count($sqlQueries) - 1] = 'COMMIT;';
                }
            }

        } finally {

            // disable logger
            $em->getConnection()
                ->getConfiguration()
                ->setSQLLogger(null);
        }

        return $sqlQueries;
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dumpSql = true === $input->getOption('dump-sql');
        $force   = true === $input->getOption('force');

/*
SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
*/


        if ($dumpSql) {
            if ($sqlQueries = $this->getSyncPagesSqlQueries()) {
                $sqlOutput = new ColoredSqlOutput($output);

                foreach ($sqlQueries as $sqlQuery) {
                    $sqlOutput->writeln($sqlQuery);
                }

            } else {
                $output->writeln('Nothing to update...');
            }


        } elseif ($force) {

            $output->writeln('Updating database schema...');
            if ($sqlQueries = $this->getSyncPagesSqlQueries()) {
                $output->writeln(count($sqlQueries) . " sql queries...");

            } else {
                $output->writeln('Nothing to update...');
            }

            $output->writeln('Database schema updated successfully!');

        } else {
            $output->writeln('ATTENTION: This operation should not be executed in a production environment.');
            $output->writeln('allowed option --dump-sql or --force.');
            return 1;
        }

        return 0;

    }


}