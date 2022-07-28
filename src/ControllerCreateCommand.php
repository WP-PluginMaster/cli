<?php

namespace PluginMaster\Console;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ControllerCreateCommand extends Command
{
    /**
     * supported command options
     * @var array
     */
    private array $supportedOptions = ['api', 'action', 'shortcode', 'sideMenu'];


    /**
     * supported command option value
     * @var string[]
     */
    private array $optionValue = [
        'api'      => 'Api',
        'action' => 'Actions',
        'shortcode' => 'Shortcodes',
        'sideMenu' => 'SideMenu'
    ];

    /**
     * application root path
     * @var string
     */
    private string $rootPath;

    /**
     * application controller path
     * @var
     */
    private string $controllerPath;


    /**
     * app namespace from composer.json
     * @var string
     */
    private string $appNamespace;


    /**
     * bootstrap namespace from composer.json
     * @var string
     */
    private string $bootstrapNamespace;


    /**
     * @var string
     */
    private string $controllerType;

    /**
     * @var string
     */
    private string $controllerName;

    public function __construct(string $path)
    {
        parent::__construct();
        $this->rootPath       = $path;
        $this->controllerPath = $path.'\\app\\Http\\Controllers';
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

            if ($namespace == 'bootstrap/') {
                $this->bootstrapNamespace = $key;
            }
        }
    }

    /**
     * config for command
     */
    protected function configure()
    {
        $this->setName('make:controller')
            ->setDescription('Create controller!')
            ->setHelp('Create controller for api, sidemenu, action, shortcode.')
            ->addArgument('controllerName', InputArgument::REQUIRED, 'add controller name.')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'add controller type. like --type=api|shortcode|action|sidemenu',
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
        $option               = trim($input->getOption('type'));
        $this->controllerType = $option && in_array($option,
            $this->supportedOptions) ? $this->optionValue[$option] : '';
        $this->controllerName = str_replace('/', '\\', trim($input->getArgument('controllerName')));

        try {
            $this->createController();

            $output->writeln('<fg=green>Success: '.sprintf('controller created at app\\Http\\Controllers\\%s',
                    $this->controllerName).'</>');

        } catch (Exception $e) {
            $output->writeln('<fg=red>Error: '.$e->getMessage().'</>');
        }

        return true;
    }

    protected function createController(): void
    {
        $filePath = str_replace('/', '\\',
                ($this->controllerType ? $this->controllerType.DIRECTORY_SEPARATOR : '').$this->controllerName).'.php';

        $pathTree = $this->controllerPath;

        foreach (explode('\\', $filePath) as $path) {

            $pathTree .= '\\'.$path;

            if (!file_exists($pathTree)) {

                if (strpos($pathTree, '.php') !== false) {
                    file_put_contents($pathTree, $this->getControllerContent($this->controllerType));
                } else {
                    mkdir($pathTree);
                }

            } else {
                if (is_file($pathTree)) {
                    throw new Exception("Controller already exist at app\\Http\\Controllers\\".$this->controllerName);
                }
            }
        }
    }

    protected function getControllerContent($type): string
    {
        $array              = explode('\\', $this->controllerName);
        $className          = end($array);
        $type               = $type ? '\\'.$type : '';
        $namespaceFromInput = rtrim(str_replace($className, '', $this->controllerName), "\\");
        $rightNamespace     = ($namespaceFromInput ? '\\'.$namespaceFromInput : '').$type;


        $data = "<?php

namespace {$this->appNamespace}Http\Controllers{$rightNamespace};
 
use {$this->bootstrapNamespace}System\Controller;   

class {$className} extends Controller
{
  
  
}
";
        return $data;
    }

}
