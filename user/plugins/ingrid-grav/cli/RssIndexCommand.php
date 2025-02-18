<?php
namespace Grav\Plugin\Console;

use Grav\Console\ConsoleCommand;
use Grav\Plugin\Rss;

/**
 * Class RssIndexCommand
 *
 * @package Grav\Plugin\Console
 */
class RssIndexCommand extends ConsoleCommand
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('index-rss')
            ->setDescription("Indexing rss")
            ->setHelp('The <info>index command</info> re-indexes the rss feeds.');
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        error_reporting(1);

        $this->output->writeln('');
        $this->output->writeln('<magenta>Re-indexing</magenta>');
        $this->output->writeln('');
        $start = microtime(true);
        $output = Rss::indexJob();
        $this->output->write($output);
        $this->output->writeln('');
        $end =  number_format(microtime(true) - $start,1);
        $this->output->writeln('');
        $this->output->writeln('Indexed in ' . $end . 's');

        return 0;
    }

}