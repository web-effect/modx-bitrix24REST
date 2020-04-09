<?php
if(!class_exists('modxVehicleResolver')){
    class modxVehicleResolver{
        public $modx=null;
        public function __construct(&$modx,$options,&$object){
            $this->modx=$modx;
            $this->config=$options['component'];
            $this->options=$options;
            $this->object=$object;
        }
        public function loadService(){
            $this->service=$modx->getService(
                $this->config['namespace'],
                $this->config['name'],
                $modx->getOption(
                    $this->config['namespace'].'.core_path', null,
                    $modx->getOption('core_path').'components/'.$this->config['namespace'].'/'
                ).'model/'.$this->config['namespace'].'/'
            );
        }
        public function run(){
            switch ($this->options[xPDOTransport::PACKAGE_ACTION]) {
                case xPDOTransport::ACTION_INSTALL:
                    $this->install();
                    break;
                case xPDOTransport::ACTION_UPGRADE:
                    $this->upgrade();
                    break;
                case xPDOTransport::ACTION_UNINSTALL:
                    $this->uninstall();
                    break;
            }
        }
        public function install(){
            
        }
        public function upgrade(){
            
        }
        public function uninstall(){
            
        }
    }
    
    class modxScriptVehicleResolver extends modxVehicleResolver{
        
    }
    
    class modxObjectVehicleResolver extends modxVehicleResolver{
        
    }
}

return true;