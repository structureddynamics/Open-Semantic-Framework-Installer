<?php

  class CommandlineTool
  {
    /* Specify if if we output everything we got from the commands */
    protected $verbose = FALSE;

    /* Full path of the logfile */
    protected $log_file = '';
    
    /* Specify that the installation process occurs in an automatic deployment framework */
    protected $auto_deploy = TRUE;    

    protected $currentWorkingDirectory;

    function __construct()
    {
      $this->currentWorkingDirectory = getcwd();
    }
    
    private function commandReturn($commandReturnVal, $errorStatus, $errorLevel = 'error')
    {
      if(is_bool($commandReturnVal))
      {
        if($commandReturnVal === FALSE)
        {
          $commandReturnVal = 1;
        }
        else
        {
          $commandReturnVal = 0;
        }
      }
      
      if ($commandReturnVal > 0) 
      {
        switch (strtolower($errorLevel)) 
        {
          case 'ignore':
            return($commandReturnVal);
          break;
          
          case 'notice':
            $this->span("An occured but the script continue its process. Check the log to see what was the error: {$this->log_file}", 'notice');
          break;

          case 'warning':
            $this->span("An occured but the script continue its process. Check the log to see what was the error: {$this->log_file}", 'warn');
          break;

          case 'error':
            if(!$this->auto_deploy)
            {
              $this->span("A non-recoverable error happened. Check the log to see what was the error: {$this->log_file}", 'error');
              
              $yes = $this->isYes($this->getInput("Do you want to continue the execution. If yes, then try to fix this error by hands before continuing, otherwise errors may occurs later in the process? (yes/no)\n"));
              
              if (!$yes){
                exit($errorStatus);
              }              
            }
            else
            {
              exit($errorStatus);
            }
          break;
        }

        return($commandReturnVal);
      } 
      else 
      {
        return($commandReturnVal);
      }      
    }

    /**
     * Execute a shell command
     * The command is also logged into the logging file
     *     
     * @param $command    the shell command to execute
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

      return($this->commandReturn($return, 2, $errorLevel));
    }

    /**
     * Change the current folder of the script
     * The command is also logged into the logging file
     *     
     * @param mixed $dir  folder path where to go
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
      $this->log(array("cd {$dir}"), TRUE);

      $success = chdir($dir);

      return($this->commandReturn($success, 3, $errorLevel));
    }

    /**
     * Colorize an output to the shell terminal.
     * 
     * @param mixed $text    Text to echo into the terminal screen
     * @param mixed $color   Color to use
     * @param mixed $return  specify if we want to return the colorized text to
     *                       the script instead of the terminal
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
      if ($out == "") {
        $out = "[0m";
      }

      if ($return) {
        return(chr(27) . "$out$text" . chr(27) . "[0m");
      }
      else {
        echo chr(27) . "$out$text" . chr(27) . chr(27) . "[0m";
      }
    }

    /**
     * Outputs a header #1 (h1) message
     * 
     * @param string  $message   Message to output
     */
    public function h1($message)
    {
      $color = 'WHITE';
      $msglen = strlen($message) + 4;
      $this->cecho("\n", $color);
      $this->cecho(str_repeat('-', $msglen) . "\n", $color);
      $this->cecho("| {$message} |\n", $color);
      $this->cecho(str_repeat('-', $msglen) . "\n", $color);
      $this->cecho("\n", $color);
    }

    /**
     * Outputs a header #2 (h2) message
     * 
     * @param string  $message   Message to output
     */
    public function h2($message)
    {
      $color = 'CYAN';
      $this->cecho("\n", $color);
      $this->cecho("{$message}\n", $color);
      $this->cecho("\n", $color);
    }

    /**
     * Outputs a header #3 (h3) message
     * 
     * @param string  $message   Message to output
     */
    public function h3($message)
    {
      $color = 'BROWN';
      $this->cecho("\n", $color);
      $this->cecho("{$message}\n", $color);
      $this->cecho("\n", $color);
    }

    /**
     * Outputs a span message
     * 
     * @param string  $message   Message to output
     * @param string  $severity  Severity of message (optional)
     */
    public function span($message, $severity = 'info')
    {
      // Check severity
      switch ($severity) {
        case 'info':
          $color = 'WHITE';
          break;
        case 'notice':
          $color = 'CYAN';
          break;
        case 'debug':
          $color = 'BLUE';
          break;
        case 'warn':
          $color = 'YELLOW';
          break;
        case 'error':
          $color = 'RED';
          break;
        case 'good':
          $color = 'GREEN';
          break;
        default:
          $color = 'WHITE';
          break;
      }
      $this->cecho("{$message}\n", $color);
    }

    /**
     * Log information into the logging file
     * 
     * @param mixed $lines         An array of lines to log into the logging file
     * @param mixed $forceSilence  Specify if we want to overwrite the verbosity of 
     *                             the script and make sure that log() stay silent
     */
    public function log($lines, $forceSilence = FALSE)
    {
      foreach($lines as $line) {
        file_put_contents($this->log_file, $line."\n", FILE_APPEND);
        if($this->verbose && !$forceSilence) {
          $this->span($line, 'debug');
        }
      }
    }

    /**
     * Enable the verbosity of the class. Everything get outputed to the 
     * shell terminal
     */
    public function verbose()
    {
      $this->verbose = TRUE;
    }

    /**
     * Disable the verbosity of the class. No command output will be displayed
     * to the terminal.
     */
    public function silent()
    {
      $this->verbose = FALSE;
    }

    /**
     * Prompt the user with a question, wait for input, and return that input
     * from the user.
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
     * Check if the answer of an input is equivalent to "yes". The strings that
     * are equivalent to "yes" are:
     *   "1", "true", "on", "y" and "yes". Returns FALSE otherwise.
     * 
     * @param mixed $input Input to test
     * 
     * @param Returns TRUE if the input is equivalent to "yes", FALSE otherwise
     */
    public function isYes($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      $input = strtolower($input);
      $answer = filter_var($input, FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));

      if ($input == 'y') {
        return(TRUE);
      }

      return($answer);
    }

    /**
     * Check if the provided input is a boolean
     * 
     * @param mixed $input        Input to test
     * 
     * @param Returns TRUE if the input is a valid boolean, FALSE otherwise
     */
    public function isBoolean($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      $options = array(
        'flags' => FILTER_NULL_ON_FAILURE,
      );
      $validation = filter_var($input, FILTER_VALIDATE_BOOLEAN, $options);

      if (is_null($validation)) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Get a boolean value from an input
     * 
     * @param mixed $input        Input to parse
     * 
     * @param Returns TRUE or FALSE
     */
    public function getBoolean($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      $options = array(
        'flags' => FILTER_NULL_ON_FAILURE,
      );
      $result = (filter_var($input, FILTER_VALIDATE_BOOLEAN, $options)) ? 'true' : 'false';

      return($result);
    }

    /**
     * Check if the provided input is a integer number
     * 
     * @param mixed $input        Input to test
     * 
     * @param Returns TRUE if the input is a valid integer number, FALSE otherwise
     */
    public function isInteger($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      $validation = filter_var($input, FILTER_VALIDATE_INT);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is an alpha numeric string
     * 
     * @param mixed $input        Input to test
     * 
     * @param Returns TRUE if the input is a valid alpha numeric, FALSE otherwise
     */
    public function isAlphaNumeric($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with regex
      // https://stackoverflow.com/questions/336210/regular-expression-for-alphanumeric-and-underscores
      $validation = preg_match('/^[a-zA-Z0-9]*$/', $input);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a valid version
     * 
     * @param mixed $input        Input to test
     * 
     * @param Returns TRUE if the input is a valid version, FALSE otherwise
     */
    public function isVersion($input) {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with regex
      $validation = preg_match('/^(0|[1-9]\d*)(\.(0|[1-9]\d*)){0,2}(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/', $input);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a valid path
     * 
     * @param mixed $input        Input to test
     * @param mixed $absolute     Treat the path as absolute or not
     * 
     * @param Returns TRUE if the input is a valid path, FALSE otherwise
     */
    public function isPath($input, $absolute = TRUE) {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with regex
      if ($absolute = TRUE) {
        $validation = preg_match('#^(/[^/]+)+$#', rtrim($input, '/'));
      } else {
        $validation = preg_match('^[a-z0-9]([a-z0-9-]*[a-z0-9])?(/[a-z0-9]([a-z0-9-]*[a-z0-9])?)*$^', rtrim($input, '/'));
      }

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Get a path from an input
     * 
     * @param mixed $input        Input to parse
     * 
     * @param Returns string
     */
    public function getPath($input) {
      if ($input === NULL) {
        return(FALSE);
      }

      $result = rtrim($input, '/');

      return($result);
    }

    /**
     * Check if the provided input is a valid domain
     * 
     * @param mixed $input        Input to test
     * 
     * @param Returns TRUE if the input is a valid domain, FALSE otherwise
     */
    public function isDomain($input) {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with regex
      // https://stackoverflow.com/questions/3026957/how-to-validate-a-domain-name-using-regex-php
      $validation = preg_match('^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$^', $input);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a valid IP address
     * 
     * @param mixed $input        Input to test
     * 
     * @param Returns TRUE if the input is a valid IP address, FALSE otherwise
     */
    public function isIP($input) {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with filter
      $validation = filter_var($input, FILTER_VALIDATE_IP);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Check if the provided input is a port number
     * 
     * @param mixed $input        Input to test
     * 
     * @param Returns TRUE if the input is a valid port number, FALSE otherwise
     */
    public function isPort($input)
    {
      if ($input === NULL) {
        return(FALSE);
      }

      // Validate with filter
      $options = array(
        'options' => array(
          'min_range' => 1,
          'max_range' => 65535,
        ),
      );
      $validation = filter_var($input, FILTER_VALIDATE_INT, $options);

      if ($validation == FALSE) {
        return(FALSE);
      } else {
        return(TRUE);
      }
    }

    /**
     * Finds and replaces content in a file
     * 
     * @param string  $find       String to find
     * @param string  $replace    String to replace
     * @param string  $file       File to update
     */
    public function sed($find, $replace, $file, $modifiers = '')
    {
      $output = array();

      // Escape reserved sed characters
      $find = str_replace(array("\n", '"', '$', '>'), array('\n', '\"', '\$', '\>'), $find);
      $replace = str_replace(array("\n", '"', '$', '>'), array('\n', '\"', '\$', '\>'), $replace);
      
      // Build command
      $command = "sed -i \"s>{$find}>{$replace}>{$modifiers}\" \"{$file}\"";
      
      $this->log(array($command), TRUE);

      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 4));
    }

    /**
     * Append data to a file
     * 
     * @param string  $data       Data to append
     * @param string  $file       File to update
     */
    public function append($data, $file)
    {
      $this->log(array("Append: ", $data, $file), TRUE);
      
      // Run command
      $return = file_put_contents($file, $data, FILE_APPEND);

      return($this->commandReturn(($return === FALSE ? 5 : 0), 5));
    }

    /**
     * Sets an option in a ini file
     * 
     * @param string  $section    Section to find
     * @param string  $option     Option to change
     * @param string  $value      Value to set
     * @param string  $file       File to update
     */
    public function setIni($section, $option, $value, $file)
    {
      $output = array();
      $this->log(array($section, $option, $value, $file), TRUE);

      // Build command
      // https://stackoverflow.com/questions/10040255/edit-file-in-unix-using-sed
      // sed -ie '/^\[Section B\]/,/^\[.*\]/s/^\(\$param2[ \t]*=[ \t]*\).*$/\1new_value/' foo.txt
      $value = str_replace("/", "\/", $value);
      $command = "sed -i -e '/^\[{$section}\]/,/^\[.*\]/s/^\({$option}[ \\t]*=[ \\t]*\).*$/\\1{$value}/' \"{$file}\"";

      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 6));
    }

    /**
     * Creates a directory
     * 
     * @param string  $path       Path to create
     */
    public function mkdir($path)
    {
      $output = array();

      // Build command
      $command = "mkdir -p \"{$path}\"";
      
      $this->log(array($command), TRUE);

      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 7));
    }

    /**
     * Remove path from filesystem
     * 
     * @param string  $path       Path to remove
     * @param boolean $recursion  Enable or disable recursion (optional)
     */
    public function rm($path, $recursion = FALSE)
    {
      $output = array();

      // Build command
      $command = "rm -f";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      // Build command
      if(strpos($path, '*') !== FALSE)
      {
        $command .= " {$path}";
      }
      else
      {
        $command .= " \"{$path}\"";
      }

      $this->log(array($command), TRUE);      
      
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 8));
    }

    /**
     * Changes owner for a path
     * 
     * @param string  $path       Path to target
     * @param string  $own        Owner to apply
     * @param boolean $recursion  Enable or disable recursion (optional)
     */
    public function chown($path, $own, $recursion = FALSE)
    {
      $output = array();

      // Build command
      $command = "chown";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      
      // Build command
      $command .= " \"{$own}\" \"{$path}\"";

      $this->log(array($command), TRUE);
      
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 9));
    }

    /**
     * Changes group for a path
     * 
     * @param string  $path       Path to target
     * @param string  $grp        Group to apply
     * @param boolean $recursion  Enable or disable recursion (optional)
     */
    public function chgrp($path, $grp, $recursion = FALSE)
    {
      $output = array();

      // Build command
      $command = "chgrp";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      // Build command
      $command .= " \"{$grp}\" \"{$path}\"";

      $this->log(array($command), TRUE);
      
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 10));
    }

    /**
     * Changes permissions for a path
     * 
     * @param string  $path       Path to target
     * @param string  $mod        Permissions modifier in octal or symbolic notion
     * @param boolean $recursion  Enable or disable recursion (optional)
     */
    public function chmod($path, $mod, $recursion = FALSE)
    {
      $output = array();

      // Build command
      $command = "chmod";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      
      // Build command
      $command .= " \"{$mod}\" \"{$path}\"";

      $this->log(array($command), TRUE);      
      
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 11));
    }

    /**
     * Soft links a source file or directory
     * 
     * @param string  $src        Source file or directory
     * @param string  $dest       Destination file or directory (optional)
     */
    public function ln($src, $dest = '')
    {
      $output = array();

      // Build command
      $command = "ln -sf \"{$src}\"";
      // Check for destination
      if (!empty($dest)) {
        $command .= " \"{$dest}\"";
      }

      $this->log(array($command), TRUE);      
      
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 12));
    }

    /**
     * Copies source files or directories to destination
     * 
     * @param string  $src        Source file or directory
     * @param string  $dest       Destination file or directory
     * @param boolean $recursion  Enable or disable recursion (optional)
     */
    public function cp($src, $dest, $recursion = FALSE)
    {
      $output = array();

      // Build command
      $command = "cp -af";
      // Check for recursion
      if ($recursion == TRUE) {
        $command .= " -R";
      }
      // Build command
      if(strpos($src, '*') !== FALSE)
      {
        $command .= " {$src} \"{$dest}\"";
      }                     
      else
      {     
        $command .= " \"{$src}\" \"{$dest}\"";
      }

      $this->log(array($command), TRUE);      
      
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 13));
    }

    /**
     * Moves source files or directories to destination
     * 
     * @param string  $src        Source file or directory
     * @param string  $dest       Destination file or directory
     */
    public function mv($src, $dest)
    {
      $output = array();

      // Build command
      $command = '';
      
      if($src == '*')
      {       
        $command = "mv -f * \"{$dest}\"";
      }
      else
      {
        $command = "mv -f \"{$src}\" \"{$dest}\"";
      }  
        
      $this->log(array($command), TRUE);
      
      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 14));
    }

    /**
     * Unzips an archive in the ZIP format, using unzip command
     * 
     * @param string  $arch       Archive file
     * @param string  $dest       Destination directory (optional)
     */
    public function unzip($arch, $dest = '')
    {
      $output = array();

      // Build command
      $command = "unzip -o \"{$arch}\"";
      // Check for destination
      if (!empty($dest)) {
        $command .= " -d \"{$dest}\"";
      }
      
      $this->log(array($command), TRUE);

      exec($command, $output, $return);
      $this->log($output);

      return($this->commandReturn($return, 14));
    }

    /**
     * Downloads an URL to local system, using wget command
     * 
     * @param string  $url        Source URL
     * @param string  $dest       Destination directory (optional)
     */
    public function wget($url, $dest = '')
    {
      $output = array();

      // Build command
      $command = "wget -qN \"{$url}\"";
      // Check for destination
      if (!empty($dest)) {
        $command .= " -P \"{$dest}\"";
      }

      $this->log(array($command), TRUE);      
      
      exec($command, $output, $return);
      $this->log($output);

      if ($return > 0) {
        // get the file that was being download from the URL
        $pos = strrpos($url, '/');
        $filename = substr($url, $pos + 1);

        // Remove the file it tries to install
        exec('rm '.$filename);

        $this->span("Connection error while downloading the file $filename: retrying...", 'warn');

        $this->wget($url);
      }

      return(TRUE);
    }

    /**
     * Downloads an URL to local system, using curl command
     * 
     * @param string  $url        Source URL
     * @param string  $dest       Destination file (optional)
     */
    public function curl($url, $dest = '')
    {
      $output = array();

      $command = "curl {$url}";
      
      $this->log(array($command), TRUE);
      
      exec($command, $output, $return);
      $this->log($output);

      if ($return > 0) {
        if (!empty($dest)) {
          // Remove the file it tries to install
          exec("rm {$dest}");

          $this->span("Connection error downloading file $dest; retrying...", 'warn');
        }
        else {
          $this->span("Connection error using Curl; retrying...", 'warn');
        }

        $this->curl($url, $dest);
      }

      return(TRUE);
    }

  }

