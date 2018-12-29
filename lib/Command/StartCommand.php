<?php

namespace Phpactor\Extension\LanguageServer\Command;

use Phpactor\LanguageServer\LanguageServerBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    const NAME = 'server:start';

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
        $this->setDescription('EXPERIMENTAL start a Phpactor language server');
        $this->addOption('address', null, InputOption::VALUE_REQUIRED, 'Address to start TCP serve', '127.0.0.1:8888');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $builder = $this->languageServerBuilder;

        $this->configureTcpServer($input->getOption('address'), $builder);

        $output->writeln('<info>Starting TCP server, use -vvv for verbose output</>');

        $server = $builder->build();
        $server->start();
    }

    private function enableRecording($file, LanguageServerBuilder $builder)
    {
        assert(is_string($file));
        $builder->recordTo($file);
    }

    private function configureTcpServer($address, LanguageServerBuilder $builder)
    {
        assert(is_string($address));
        $builder->tcpServer($address);
    }
}
