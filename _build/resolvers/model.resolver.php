<?php

class modxModelResolver extends modxScriptVehicleResolver{
    public function install(){
        $this->loadService();
        
    }
    public function upgrade(){
        
    }
    public function uninstall(){
        
    }
}

$modelResolver=new modxModelResolver($transport->xpdo,$options,$object);
$modelResolver->run();