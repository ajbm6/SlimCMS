<?php

namespace App\Modules;

use App\Helpers\SqliteMonologHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use MySQLHandler\MySQLHandler;
use Slim\Container;
use Slim\App;

class LoggerModule extends AModule
{
    const MODULE_NAME = 'logger';

    protected $registerDi = false;

    public function initialization(App $app)
    {
        parent::initialization($app);

        $this->registerDi();

        $this->container->get('logger')->addInfo("Info - Logger initialization", []);
        $this->container->get('logger')->addInfo("Info - Request Url", [$_SERVER['REQUEST_URI']]);
        $this->container->get('logger')->addInfo("Info - Request Method", [$_SERVER['REQUEST_METHOD']]);

        foreach ($this->container->modules as $name) {
            if( $name == 'core' ){
                continue;
            }
            
            $this->container->dispatcher->addListener('module.' . $name . '.beforeInitialization', function ($event) {
                $event->getLogger()->addInfo("action beforeInitialization", [$event->getParam()->getName()]);
            });
/*
            $this->container->dispatcher->addListener('module.' . $name . '.beforeRegister.route', function ($event) {
                echo "action beforeRegister route";
            });

            $this->container->dispatcher->addListener('module.' . $name . '.afterRegister.route', function ($event) {
                echo "action afterRegister route";
            });

            $this->container->dispatcher->addListener('module.' . $name . '.beforeRegister.di', function ($event) {
                echo "action beforeRegister di";
            });

            $this->container->dispatcher->addListener('module.' . $name . '.afterRegister.di', function ($event) {
                echo "action afterRegister di";
            });

            $this->container->dispatcher->addListener('module.' . $name . '.beforeRegister.middleware', function ($event) {
                echo "action beforeRegister middleware";
            });

            $this->container->dispatcher->addListener('module.' . $name . '.afterRegister.middleware', function ($event) {
                echo "action afterRegister middleware";
            });
*/
            $this->container->dispatcher->addListener('module.' . $name . '.afterInitialization', function ($event) {
                $event->getLogger()->addInfo("action afterInitialization", [$event->getParam()->getName()]);
            });
        }

        $this->container->dispatcher->addListener('app.afterRun', function ($event){
            $workTime = round((microtime(true) - $GLOBALS['startTime']), 3);
            $logger = $event->getLogger();
            $logger->addInfo("Statistic - work time: ", [$workTime . 's']);
            $logger->addInfo("Statistic - memory usage: ", [memoryFormat(memory_get_usage())]);
            $logger->addInfo("Statistic - max memory usage: ", [memoryFormat(memory_get_peak_usage())]);
            $logger->addInfo("Info - Stop Application", []);
        });
    }

    public function registerDi()
    {
        if ($this->registerDi) {
            return;
        }

        // Register service provider
        $this->container['logger'] = function ($c) {
            $logger = new Logger('slimcms_core');

            $handler = new StreamHandler(ROOT_PATH . "log/app.log");
            if ($c['settings']['log_system'] == 'db') {
                $handler = new MySQLHandler(DB::connection()->getPdo(), "logging");
                if (DB::connection()->getDriverName() == 'sqlite') {
                    $handler = new SqliteMonologHandler(DB::connection()->getPdo(), "logging");
                }
            }

            if( $c['settings']['use_log'] ){
                $logger->pushHandler($handler);
            }
	    return $logger;
        };

        $this->registerDi = true;
    }

}
