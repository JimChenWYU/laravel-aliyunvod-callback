<?php

namespace JimChen\AliyunVodMNS\Console;

use Illuminate\Console\GeneratorCommand;

class CallbackEventMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:vodcallback:callback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new vodcallback event class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'VodCallback Handler';

    /**
     * Determine if the class already exists.
     *
     * @param string $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        return class_exists($rawName);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/handler.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Listeners';
    }
}
