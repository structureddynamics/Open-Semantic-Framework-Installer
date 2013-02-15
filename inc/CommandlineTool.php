<?php
  
  class CommandlineTool
  {
    /* Specify if if we output everything we got from the commands */
    protected $verbose = FALSE;
    
    /* Full path of the logfile */
    protected $log_file = '';
    
    protected $currentWorkingDirectory;
    
    function __construct()
    {
      $this->currentWorkingDirectory = getcwd();
    }    
    
    /**
    * Execute a shell command. The command is also logged into the logging file.
    *     
    * @param $command the shell command to execute
    * @param $errorLevel the level of the error if an error happens. There are 4 levels:
    *                    (1) ignore, (2) notice, (3) warning and (4) error. The ignore
    *                    level doesn't display anything, the notice level output an error
    *                    in light-cyan, the warning level output an error message in yellow
    *                    color and a error error output an error message in red and 
    *                    stops the execution of the script.
    * 
    * @return Returns TRUE if the command succeeded, FALSE otherwise
    */
    public function exec($command, $errorLevel = 'error')
    {
      $output = array();
      $this->log(array($command), TRUE);      
      exec($command, $output, $return);
      
      $this->log($output);      
      
      if($return == 0)
      {
        switch(strtolower($errorLevel))
        {
          case 'notice':
            $this->cecho("An occured but the script continue its process. Check the log to see what was the error: ".$this->log_file."\n", 'LIGHT_CYAN');
          break;
          
          case 'warning':
            $this->cecho("An occured but the script continue its process. Check the log to see what was the error: ".$this->log_file."\n", 'YELLOW');
          break;
          
          case 'error':
            $this->cecho("A non-recoverable error happened. Check the log to see what was the error: ".$this->log_file."\n", 'RED');
            exit(1);
          break;
        }
        
        return(TRUE);
      }
      else
      {
        return(FALSE);
      }
    }
    
    /**
    * Change the current folder of the script. The command is also logged into the logging file.
    *     
    * @param mixed $dir folder path where to go
    * @param $errorLevel the level of the error if an error happens. There are 4 levels:
    *                    (1) ignore, (2) notice, (3) warning and (4) error. The ignore
    *                    level doesn't display anything, the notice level output an error
    *                    in light-cyan, the warning level output an error message in yellow
    *                    color and a error error output an error message in red and 
    *                    stops the execution of the script. 
    * 
    * @return Return TRUE if the comman succeeded, FALSE otherwise
    */
    public function chdir($dir, $errorLevel = 'error')
    {
      $this->log(array('cd '.$dir), TRUE);      

      $error = chdir($dir);
      
      if($error)
      {
        switch(strtolower($errorLevel))
        {
          case 'notice':
            $this->cecho("An occured but the script continue its process. Check the log to see what was the error: ".$this->log_file."\n", 'LIGHT_CYAN');
          break;
          
          case 'warning':
            $this->cecho("An occured but the script continue its process. Check the log to see what was the error: ".$this->log_file."\n", 'YELLOW');
          break;
          
          case 'error':
            $this->cecho("A non-recoverable error happened. Check the log to see what was the error: ".$this->log_file."\n", 'RED');
            exit(1);
          break;
        }      
        
        return(FALSE);
      }
      else
      {
        return(TRUE);
      }
    }  
    
    /**
    * Colorize an output to the shell terminal.
    * 
    * @param mixed $text Text to echo into the terminal screen
    * @param mixed $color Color to use
    * @param mixed $return specify if we want to return the colorized text to the script instead of the terminal
    */
    public function cecho($text, $color = "NORMAL", $return = FALSE)
    {
      // Log the text
      file_put_contents($this->log_file, $text, FILE_APPEND);
      
      $_colors = array(
        'LIGHT_RED'    => "[1;31m",
        'LIGHT_GREEN'  => "[1;32m",
        'YELLOW'       => "[1;33m",
        'LIGHT_BLUE'   => "[1;34m",
        'MAGENTA'      => "[1;35m",
        'LIGHT_CYAN'   => "[1;36m",
        'WHITE'        => "[1;37m",
        'NORMAL'       => "[0m",
        'BLACK'        => "[0;30m",
        'RED'          => "[0;31m",
        'GREEN'        => "[0;32m",
        'BROWN'        => "[0;33m",
        'BLUE'         => "[0;34m",
        'CYAN'         => "[0;36m",
        'BOLD'         => "[1m",
        'UNDERSCORE'   => "[4m",
        'REVERSE'      => "[7m",
      );    
      
      $out = $_colors["$color"];
      
      if($out == "")
      { 
        $out = "[0m"; 
      }
      
      if($return)
      {
        return(chr(27)."$out$text".chr(27)."[0m");
      }
      else
      {
        echo chr(27)."$out$text".chr(27).chr(27)."[0m";
      }
    }
    
    /**
    * Log information into the logging file
    * 
    * @param mixed $lines An array of lines to log into the logging file
    * @param mixed $forceSilence Specify if we want to overwrite the verbosity of 
    *                            the script and make sure that log() stay silent.
    */
    public function log($lines, $forceSilence=FALSE)
    {
      foreach($lines as $line)
      {
        file_put_contents($this->log_file, $line."\n", FILE_APPEND);

        if($this->verbose && !$forceSilence)
        {
          $this->cecho($line."\n", 'BLUE');
        }
      }
    }  
    
    /**
    * Enable the verbosity of the class. Everything get outputed to the shell terminal
    */
    public function verbose()
    {
      $this->verbose = TRUE;      
    }  
    
    /**
    * Disable the verbosity of the class. No command output will be displayed to the terminal.
    */
    public function silent()
    {
      $this->verbose = FALSE;
    }

    /**
    * Prompt the user with a question, wait for input, and return that input from the user.
    *     
    * @param mixed $msg Message to display to the user before waiting for an answer.
    * 
    * @return Returns the answer of the user.
    */
    public function getInput($msg)
    {
      fwrite(STDOUT, $this->cecho("$msg: ", 'MAGENTA', TRUE));
      $varin = trim(fgets(STDIN));
      
      $this->log(array("[USER-INPUT]: ".$varin."\n"), TRUE);
      
      return $varin;
    }       
    
    /**
    * Check if the answer of an input is equivalent to "yes". The strings that are equivalent to "yes" are:    
    * "1", "true", "on", "y" and "yes". Returns FALSE otherwise. 
    * 
    * @param mixed $input Input to test
    * 
    * @param Returns TRUE if the input is equivalent to "yes", FALSE otherwise
    */
    public function isYes($input) 
    {
      $input = strtolower($input);
      
      $answer = filter_var($input, FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
      
      if($input === NULL)
      {
        return(FALSE);
      }  
      
      if($input == 'y')      
      {
        return(TRUE);
      }
      
      return($answer);
    }    
  }
  
?>
