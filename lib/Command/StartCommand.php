<?php

namespace Phpactor\Extension\LanguageServer\Command;

use Phpactor\LanguageServer\LanguageServerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    const NAME = 'language-server';

    /**
     * @var LanguageServerBuilder
     */
    private $languageServerBuilder;

    public function __construct(LanguageServerBuilder $languageServerBuilder)
    {
        parent::__construct();
        $this->languageServerBuilder = $languageServerBuilder;
    }

    protected function configure()
    {
        $this->setDescription('Start Language Server');
        $this->addOption('address', null, InputOption::VALUE_REQUIRED, 'Start a TCP server at this address (e.g. 127.0.0.1:0)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->languageServerBuilder;

        if ($output instanceof ConsoleOutput) {
            $output->getErrorOutput()->writeln(
                '<info>Starting language server, use -vvv for verbose output</>'
            );
        }

        if ($input->getOption('address')) {
            $this->configureTcpServer($input->getOption('address'), $builder);
        }

        $server = $builder->build();
        $server->start();
    }

    private function configureTcpServer($address, LanguageServerBuilder $builder)
    {
        assert(is_string($address));
        $builder->tcpServer($address);
    }
}
