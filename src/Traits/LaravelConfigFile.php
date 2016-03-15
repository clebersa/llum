<?php

namespace Acacha\Llum\Traits;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LaravelConfigFile.
 * @property string $laravel_config_file
 * @property OutputInterface $output
 */
trait LaravelConfigFile
{
    /**
     * Avoids using bash using stubs instead to modify config/app.php file.
     *
     * @var bool
     */
    protected $noBash = false;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        if ($input->hasOption('no-bash')) {
            $this->noBash = $input->getOption('no-bash');
        }
    }

    /**
     * Check is --no-bash option is active.
     *
     * @return bool
     */
    private function isNoBashActive()
    {
        return $this->noBash;
    }

    /**
     * Add Laravel IDE Helper provider to config/app.php file.
     *
     * @return int|null
     */
    protected function addLaravelIdeHelperProvider()
    {
        return $this->addProvider('Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class');
    }

    /**
     *  Add provider to config/app.php file.
     *
     * @param $provider
     *
     * @return int|null
     */
    private function addProvider($provider)
    {
        return $this->addTextIntoMountPoint('#llum_providers', $provider);
    }

    /**
     * Add alias to config/app.php file.
     *
     * @param string $alias
     *
     * @return int|null
     */
    private function addAlias($alias)
    {
        return $this->addTextIntoMountPoint('#llum_aliases', $alias);
    }

    /**
     * Insert text into file using mountpoint. Mountpoint is maintained at file.
     *
     * @param string $mountpoint
     * @param $textToAdd
     *
     * @return int|null
     */
    private function addTextIntoMountPoint($mountpoint, $textToAdd)
    {
        passthru(
            'sed -i \'s/.*'.$mountpoint.'.*/ \ \ \ \ \ \ \ '.$this->scapeSingleQuotes(preg_quote($textToAdd)).',\n \ \ \ \ \ \ \ '.$mountpoint.'/\' '.$this->laravel_config_file, $error);

        return $error;
    }

    /**
     * scape single quotes for sed using \x27.
     *
     * @param string $str
     *
     * @return string
     */
    private function scapeSingleQuotes($str)
    {
        return str_replace("'", '\\x27', $str);
    }

    /**
     * Installs provider in laravel config/app.php file.
     *
     * @param $provider
     */
    protected function provider($provider)
    {
        if ($this->installConfigAppFile() == -1) {
            return;
        }
        $this->addProvider($provider);
    }

    /**
     * Setup laravel config file adding providers and aliases.
     *
     * @param $providers
     * @param $aliases
     * @return int
     */
    private function setupLaravelConfigFile($providers, $aliases)
    {
        if ($this->installConfigAppFile() == -1) {
            return -1;
        }

        $this->addProviders($providers);

        $this->addAliases($aliases);
    }

    /**
     * Installs alias/facade in laravel config/app.php file.
     *
     * @param $aliasName
     * @param $aliasClass
     */
    protected function alias($aliasName, $aliasClass)
    {
        if ($this->installConfigAppFile() == -1) {
            return;
        }
        $this->addAlias("'".$aliasName."' => ".$aliasClass);
    }

    /**
     * Install /config/app.php file using bash script.
     */
    protected function installConfigAppFileWithBash()
    {
        passthru(__DIR__.'/../bash_scripts/iluminar.sh '.$this->laravel_config_file);
    }

    /**
     * Install /stubs/app.php into /config/app.php.
     */
    protected function installConfigAppFileWithStubs()
    {
        copy(__DIR__.'/stubs/app.php', $this->laravel_config_file);
    }

    /**
     * Check if Laravel config file exists.
     *
     * @return bool
     */
    protected function checkIfLaravelConfigFileExists()
    {
        return file_exists($this->laravel_config_file);
    }

    /**
     * Install llum custom config/app.php file.
     *
     * @return int
     */
    protected function installConfigAppFile()
    {
        $this->testLaravelConfigFileExists();

        $this->showWarningIfLaravelConfigAlreadySupportsLlum();

        if ($this->isNoBashActive()) {
            $this->installConfigAppFileWithStubs();
            $this->output->writeln('<info>File '.$this->laravel_config_file.' overwrited correctly with and stub.</info>');
        } else {
            $this->installConfigAppFileWithBash();
        }

        return 0;
    }

    /**
     * Test Laravel config file exists.
     *
     * @return int
     */
    private function testLaravelConfigFileExists()
    {
        if (! $this->checkIfLaravelConfigFileExists()) {
            $this->output->writeln('<error>File '.$this->laravel_config_file.' doesn\'t exists');

            return -1;
        }
    }

    /**
     * Show warning if Laravel config file already supports llum.
     *
     * @return int
     */
    private function showWarningIfLaravelConfigAlreadySupportsLlum()
    {
        if ($this->configAppFileAlreadyInstalled()) {
            $this->output->writeln('<info>File '.$this->laravel_config_file.' already supports llum.</info>');

            return 0;
        }
    }

    /**
     * Add providers to Laravel config file.
     *
     * @param $providers
     */
    protected function addProviders($providers)
    {
        foreach ($providers as $provider) {
            $this->output->writeln('<info>Adding '.$provider.' to Laravel config app.php file</info>');
            $this->addProvider($provider);
        }
    }

    /**
     * Add aliases to Laravel config file.
     *
     * @param $aliases
     */
    protected function addAliases($aliases)
    {
        if ($aliases == null) {
            return;
        }
        foreach ($aliases as $alias => $aliasClass) {
            $this->output->writeln('<info>Adding '.$aliasClass.' to Laravel config app.php file</info>');
            $this->addAlias("'$alias' => ".$aliasClass);
        }
    }

    /**
     * Check if config/app.php stub file is already installed.
     *
     * @return bool
     */
    protected function configAppFileAlreadyInstalled()
    {
        if (strpos(file_get_contents($this->laravel_config_file), '#llum_providers') !== false) {
            return true;
        }

        return false;
    }
}