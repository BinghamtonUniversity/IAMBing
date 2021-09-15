<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Env;
use Illuminate\Support\ProcessUtils;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\PhpExecutableFinder;

class ServeDev extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    // protected $name = 'servedev';
    protected $name = 'serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Serve IAMBing Locally from iambing.local:8000';

    /**
     * The current port offset.
     *
     * @var int
     */
    protected $portOffset = 0;

    /**
     * Execute the console command.
     *
     * @return int
     *
     * @throws \Exception
     */
    public function handle()
    {
        chdir(public_path());

        $ip = gethostbyname($this->host());
        if ($ip !== "127.0.0.1") {
            $this->line("<error>You must add the following entry to your /etc/hosts file:</error>");
            $this->line("127.0.0.1\t".$this->host());
            return 0;
        }
        $this->line("<info>Laravel development server started:</info> http://{$this->host()}:{$this->port()}");

        passthru($this->serverCommand(), $status);

        if ($status && $this->canTryAnotherPort()) {
            $this->portOffset += 1;

            return $this->handle();
        }

        return $status;
    }

    /**
     * Get the full server command.
     *
     * @return string
     */
    protected function serverCommand()
    {
        return sprintf('%s -S %s:%s %s',
            ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false)),
            $this->host(),
            $this->port(),
            ProcessUtils::escapeArgument(base_path('server.php'))
        );
    }

    /**
     * Get the host for the command.
     *
     * @return string
     */
    protected function host()
    {
        return $this->input->getOption('host');
    }

    /**
     * Get the port for the command.
     *
     * @return string
     */
    protected function port()
    {
        $port = $this->input->getOption('port') ?: 8000;

        return $port + $this->portOffset;
    }

    /**
     * Check if command has reached its max amount of port tries.
     *
     * @return bool
     */
    protected function canTryAnotherPort()
    {
        return is_null($this->input->getOption('port')) &&
               ($this->input->getOption('tries') > $this->portOffset);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on', 'iambing.local'],

            ['port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on', Env::get('SERVER_PORT')],

            ['tries', null, InputOption::VALUE_OPTIONAL, 'The max number of ports to attempt to serve from', 10],
        ];
    }
}