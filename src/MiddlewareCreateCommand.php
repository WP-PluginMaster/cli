<?php

namespace PluginMaster\Console;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MiddlewareCreateCommand extends Command
{

    /**
     * application root path
     * @var string
     */
    private string $rootPath;

    /**
     * application controller path
     * @var
     */
    private string $middlewarePath;


    /**
     * app namespace from composer.json
     * @var string
     */
    private string $appNamespace;

    /**
     * @var string
     */
    private string $middlewareName;

    public function __construct(string $path)
    {
        parent::__construct();
        $this->rootPath       = $path;
        $this->middlewarePath = $path.'\\app\\Http\\Middleware';
        $this->setNamespace();
    }

    /**
     * set Namespace for app directory from composer.json file
     */
    protected function setNamespace(): void
    {
        $composer = file_get_contents($this->rootPath.DIRECTORY_SEPARATOR.'composer.json');

        $namespaces = json_decode($composer, true)['autoload']['psr-4'];

        foreach ($namespaces as $key => $namespace) {
            if ($namespace == 'app/') {
                $this->appNamespace = $key;
            }
        }
    }

    /**
     * config for command
     */
    protected function configure(): void
    {
        $this->setName('make:middleware')
            ->setDescription('Create middleware!')
            ->setHelp('Create middleware.')
            ->addArgument('middlewareName', InputArgument::REQUIRED, 'add middleware name.')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'add middleware.',
                ''
            );
    }

    /**
     * execute command
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->middlewareName = str_replace('/', '\\', trim($input->getArgument('middlewareName')));
        try {
            $this->createController();

            $output->writeln('<fg=green>Success: '.sprintf('Middleware created at app\\Http\\Middleware\\%s ',
                    $this->middlewareName));

        } catch (Exception $e) {

            $output->writeln('<fg=red>Error: '.$e->getMessage());
        }

        return 1;
    }

    protected function createController(): void
    {
        $filePath = str_replace('/', '\\', $this->middlewareName).'.php';

        $pathTree = $this->middlewarePath;

        foreach (explode('\\', $filePath) as $path) {

            $pathTree .= '\\'.$path;

            if (!file_exists($pathTree)) {

                if (strpos($pathTree, '.php') !== false) {
                    file_put_contents($pathTree, $this->getControllerContent());
                } else {
                    mkdir($pathTree);
                }

            } else {
                if (is_file($pathTree)) {
                    throw new Exception("Middleware already exist at app\\Http\\Middleware\\".$this->middlewareName);
                }
            }
        }
    }

    protected function getControllerContent(): string
    {
        $array              = explode('\\', $this->middlewareName);
        $className          = end($array);
        $namespaceFromInput = rtrim(str_replace($className, '', $this->middlewareName), "\\");
        $rightNamespace     = ($namespaceFromInput ? '\\'.$namespaceFromInput : '');


        $data = "<?php

namespace {$this->appNamespace}Http\Middleware{$rightNamespace};

use PluginMaster\Contracts\Middleware\MiddlewareInterface;
use WP_REST_Request;

class {$className} implements MiddlewareInterface
{

    public function handler(WP_REST_Request ".'$request'.")
    {
        return true;
    }

}
";
        return $data;
    }

}
