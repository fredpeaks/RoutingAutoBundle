<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Bundle\RoutingAutoBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\Bundle\PHPCRBundle\Command\DoctrineCommandHelper;
use Symfony\Cmf\Component\RoutingAuto\UriContextCollection;

class RefreshCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('cmf:routing:auto:refresh')
            ->setDescription('Refresh auto-routeable documents')
            ->setHelp(<<<HERE
WARNING: Experimental!

This command iterates over all Documents that are mapped by the auto
routing system and re-applys the auto routing logic.

You can specify the "--verbose" option to output detail for each created
route.

Specify the "--dry-run" option to not write any changes to the database.

Use "--class" to only apply the changes to a single class - although beware this
may cause an error if you persist a class whose auto routing configuration
relies on the auto routing of another class.
HERE
        );

        $this->addOption('dry-run', null, InputOption::VALUE_NONE,
            'Do not write any change to the database.'
        );
        $this->addOption('class', null, InputOption::VALUE_REQUIRED,
            'Only update the given class FQN'
        );
        $this->addOption('session', null, InputOption::VALUE_OPTIONAL, 'The session to use for this command');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $persistenceType = $container->getParameter('cmf_routing_auto.persistence_type');
        if('Phpcr' === $persistenceType) {
            $manager = $container->get('doctrine_phpcr');
        } else {
            $manager = $container->get('doctrine');
        }
        $om = $manager->getManager();
        $factory = $container->get('cmf_routing_auto.metadata.factory');
        $arm = $container->get('cmf_routing_auto.auto_route_manager');

        $session = $input->getOption('session');
        $dryRun = $input->getOption('dry-run');
        $class = $input->getOption('class');
        $verbose = $input->getOption('verbose');

        if('Phpcr' === $persistenceType) {
            $uow = $om->getUnitOfWork();
            DoctrineCommandHelper::setApplicationPHPCRSession(
                $this->getApplication(),
                $session
            );
        }

        if ($class) {
            $mapping = array($class => $class);
        } else {
            $mapping = iterator_to_array($factory->getIterator());
        }

        foreach (array_keys($mapping) as $classFqn) {
            $output->writeln(sprintf('<info>Processing class: </info> %s', $classFqn));

            if('Phpcr' === $persistenceType) {
                $qb = $om->createQueryBuilder();
                $qb->from()->document($classFqn, 'a');
            } else {
                $repo = $om->getRepository($classFqn);
                $qb = $repo->createQueryBuilder('a');
            }
            $q = $qb->getQuery();
            $result = $q->getResult();

            foreach ($result as $autoRouteableDocument) {
                if('Phpcr' === $persistenceType) {
                    $id = $uow->getDocumentId($autoRouteableDocument);
                } else {
                    $id = $autoRouteableDocument->getId();
                }
                $output->writeln('  <info>Refreshing: </info>'.$id);

                $uriContextCollection = new UriContextCollection($autoRouteableDocument);
                $arm->buildUriContextCollection($uriContextCollection);

                foreach ($uriContextCollection->getUriContexts() as $uriContext) {
                    $autoRoute = $uriContext->getAutoRoute();
                    $om->persist($autoRoute);
                    if('Phpcr' === $persistenceType) {
                        $autoRouteId = $uow->getDocumentId($autoRoute);
                    } else {
                        $autoRouteId = $autoRoute->getId();
                    }

                    if ($verbose) {
                        $output->writeln(sprintf(
                            '<comment>    - %sPersisting: </comment> %s <comment>%s</comment>',
                            $dryRun ? '(dry run) ' : '',
                            $autoRouteId,
                            '[...]'.substr(get_class($autoRoute), -10)
                        ));
                    }

                    if (true !== $dryRun) {
                        $om->flush();
                    }
                }
            }
        }
    }
}
