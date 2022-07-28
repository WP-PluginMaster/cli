<?php

namespace PluginMaster\Console;


use Exception;
use Symfony\Component\Console\Application;

class ConsoleApplication
{
    public static function run(string $path): void
    {
        $app = new Application();
        $app->add(new ControllerCreateCommand($path));
        $app->add(new MiddlewareCreateCommand($path));
        $app->add(new ProviderCreateCommand($path));

        try {
            $app->run();
        } catch (Exception $e) {
            echo "Something went wrong";
        }
    }
}
