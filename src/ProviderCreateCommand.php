<?php

namespace PluginMaster\Console;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProviderCreateCommand extends Command
{

    /**
     * application root path
     * @var string
     */
    private $rootPath;

    /**
     * application controller path
     * @var
     */
    private $providerPath;


    /**
     * app namespace from composer.json
     * @var string
     */
    private $appNamespace;

    /**
     * @var string
     */
    private $providerName;

    public function __construct($path)
    {
        parent::__construct();
        $this->rootPath     = $path;
        $this->providerPath = $path.'\\app\\Providers';
        $this->setNamespace();
    }

    protected function setNamespace()
    {
        $composer = file_get_contents($this->rootPath.DIRECTORY_SEPARATOR.'composer.json');

        $namespaces = json_decode($composer, true)['autoload']['psr-4'];

        foreach ($namespaces as $key => $namespace) {
            if ($namespace == 'app/') {
                $this->appNamespace = $key;
            }
        }

    }

    protected function configure()
    {
        $this->setName('make:provider')
            ->setDescription('Create provider!')
            ->setHelp('Create provider.')
            ->addArgument('providerName', InputArgument::REQUIRED, 'add provider name.')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'add provider.',
                ''
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->providerName = str_replace('/', '\\', trim($input->getArgument('providerName')));
        try {
            $this->createController();

            $output->writeln('<fg=green>Success: '.sprintf('Provider created at app\\Providers\\%s',
                    $this->providerName));

        } catch (Exception $e) {

            $output->writeln('<fg=red>Error: '.$e->getMessage());
        }

        return 1;
    }

    protected function createController(): void
    {

        $filePath = str_replace('/', '\\', $this->providerName).'.php';

        $pathTree = $this->providerPath;

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
                    throw new Exception("Middleware already exist at app\\Providers\\".$this->providerName);
                }
            }
        }
    }

    protected function getControllerContent()
    {
        $array              = explode('\\', $this->providerName);
        $className          = end($array);
        $namespaceFromInput = rtrim(str_replace($className, '', $this->providerName), "\\");
        $rightNamespace     = ($namespaceFromInput ? '\\'.$namespaceFromInput : '');


        $data = "<?php

namespace UIDons\App\Providers{$rightNamespace};
 
use PluginMaster\Contracts\Provider\ServiceProviderInterface;
 
class {$className} implements ServiceProviderInterface
{

    public function boot()
    {
 
    }

}
";

        return $data;
    }

}
