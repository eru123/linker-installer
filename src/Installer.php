<?php

namespace Linker\Installer;

use Linker\FileSystem\FileSystem as FS;

class Installer {
    private array $commands = [
        "create" => [
            "alias" => ["new", "generate", "install","-i"],
            "description" => "Create new project"
        ],
        "-v" => [
            "alias" => ["version","-version","--version","--v"],
            "description" => "Display installer version"
        ],
        "-vv" => [
            "alias" => ["-verbose","--verbose","--vv"],
            "description" => "Display debug screen"
        ]
    ];
    public function __construct(){

    }
    public static function echo(string $str,int $color = 0){
        echo "\033[".$color."m$str\033[0m";
    }
    public function create(string $template,string $project,bool $verbose = false){
        $scan = FS::scandirTree($template);
        try {
            self::echo("Generating files and directory\n\n",1);
            foreach($scan as $tmp){
                if(is_dir($tmp)){
                    if($verbose){
                        echo "Creating directory - ".str_replace($template,"",$tmp).PHP_EOL;
                    }
                    FS::mkdir(str_replace($template,$project,$tmp));
                } elseif (is_file($tmp)){
                    if($verbose){
                        echo "Creating file - ".str_replace($template,"",$tmp).PHP_EOL;
                    }
                    copy($tmp,str_replace($template,$project,$tmp));
                }
            }
            sleep(5);
            exec("cd $project && composer install --verbose");
        } catch(\Exception $e){
            self::echo((string)$e->getMessage() ?? "Failed to generate all files",31);
        }
        return TRUE;
    }
    public function help(int $space = 2){
        echo PHP_EOL;

        $max_command_name = 0;

        foreach($this->commands as $command => $info)
            if(strlen($command) > $max_command_name)
                $max_command_name = strlen($command);

        $max_command_name += $space;

        self::echo("Command",1);
        for ($i=0; $i < ($max_command_name - strlen("Command")); $i++) { 
            echo " ";
        }
        self::echo("Description\n\n",1);

        foreach($this->commands as $command => $info){
            self::echo($command,34);
            for ($i=0; $i < $max_command_name - strlen($command); $i++) { 
                echo " ";
            }
            echo @$info["description"].PHP_EOL;
            for ($i=0; $i < $max_command_name; $i++) { 
                echo " ";
            }
            self::echo("Alias: ".json_encode(@$info["alias"])."\n\n",96);
        }
    }
    public function verify_command(string $arg,string $command){
        $arg = strtolower($arg);
        if(isset($this->commands[$command])){
            if($command == $arg){
                return TRUE;
            }
            $command = $this->commands[$command];
            foreach((array)$command["alias"] as $alias){
                if($arg == $alias){
                    return TRUE;
                }
            }
        }
        return FALSE;
    }
    public function init(string $dir,array $argv,string $version){
        $verbose = false;
        if(isset($argv)){
            if(count($argv) > 1){
                $si = 1; // starting index
                $ei = count($argv); //ending index
                for ($i=$si; $i < $ei; $i++) { 
                    $arg = $arg = $argv[$i];
                    $next_arg = $argv[$i + 1] ?? NULL;
                    $skipped_arg = $argv[$i + 2] ?? NULL;
                    if($this->verify_command($arg,"-vv")){
                        $verbose = TRUE;
                    }

                    if($this->verify_command($arg, "-v")){
                        echo $version;
                        return TRUE;
                    }

                    if($this->verify_command($arg, "create")){
                        if(isset($argv[$i + 1])){
                            $next_arg = rtrim($next_arg,"/")."/";
                            $dir = rtrim($dir,"/")."/template/";
                            if(is_dir($next_arg) && count(FS::scandir($next_arg))>0){
                                self::echo("Project folder contains files, Make sure you working on an empty directory\n",31);
                                return TRUE;
                            } elseif(!is_dir($next_arg) && !FS::mkdir($next_arg)){
                                self::echo("Failed to create project! Make sure that the directory is writable.\n",31);
                                return TRUE;
                            }
                            if(isset($argv[$i + 2]) && $this->verify_command($argv[$i + 2],"-vv")){
                                $verbose = TRUE;
                                
                            }
                            return $this->create($dir,$next_arg,$verbose);
                        } else {
                            self::echo("Project name is missing\n",31);
                            self::echo("HINT: ",1);
                            self::echo("linker create <project_name>\n",33);
                            $this->help();
                            return TRUE;
                        }
                    }
                }
                return TRUE;
            } else {
                $this->help();
            }
        }
        
        return FALSE;
    }
}