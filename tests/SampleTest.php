<?php
namespace ManyMailerPlus\tests;

use ManyMailerPlus\libraries\Tx_service;
use PHPUnit\Framework\TestCase;


/**
 * ServiceTest
 * @group group
 */
class ServiceTest extends TestCase
{
    public function get_service_obj()
    {
        $this->services = $this->get_service_classes();
    }
    

    public function get_service_classes(){
        $_container = array();
        $path = getcwd() ."/libraries/Tx_service/drivers";
        $_container['files'] = array_diff(scandir($path), array('.','..'));
        foreach ($_container['files'] as $file){
            if($this->load_service($file)) $_container['loaded_classes'][] = basename($file, '.php');
        }
        return $_container;
    }
    public function load_service($file)
    {         
        $class_file =  "./libraries/Tx_service/drivers/". $file;
        $this->dump((is_readable($class_file)) ? "$class_file exists" : "$class_file does not exist");
        if (is_readable($class_file)) include($class_file);
        return class_exists(basename($file, '.php')); 
        
    }
    
    /** @test */
    public function test_get_service_apiKey()
    {
        // Test
        // $this->dump(\realpath('/'));
        if (empty($this->services)) $this->get_service_obj();
        // $this->assertIsArray($this->services);
        foreach(array_reverse($this->services['loaded_classes']) as $class){
            $this->dump("Instantiating $class");
            $service = new $class;
            $key = $service->get_api_key();
            $this->assertNull($key);
        }
    }

    public function dump($str){
        // throw new \Exception( $str);        
        \fwrite(STDERR, (is_array($str) ? print_r($str) : $str) .PHP_EOL );
    }
}
