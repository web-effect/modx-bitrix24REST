<?php

class bitrix24REST
{
    const NAMESPACE='bitrix24rest';
    public $modx;
    public $authenticated = false;
    public $errors = array();

    function __construct(modX &$modx, array $config = array()){
        $this->modx = &$modx;
        
        $localPath='components/'.static::NAMESPACE.'/';
        $corePath = $this->modx->getOption(static::NAMESPACE.'.core_path', $config, $this->modx->getOption('core_path') . $localPath);
        $assetsPath = $this->modx->getOption(static::NAMESPACE.'.assets_path', $config, $this->modx->getOption('assets_path') . $localPath);
        $assetsUrl = $this->modx->getOption(static::NAMESPACE.'.assets_url', $config, $this->modx->getOption('assets_url') . $localPath);
        $connectorUrl = $assetsUrl . 'connector.php';
        $context_path = $this->modx->context->get('key')=='mgr'?'mgr':'web';

        $this->config = array_merge(array(
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . $context_path . '/css/',
            'jsUrl' => $assetsUrl . $context_path . '/js/',
            'jsPath' => $assetsPath . $context_path . '/js/',
            'imagesUrl' => $assetsUrl . $context_path . '/img/',
            'connectorUrl' => $connectorUrl,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'templatesPath' => $corePath . 'elements/templates/',
            'chunkSuffix' => '.chunk.tpl',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/',
            'vendorPath' => $corePath . 'vendor/',
        ), $config);

        $this->modx->lexicon->load(static::NAMESPACE.':default');
        $this->authenticated = $this->modx->user->isAuthenticated($this->modx->context->get('key'));
        spl_autoload_register(array($this,'autoload'));
    }

    public function initialize($scriptProperties = array(),$ctx = 'web'){
        $this->config['options'] = $scriptProperties;
        $this->config['ctx'] = $ctx;
        
        if($scriptProperties['mode']=='webhook'){
            define('C_REST_WEB_HOOK_URL','https://'.$this->modx->getOption(static::NAMESPACE.'.webhook.accaunt').'.bitrix24.ru/rest/'.$this->modx->getOption(static::NAMESPACE.'.webhook.user',null,1,true).'/'.base64_decode($this->modx->getOption(static::NAMESPACE.'.webhook.key')).'/');
        }
        require_once($this->config['vendorPath'].'bitrix-tools/crest/src/crest.php');
        $this->sdk='CRest';
        
        return true;
    }
    
    public function autoload($class){
        $class = explode('/',str_replace("\\", "/", $class));
        $className = array_pop($class);
        $classPath = strtolower(implode('/',$class));
        
        $path = $this->config['modelPath'].'/'.$classPath.'/'.$className.'.php';
        if(!file_exists($path))return false;
        include $path;
    }
    
    public function loadAssets($ctx){
        if(!$this->modx->controller)return false;
        $this->modx->controller->addLexiconTopic(static::NAMESPACE.':default');
        switch($ctx){
            case 'mgr':{
                $this->modx->controller->addJavascript($this->config['assetsUrl'].'mgr/js/'.static::NAMESPACE.'.js');
            }
        }
    }
    
    public function processActions(&$hook,$fields,$actions,$response=array()){
        $REST=$this->sdk;
        $success=true;
        foreach($actions as $name=>$options){
            $options=$this->prepareAction($hook,$fields,$name,$options,$response);
            if(!$options)continue;
            
            if($options['batch']){
                $_options=array();
                foreach($options['batch'] as $key=>$action){
                    $action=$this->prepareAction($hook,$fields,$key,$action,$response);
                    if(!$action)continue;
                    $method=$action['_action'];
                    unset($action['_action']);
                    $_options[$key]=[
                        'method'=>$method,
                        'params'=>$action
                    ];
                }
                //$this->modx->log(1,print_r($_options,1));
                $response=$REST::callBatch($_options,$options['halt']?:0);
                //$this->modx->log(1,print_r($response,1));
            }else{
                $_options=$options;
                unset($_options['success']);
                unset($_options['failure']);
                unset($_options['_action']);
                //$this->modx->log(1,print_r($_options,1));
                $response=$REST::call($options['_action'],$_options);
                //$this->modx->log(1,print_r($response,1));
            }
            
            if(!empty($response['error'])){
                $hook->addError(static::NAMESPACE,$response['error_message']);
                $this->modx->log(MODX_LOG_LEVEL_ERROR,print_r($response,1));
                $success=false;
                break;
            }else{
                if(!empty($response['result'])&&$options['success']){
                    $success=$this->processActions($hook,$fields,$options['success'],$response);
                }
                if(empty($response['result'])&&$options['failure']){
                    $success=$this->processActions($hook,$fields,$options['failure'],$response);
                }
                if(!$success)break;
            }
        }
        return $success;
    }
    
    public function prepareAction(&$hook,$fields,$name,$options,$response=array()){
        if(is_scalar($options)&&strpos($options,'@SNIPPET')===0){
            $options=$this->modx->runSnippet(trim(substr($options,8)),['name'=>$name,'hook'=>$hook]);
            if(!is_array($options))$options=json_decode($options,true);
            if(!$options)return false;
        }
        if(!$options['_action'])$options['_action']=$name;
        //$this->modx->log(1,print_r($fields,1));
        $options=$this->processOptions($options,array_merge($response,$fields));
        return $options;
    }
    
    public function processOptions($options,$placeholders){
        $this->modx->getParser();
        $maxIterations = (integer) $this->modx->getOption('parser_max_iterations', null, 10);
        foreach($options as $key=>&$option){
            if($key==='success'||$key==='failure')continue;
            if(is_scalar($option)){
                if($this->modx->parser instanceof pdoParser)$option = $this->modx->parser->pdoTools->getChunk('@INLINE '.$option, $placeholders);
                else $option = $chunk->process($placeholders,$option);
                $this->modx->parser->processElementTags('', $option, false, false, '[[', ']]', array(), $maxIterations);
                $this->modx->parser->processElementTags('', $option, true, true, '[[', ']]', array(), $maxIterations);
            }elseif(is_array($option)){
                $option=$this->processOptions($option,$placeholders);
            }
        }
        return $options;
    }
}
